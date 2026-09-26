<?php
/**
 * Cookie Ability Definitions for LW Site Manager.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\SiteManager;

/**
 * Registers Cookie-specific abilities with the WordPress Abilities API.
 */
final class CookieAbilities {

	/**
	 * Opt-in for LW Site Manager's MCP server: Site Manager only exposes its
	 * own site-manager/* abilities automatically, so companion abilities must
	 * flag themselves as public MCP tools. Authorization is unchanged — every
	 * call still goes through the ability's permission_callback.
	 */
	private const MCP_META = [
		'public' => true,
		'type'   => 'tool',
	];

	/**
	 * Register all Cookie abilities.
	 *
	 * @param object $permissions Permission manager instance.
	 * @return void
	 */
	public static function register( object $permissions ): void {
		self::register_options_abilities( $permissions );
		self::register_consent_abilities( $permissions );
		self::register_scanner_abilities( $permissions );
	}

	/**
	 * Register options read/write abilities.
	 *
	 * @param object $permissions Permission manager instance.
	 * @return void
	 */
	private static function register_options_abilities( object $permissions ): void {
		wp_register_ability(
			'lw-cookie/get-options',
			[
				'label'               => __( 'Get Cookie Options', 'lw-cookie' ),
				'description'         => __( 'Get LW Cookie consent settings (banner, categories, texts, advanced).', 'lw-cookie' ),
				'category'            => 'cookie',
				'execute_callback'    => [ CookieService::class, 'get_options' ],
				'permission_callback' => $permissions->callback( 'can_manage_options' ),
				'input_schema'        => [
					'type'    => 'object',
					'default' => [],
				],
				'output_schema'       => [
					'type'       => 'object',
					'properties' => [
						'success'       => [ 'type' => 'boolean' ],
						'options'       => [ 'type' => 'object' ],
						'writable_keys' => [ 'type' => 'array' ],
					],
				],
				'meta'                => self::readonly_meta(),
			]
		);

		wp_register_ability(
			'lw-cookie/set-options',
			[
				'label'               => __( 'Set Cookie Options', 'lw-cookie' ),
				'description'         => __( 'Update LW Cookie consent settings. Values are validated and sanitized like in the settings screen; invalid, unknown or locked keys are not saved and are listed in "rejected" with the reason.', 'lw-cookie' ),
				'category'            => 'cookie',
				'execute_callback'    => [ CookieService::class, 'set_options' ],
				'permission_callback' => $permissions->callback( 'can_manage_options' ),
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'options' ],
					'properties' => [
						'options' => [
							'type'        => 'object',
							'description' => __( 'Key-value pairs of settings to update. Only provided keys are changed; get-options lists the writable keys.', 'lw-cookie' ),
						],
					],
				],
				'output_schema'       => [
					'type'       => 'object',
					'properties' => [
						'success'  => [ 'type' => 'boolean' ],
						'message'  => [ 'type' => 'string' ],
						'updated'  => [ 'type' => 'array' ],
						'rejected' => [ 'type' => 'object' ],
						'options'  => [ 'type' => 'object' ],
					],
				],
				'meta'                => self::write_meta(),
			]
		);
	}

	/**
	 * Register consent statistics ability.
	 *
	 * @param object $permissions Permission manager instance.
	 * @return void
	 */
	private static function register_consent_abilities( object $permissions ): void {
		wp_register_ability(
			'lw-cookie/get-consent-stats',
			[
				'label'               => __( 'Get Consent Stats', 'lw-cookie' ),
				'description'         => __( 'Get consent logging statistics grouped by action type and policy version.', 'lw-cookie' ),
				'category'            => 'cookie',
				'execute_callback'    => [ CookieService::class, 'get_consent_stats' ],
				'permission_callback' => $permissions->callback( 'can_manage_options' ),
				'input_schema'        => [
					'type'       => 'object',
					'default'    => [],
					'properties' => [
						'days' => [
							'type'        => 'integer',
							'description' => __( 'Number of past days to include. Default: 30.', 'lw-cookie' ),
							'default'     => 30,
						],
					],
				],
				'output_schema'       => [
					'type'       => 'object',
					'properties' => [
						'success' => [ 'type' => 'boolean' ],
						'stats'   => [ 'type' => 'object' ],
						'total'   => [ 'type' => 'integer' ],
					],
				],
				'meta'                => self::readonly_meta(),
			]
		);
	}

	/**
	 * Register scanner ability.
	 *
	 * @param object $permissions Permission manager instance.
	 * @return void
	 */
	private static function register_scanner_abilities( object $permissions ): void {
		wp_register_ability(
			'lw-cookie/scan-cookies',
			[
				'label'               => __( 'Scan Cookies', 'lw-cookie' ),
				'description'         => __( 'Trigger an HTTP header pre-scan to detect cookies across site URLs.', 'lw-cookie' ),
				'category'            => 'cookie',
				'execute_callback'    => [ CookieService::class, 'scan_cookies' ],
				'permission_callback' => $permissions->callback( 'can_manage_options' ),
				'input_schema'        => [
					'type'    => 'object',
					'default' => [],
				],
				'output_schema'       => [
					'type'       => 'object',
					'properties' => [
						'success'    => [ 'type' => 'boolean' ],
						'cookies'    => [ 'type' => 'array' ],
						'domains'    => [ 'type' => 'array' ],
						'urls_count' => [ 'type' => 'integer' ],
					],
				],
				'meta'                => self::write_meta(),
			]
		);
	}

	/**
	 * Read-only ability metadata.
	 *
	 * @return array<string, mixed>
	 */
	private static function readonly_meta(): array {
		return [
			'show_in_rest' => true,
			'mcp'          => self::MCP_META,
			'annotations'  => [
				'readonly'    => true,
				'destructive' => false,
				'idempotent'  => true,
			],
		];
	}

	/**
	 * Write ability metadata.
	 *
	 * @return array<string, mixed>
	 */
	private static function write_meta(): array {
		return [
			'show_in_rest' => true,
			'mcp'          => self::MCP_META,
			'annotations'  => [
				'readonly'    => false,
				'destructive' => false,
				'idempotent'  => true,
			],
		];
	}
}
