<?php
/**
 * Consent Logger class.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Consent;

use LightweightPlugins\Cookie\Database\Schema;
use LightweightPlugins\Cookie\Options;

/**
 * Logs consent to database for GDPR compliance.
 */
final class Logger {

	/**
	 * Log consent to database.
	 *
	 * @param string              $consent_id  Unique consent identifier.
	 * @param array<string, bool> $categories  Category consent states.
	 * @param string              $action_type Action type (accept_all, reject_all, customize).
	 * @return bool
	 */
	public function log( string $consent_id, array $categories, string $action_type ): bool {
		global $wpdb;

		$table_name = $wpdb->prefix . Schema::TABLE_CONSENTS;

		// Get IP hash (anonymized for GDPR).
		$ip_hash = $this->get_anonymized_ip();

		// Get user agent (truncated).
		$user_agent = $this->get_user_agent();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->insert(
			$table_name,
			[
				'consent_id'     => $consent_id,
				'ip_hash'        => $ip_hash,
				'categories'     => wp_json_encode( $categories ),
				'policy_version' => Options::get( 'policy_version' ),
				'action_type'    => $action_type,
				'user_agent'     => $user_agent,
				'created_at'     => current_time( 'mysql' ),
			],
			[ '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
		);

		return false !== $result;
	}

	/**
	 * Get anonymized IP address hash.
	 *
	 * @return string SHA-256 hash of anonymized IP.
	 */
	private function get_anonymized_ip(): string {
		$ip = $this->get_client_ip();

		// Anonymize IP before hashing (remove last octet for IPv4, last 80 bits for IPv6).
		$ip = $this->anonymize_ip( $ip );

		// Hash the anonymized IP.
		return hash( 'sha256', $ip . wp_salt( 'auth' ) );
	}

	/**
	 * Get client IP address.
	 *
	 * @return string
	 */
	private function get_client_ip(): string {
		$ip_keys = [
			'HTTP_CF_CONNECTING_IP', // Cloudflare.
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'REMOTE_ADDR',
		];

		foreach ( $ip_keys as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );

				// Handle comma-separated IPs (X-Forwarded-For).
				if ( str_contains( $ip, ',' ) ) {
					$ips = explode( ',', $ip );
					$ip  = trim( $ips[0] );
				}

				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return '0.0.0.0';
	}

	/**
	 * Anonymize IP address for GDPR compliance.
	 *
	 * @param string $ip IP address.
	 * @return string Anonymized IP.
	 */
	private function anonymize_ip( string $ip ): string {
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			// IPv4: Zero out last octet.
			return preg_replace( '/\.\d+$/', '.0', $ip ) ?? $ip;
		}

		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			// IPv6: Zero out last 80 bits (5 groups).
			return preg_replace( '/:[\da-f]+:[\da-f]+:[\da-f]+:[\da-f]+:[\da-f]+$/i', ':0:0:0:0:0', $ip ) ?? $ip;
		}

		return $ip;
	}

	/**
	 * Get user agent string (truncated).
	 *
	 * @return string
	 */
	private function get_user_agent(): string {
		if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
			return '';
		}

		$user_agent = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );

		// Truncate to 255 characters.
		return substr( $user_agent, 0, 255 );
	}
}
