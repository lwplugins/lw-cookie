<?php
/**
 * Consent Manager class.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Consent;

use LightweightPlugins\Cookie\Options;

/**
 * Manages consent state and operations.
 */
final class Manager {

	/**
	 * Cookie name for consent.
	 */
	public const COOKIE_NAME = 'lw_cookie_consent';

	/**
	 * Current consent data.
	 *
	 * @var array<string, mixed>|null
	 */
	private ?array $consent = null;

	/**
	 * Storage instance.
	 *
	 * @var Storage
	 */
	private Storage $storage;

	/**
	 * Logger instance.
	 *
	 * @var Logger
	 */
	private Logger $logger;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->storage = new Storage();
		$this->logger  = new Logger();
		$this->load_consent();
	}

	/**
	 * Check if user has given consent.
	 *
	 * @return bool
	 */
	public function has_consent(): bool {
		return null !== $this->consent && ! empty( $this->consent['id'] );
	}

	/**
	 * Check if consent is valid (matches current policy version).
	 *
	 * @return bool
	 */
	public function is_consent_valid(): bool {
		if ( ! $this->has_consent() ) {
			return false;
		}

		$current_version = Options::get( 'policy_version' );
		$consent_version = $this->consent['version'] ?? '';

		return $current_version === $consent_version;
	}

	/**
	 * Check if a specific category is allowed.
	 *
	 * @param string $category Category key (necessary, functional, analytics, marketing).
	 * @return bool
	 */
	public function is_category_allowed( string $category ): bool {
		// Necessary cookies are always allowed.
		if ( 'necessary' === $category ) {
			return true;
		}

		if ( ! $this->has_consent() || ! $this->is_consent_valid() ) {
			return false;
		}

		return ! empty( $this->consent['categories'][ $category ] );
	}

	/**
	 * Get all allowed categories.
	 *
	 * @return array<string, bool>
	 */
	public function get_allowed_categories(): array {
		$categories = [
			'necessary'  => true,
			'functional' => false,
			'analytics'  => false,
			'marketing'  => false,
		];

		if ( $this->has_consent() && $this->is_consent_valid() ) {
			$consent_categories = $this->consent['categories'] ?? [];
			foreach ( $consent_categories as $key => $value ) {
				if ( isset( $categories[ $key ] ) ) {
					$categories[ $key ] = (bool) $value;
				}
			}
		}

		// Necessary is always true.
		$categories['necessary'] = true;

		return $categories;
	}

	/**
	 * Get current consent ID.
	 *
	 * @return string|null
	 */
	public function get_consent_id(): ?string {
		return $this->consent['id'] ?? null;
	}

	/**
	 * Save consent.
	 *
	 * @param array<string, bool> $categories Category consent states.
	 * @param string              $action     Action type (accept_all, reject_all, customize).
	 * @return void
	 */
	public function save_consent( array $categories, string $action = 'customize' ): void {
		// Ensure necessary is always true.
		$categories['necessary'] = true;

		$consent_id = $this->generate_consent_id();

		$this->consent = [
			'id'         => $consent_id,
			'version'    => Options::get( 'policy_version' ),
			'timestamp'  => time(),
			'categories' => $categories,
		];

		// Save to cookie.
		$this->storage->save_cookie( $this->consent );

		// Log to database.
		$this->logger->log( $consent_id, $categories, $action );
	}

	/**
	 * Accept all categories.
	 *
	 * @return void
	 */
	public function accept_all(): void {
		$this->save_consent(
			[
				'necessary'  => true,
				'functional' => true,
				'analytics'  => true,
				'marketing'  => true,
			],
			'accept_all'
		);
	}

	/**
	 * Reject all optional categories.
	 *
	 * @return void
	 */
	public function reject_all(): void {
		$this->save_consent(
			[
				'necessary'  => true,
				'functional' => false,
				'analytics'  => false,
				'marketing'  => false,
			],
			'reject_all'
		);
	}

	/**
	 * Revoke consent.
	 *
	 * @return void
	 */
	public function revoke(): void {
		$this->consent = null;
		$this->storage->delete_cookie();
	}

	/**
	 * Load consent from cookie.
	 *
	 * @return void
	 */
	private function load_consent(): void {
		$this->consent = $this->storage->load_cookie();
	}

	/**
	 * Generate a unique consent ID.
	 *
	 * @return string UUID v4.
	 */
	private function generate_consent_id(): string {
		$data    = random_bytes( 16 );
		$data[6] = chr( ord( $data[6] ) & 0x0f | 0x40 );
		$data[8] = chr( ord( $data[8] ) & 0x3f | 0x80 );

		return vsprintf( '%s%s-%s-%s-%s-%s%s%s', str_split( bin2hex( $data ), 4 ) );
	}
}
