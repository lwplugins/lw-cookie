<?php
/**
 * Inline notice for settings tabs when a multilingual plugin is active.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Admin\Settings;

use LightweightPlugins\Cookie\I18n\MultilingualDetector;

/**
 * Renders a warning + "manage translations" CTA on tabs that hold
 * translatable user-facing strings (Texts, Categories).
 */
final class MultilingualNotice {

	/**
	 * Output the notice, or nothing when no multilingual plugin is active.
	 *
	 * @return void
	 */
	public static function render(): void {
		$active = MultilingualDetector::active();
		if ( null === $active ) {
			return;
		}

		?>
		<div class="lw-cookie-mlang-notice notice notice-warning inline">
			<p>
				<strong>
					<?php
					printf(
						/* translators: %s: multilingual plugin name */
						esc_html__( '%s detected — fields below are locked.', 'lw-cookie' ),
						esc_html( $active['name'] )
					);
					?>
				</strong>
			</p>
			<p>
				<?php esc_html_e( 'These fields hold the source-language strings only. Their values will not take effect on the frontend — translations are delivered by your multilingual plugin. Editing the source text here can invalidate existing translations.', 'lw-cookie' ); ?>
			</p>
			<p>
				<a href="<?php echo esc_url( $active['url'] ); ?>" class="button button-primary" target="_blank" rel="noopener">
					<?php
					printf(
						/* translators: %s: multilingual plugin name */
						esc_html__( 'Manage translations in %s', 'lw-cookie' ),
						esc_html( $active['name'] )
					);
					?>
				</a>
				<button type="button" class="button button-link lw-cookie-unlock-source">
					<?php esc_html_e( 'Unlock to edit source text', 'lw-cookie' ); ?>
				</button>
			</p>
		</div>
		<?php
	}

	/**
	 * Open a fieldset that locks the enclosed form fields if multilingual is active.
	 *
	 * @return void
	 */
	public static function open_lock(): void {
		$active = MultilingualDetector::active();

		if ( null === $active ) {
			echo '<fieldset class="lw-cookie-mlang-fieldset">';
			return;
		}

		echo '<fieldset class="lw-cookie-mlang-locked" disabled>';
		self::render_lock_reason( $active );
	}

	/**
	 * Close the lock fieldset.
	 *
	 * @return void
	 */
	public static function close_lock(): void {
		echo '</fieldset>';
	}

	/**
	 * Render the inline lock-reason banner shown inside the disabled fieldset.
	 *
	 * @param array{slug: string, name: string, url: string} $active Active multilingual plugin info.
	 * @return void
	 */
	private static function render_lock_reason( array $active ): void {
		?>
		<div class="lw-cookie-mlang-lock-reason" role="status">
			<span class="dashicons dashicons-lock" aria-hidden="true"></span>
			<span class="lw-cookie-mlang-lock-text">
				<strong>
					<?php
					printf(
						/* translators: %s: multilingual plugin name */
						esc_html__( 'Locked by %s.', 'lw-cookie' ),
						esc_html( $active['name'] )
					);
					?>
				</strong>
				<?php
				printf(
					/* translators: %s: multilingual plugin name */
					esc_html__( 'The values below are the source strings only; translations are served by %s on the frontend. Saving this form will not change them while the plugin is active.', 'lw-cookie' ),
					esc_html( $active['name'] )
				);
				?>
			</span>
		</div>
		<?php
	}
}
