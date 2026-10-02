<?php
/**
 * Complianz → LW Cookie cookie declaration mapper.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Migration\Complianz;

/**
 * Turns Complianz cookie rows into declared_cookies rows.
 *
 * Complianz keeps an English parent row per cookie plus one translated row
 * per language (isTranslationFrom = parent ID). The site-language row wins
 * for the visible texts; the category always comes from the English parent,
 * because its purpose is the untranslated cookiedatabase.org label.
 */
final class CookieMapper {

	/**
	 * Map the rows. A cookie with neither a purpose nor a service category
	 * gets an empty category: guessing one would misdeclare it.
	 *
	 * @param array<int, array<string, mixed>> $cookies  Cookie rows.
	 * @param array<int, array<string, mixed>> $services Service rows keyed by ID.
	 * @param string                           $language Two-letter site language.
	 * @return array<int, array{name: string, provider: string, purpose: string, duration: string, category: string, type: string}> Category '' when unknown.
	 */
	public static function map( array $cookies, array $services, string $language ): array {
		$mapped = [];

		foreach ( self::group( $cookies ) as $group ) {
			$parent = $group['parent'];
			$row    = array_merge( $parent, array_filter( $group['translations'][ $language ] ?? [], [ self::class, 'filled' ] ) );
			$name   = trim( (string) ( $row['name'] ?? '' ) );

			if ( '' === $name || isset( $mapped[ $name ] ) ) {
				continue;
			}

			$service  = $services[ (int) ( $row['serviceID'] ?? 0 ) ] ?? $services[ (int) ( $parent['serviceID'] ?? 0 ) ] ?? [];
			$category = (string) ( $parent['purpose'] ?? '' );
			$duration = trim( (string) ( $row['retention'] ?? '' ) );

			$mapped[ $name ] = [
				'name'     => $name,
				'provider' => trim( (string) ( $service['name'] ?? '' ) ),
				'purpose'  => trim( (string) ( $row['cookieFunction'] ?? '' ) ),
				'duration' => $duration,
				'category' => self::category( '' !== $category ? $category : (string) ( $service['category'] ?? '' ) ),
				'type'     => false !== stripos( $duration, 'session' ) ? 'session' : 'persistent',
			];
		}

		return array_values( $mapped );
	}

	/**
	 * LW Cookie category of a Complianz purpose/service category label.
	 * Complianz "Functional" is the always-on (necessary) category; no
	 * label means unknown ('').
	 *
	 * @param string $label Complianz label, e.g. "Marketing/Tracking".
	 * @return string
	 */
	public static function category( string $label ): string {
		$label = strtolower( trim( $label ) );

		if ( '' === $label ) {
			return '';
		}

		foreach (
			[
				'marketing'  => [ 'marketing', 'tracking', 'advertis' ],
				'analytics'  => [ 'statistic', 'analytic' ],
				'functional' => [ 'preference' ],
			] as $category => $needles
		) {
			foreach ( $needles as $needle ) {
				if ( str_contains( $label, $needle ) ) {
					return $category;
				}
			}
		}

		return 'necessary';
	}

	/**
	 * Group rows by parent: [ parent row, translations by language ].
	 *
	 * @param array<int, array<string, mixed>> $cookies Cookie rows.
	 * @return array<int, array{parent: array<string, mixed>, translations: array<string, array<string, mixed>>}>
	 */
	private static function group( array $cookies ): array {
		$groups = [];

		foreach ( $cookies as $row ) {
			$from = (int) ( $row['isTranslationFrom'] ?? 0 );
			if ( $from > 0 ) {
				$groups[ $from ]['translations'][ (string) ( $row['language'] ?? '' ) ] = $row;
			} else {
				$groups[ (int) ( $row['ID'] ?? 0 ) ]['parent'] = $row;
			}
		}

		$complete = [];
		foreach ( $groups as $group ) {
			if ( isset( $group['parent'] ) ) {
				$complete[] = [
					'parent'       => $group['parent'],
					'translations' => $group['translations'] ?? [],
				];
			}
		}

		return $complete;
	}

	/**
	 * Whether a translated value should override the parent's.
	 *
	 * @param mixed $value Column value.
	 * @return bool
	 */
	private static function filled( mixed $value ): bool {
		return is_scalar( $value ) && '' !== trim( (string) $value );
	}
}
