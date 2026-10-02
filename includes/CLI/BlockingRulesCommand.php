<?php
/**
 * WP-CLI custom blocking rules command.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\CLI;

use LightweightPlugins\Cookie\Admin\SettingsStore;
use LightweightPlugins\Cookie\Blocking\CustomRules;
use WP_CLI;

/**
 * Manage custom blocking rules: a domain or URL pattern and the consent
 * category it needs. Rules override the built-in list; "necessary" never
 * blocks.
 *
 * ## EXAMPLES
 *
 *     wp lw-cookie blocking-rules list
 *     wp lw-cookie blocking-rules add code.tidio.co functional
 *     wp lw-cookie blocking-rules add googletagmanager.com necessary
 *     wp lw-cookie blocking-rules remove code.tidio.co
 */
final class BlockingRulesCommand {

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	public static function register(): void {
		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
			return;
		}

		WP_CLI::add_command( 'lw-cookie blocking-rules', self::class );
	}

	/**
	 * List the rules.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : table, json, csv or yaml. Default: table.
	 *
	 * @subcommand list
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Associative arguments.
	 * @return void
	 */
	public function list_( array $args, array $assoc_args ): void {
		$rules = self::rules();

		if ( [] === $rules ) {
			WP_CLI::log( 'No custom blocking rules.' );
			return;
		}

		WP_CLI\Utils\format_items( $assoc_args['format'] ?? 'table', $rules, [ 'pattern', 'category' ] );
	}

	/**
	 * Add a rule, or change the category of an existing pattern.
	 *
	 * ## OPTIONS
	 *
	 * <pattern>
	 * : Domain (matches subdomains too) or URL part with a path, e.g. code.tidio.co or example.com/pixel.js.
	 *
	 * <category>
	 * : necessary (never block), functional, analytics or marketing.
	 *
	 * @param array<int, string> $args Positional arguments.
	 * @return void
	 */
	public function add( array $args ): void {
		$pattern  = CustomRules::pattern( $args[0] ?? '' );
		$category = $args[1] ?? '';

		if ( null === $pattern ) {
			WP_CLI::error( 'Invalid pattern: use a domain, optionally with a path (e.g. code.tidio.co).' );
		}

		if ( ! in_array( $category, CustomRules::CATEGORIES, true ) ) {
			WP_CLI::error( 'Invalid category: use ' . implode( ', ', CustomRules::CATEGORIES ) . '.' );
		}

		$others = array_filter( self::rules(), static fn( array $rule ): bool => $rule['pattern'] !== $pattern );
		self::save(
			array_merge(
				array_values( $others ),
				[
					[
						'pattern'  => $pattern,
						'category' => $category,
					],
				]
			)
		);

		WP_CLI::success( sprintf( '%s → %s', $pattern, $category ) );
	}

	/**
	 * Remove a rule.
	 *
	 * ## OPTIONS
	 *
	 * <pattern>
	 * : The pattern to remove.
	 *
	 * @param array<int, string> $args Positional arguments.
	 * @return void
	 */
	public function remove( array $args ): void {
		$pattern = CustomRules::pattern( $args[0] ?? '' );
		$rules   = self::rules();
		$others  = array_values( array_filter( $rules, static fn( array $rule ): bool => $rule['pattern'] !== $pattern ) );

		if ( count( $others ) === count( $rules ) ) {
			WP_CLI::error( sprintf( 'No rule for %s.', (string) ( $args[0] ?? '' ) ) );
		}

		self::save( $others );
		WP_CLI::success( sprintf( 'Removed %s.', (string) $pattern ) );
	}

	/**
	 * Stored rules.
	 *
	 * @return array<int, array{pattern: string, category: string}>
	 */
	private static function rules(): array {
		$rules = SettingsStore::current()['custom_blocking_rules'] ?? [];

		return CustomRules::sanitize( is_array( $rules ) ? $rules : [] );
	}

	/**
	 * Store the rules through the settings sanitizer.
	 *
	 * @param array<int, array{pattern: string, category: string}> $rules Rules.
	 * @return void
	 */
	private static function save( array $rules ): void {
		SettingsStore::save( [ 'custom_blocking_rules' => $rules ] );
	}
}
