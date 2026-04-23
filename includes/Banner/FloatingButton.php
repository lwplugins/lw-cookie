<?php
/**
 * Floating reopen button.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Banner;

use LightweightPlugins\Cookie\Options;

/**
 * Renders the floating "cookie settings" button.
 *
 * Always output with the hidden class; guard.js shows it only
 * after consent has been given.
 */
final class FloatingButton {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_footer', [ $this, 'render' ], 100 );
	}

	/**
	 * Render the floating button.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! Options::get( 'show_floating_button' ) ) {
			return;
		}

		$position = $this->get_effective_position();
		?>
		<button type="button"
			id="lw-cookie-floating-btn"
			class="lw-cookie-floating-btn lw-cookie-floating-<?php echo esc_attr( $position ); ?> lw-cookie-hidden"
			aria-label="<?php esc_attr_e( 'Cookie Settings', 'lw-cookie' ); ?>"
			data-lw-cookie-open-preferences>
			<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 2s3 2 7 2c0 6-3 11-7 14C6 15 3 10 3 4c4 0 7-2 7-2z"/></svg>
		</button>
		<?php
	}

	/**
	 * Resolve the effective floating button position.
	 *
	 * With the Floating Box banner layout the button mirrors the banner's
	 * alignment so both sit on the same side. With the Full-width Bar
	 * layout the user-configured `floating_button_pos` wins.
	 *
	 * @return string Position slug ("bottom-left" or "bottom-right").
	 */
	private function get_effective_position(): string {
		if ( 'box' === Options::get( 'banner_layout' ) ) {
			$alignment = 'left' === Options::get( 'banner_box_alignment' ) ? 'left' : 'right';
			return 'bottom-' . $alignment;
		}

		return (string) Options::get( 'floating_button_pos' );
	}
}
