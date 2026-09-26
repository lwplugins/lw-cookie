<?php
/**
 * Minimal WP_Error double for unit tests (WordPress is not loaded).
 *
 * Only the members the plugin code reads are implemented.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

// phpcs:ignoreFile -- test double mirroring a WordPress core class name.

class WP_Error {

	/**
	 * @var string
	 */
	private string $code;

	/**
	 * @var string
	 */
	private string $message;

	/**
	 * @var mixed
	 */
	private mixed $data;

	public function __construct( string $code = '', string $message = '', mixed $data = '' ) {
		$this->code    = $code;
		$this->message = $message;
		$this->data    = $data;
	}

	public function get_error_code(): string {
		return $this->code;
	}

	public function get_error_message(): string {
		return $this->message;
	}

	public function get_error_data(): mixed {
		return $this->data;
	}
}
