<?php
/**
 * Consent Storage class.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Consent;

use LightweightPlugins\Cookie\Options;

/**
 * Handles cookie storage for consent.
 */
final class Storage {

	/**
	 * Cookie name.
	 */
	public const COOKIE_NAME = 'lw_cookie_consent';

	/**
	 * Save consent to cookie.
	 *
	 * @param array<string, mixed> $consent Consent data.
	 * @return bool
	 */
	public function save_cookie( array $consent ): bool {
		$duration = (int) Options::get( 'consent_duration', 365 );
		$expiry   = time() + ( $duration * DAY_IN_SECONDS );

		$cookie_value = $this->encode_consent( $consent );

		// Set cookie with secure options.
		return setcookie(
			self::COOKIE_NAME,
			$cookie_value,
			[
				'expires'  => $expiry,
				'path'     => COOKIEPATH,
				'domain'   => COOKIE_DOMAIN,
				'secure'   => is_ssl(),
				'httponly' => false, // Must be accessible by JS.
				'samesite' => 'Lax',
			]
		);
	}

	/**
	 * Load consent from cookie.
	 *
	 * @return array<string, mixed>|null
	 */
	public function load_cookie(): ?array {
		if ( ! isset( $_COOKIE[ self::COOKIE_NAME ] ) ) {
			return null;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$cookie_value = wp_unslash( $_COOKIE[ self::COOKIE_NAME ] );

		return $this->decode_consent( $cookie_value );
	}

	/**
	 * Delete consent cookie.
	 *
	 * @return bool
	 */
	public function delete_cookie(): bool {
		return setcookie(
			self::COOKIE_NAME,
			'',
			[
				'expires'  => time() - YEAR_IN_SECONDS,
				'path'     => COOKIEPATH,
				'domain'   => COOKIE_DOMAIN,
				'secure'   => is_ssl(),
				'httponly' => false,
				'samesite' => 'Lax',
			]
		);
	}

	/**
	 * Encode consent data to cookie string.
	 *
	 * @param array<string, mixed> $consent Consent data.
	 * @return string
	 */
	private function encode_consent( array $consent ): string {
		$json = wp_json_encode( $consent );

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		return base64_encode( $json );
	}

	/**
	 * Decode consent data from cookie string.
	 *
	 * @param string $cookie_value Cookie value.
	 * @return array<string, mixed>|null
	 */
	private function decode_consent( string $cookie_value ): ?array {
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$json = base64_decode( $cookie_value, true );

		if ( false === $json ) {
			return null;
		}

		$consent = json_decode( $json, true );

		if ( ! is_array( $consent ) ) {
			return null;
		}

		return $consent;
	}
}
