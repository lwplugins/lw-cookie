<?php
/**
 * Cookies Declaration Tab.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Admin\Settings;

use LightweightPlugins\Cookie\Options;
use LightweightPlugins\Cookie\Scanner\Scanner;

/**
 * Cookie declaration settings tab.
 */
final class TabCookies implements TabInterface {

	/**
	 * Get tab slug.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return 'cookies';
	}

	/**
	 * Get tab label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Cookies', 'lw-cookie' );
	}

	/**
	 * Get tab icon.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'dashicons-list-view';
	}

	/**
	 * Render tab content.
	 *
	 * @return void
	 */
	public function render(): void {
		$cookies = Options::get( 'declared_cookies', [] );
		if ( ! is_array( $cookies ) ) {
			$cookies = [];
		}

		$categories = [
			'necessary'  => __( 'Necessary', 'lw-cookie' ),
			'functional' => Options::get( 'cat_functional_name', __( 'Functional', 'lw-cookie' ) ),
			'analytics'  => Options::get( 'cat_analytics_name', __( 'Analytics', 'lw-cookie' ) ),
			'marketing'  => Options::get( 'cat_marketing_name', __( 'Marketing', 'lw-cookie' ) ),
		];

		$existing_names = array_column( $cookies, 'name' );
		?>
		<div class="lw-cookie-declaration-manager">
			<!-- Scanner Section -->
			<div class="lw-cookie-scanner-box">
				<div class="lw-cookie-scanner-header">
					<span class="dashicons dashicons-search"></span>
					<div>
						<h3><?php esc_html_e( 'Cookie Scanner', 'lw-cookie' ); ?></h3>
						<p><?php esc_html_e( 'Scan your website to detect cookies in use.', 'lw-cookie' ); ?></p>
					</div>
				</div>
				<button type="button" class="button button-primary button-hero" id="lw-cookie-scan-btn">
					<span class="dashicons dashicons-update"></span>
					<?php esc_html_e( 'Scan Website', 'lw-cookie' ); ?>
				</button>
			</div>

			<!-- Scanner Results Modal -->
			<div id="lw-cookie-scanner-modal" class="lw-cookie-modal" style="display: none;">
				<div class="lw-cookie-modal-content">
					<div class="lw-cookie-modal-header">
						<h2>
							<span class="dashicons dashicons-search"></span>
							<?php esc_html_e( 'Scan Results', 'lw-cookie' ); ?>
						</h2>
						<button type="button" class="lw-cookie-modal-close">&times;</button>
					</div>
					<div class="lw-cookie-modal-body">
						<div id="lw-cookie-scan-progress" class="lw-cookie-scan-progress">
							<div class="lw-cookie-scan-spinner"></div>
							<p><?php esc_html_e( 'Scanning your website for cookies...', 'lw-cookie' ); ?></p>
						</div>
						<div id="lw-cookie-scan-results" style="display: none;">
							<div class="lw-cookie-scan-summary"></div>
							<div class="lw-cookie-scan-list"></div>
						</div>
					</div>
					<div class="lw-cookie-modal-footer">
						<button type="button" class="button" id="lw-cookie-scan-close">
							<?php esc_html_e( 'Close', 'lw-cookie' ); ?>
						</button>
						<button type="button" class="button button-primary" id="lw-cookie-add-selected" style="display: none;">
							<span class="dashicons dashicons-plus-alt2"></span>
							<?php esc_html_e( 'Add Selected', 'lw-cookie' ); ?>
						</button>
					</div>
				</div>
			</div>

			<!-- Hidden iframe for scanning -->
			<iframe id="lw-cookie-scan-frame" style="display:none;"></iframe>

			<!-- Cookie Table -->
			<table class="wp-list-table widefat fixed striped" id="lw-cookie-declaration-table">
				<thead>
					<tr>
						<th style="width: 15%;"><?php esc_html_e( 'Cookie Name', 'lw-cookie' ); ?></th>
						<th style="width: 15%;"><?php esc_html_e( 'Provider', 'lw-cookie' ); ?></th>
						<th style="width: 25%;"><?php esc_html_e( 'Purpose', 'lw-cookie' ); ?></th>
						<th style="width: 10%;"><?php esc_html_e( 'Duration', 'lw-cookie' ); ?></th>
						<th style="width: 12%;"><?php esc_html_e( 'Category', 'lw-cookie' ); ?></th>
						<th style="width: 10%;"><?php esc_html_e( 'Type', 'lw-cookie' ); ?></th>
						<th style="width: 13%;"><?php esc_html_e( 'Actions', 'lw-cookie' ); ?></th>
					</tr>
				</thead>
				<tbody id="lw-cookie-rows">
					<?php
					if ( ! empty( $cookies ) ) {
						foreach ( $cookies as $index => $cookie ) {
							$this->render_cookie_row( $index, $cookie, $categories );
						}
					}
					?>
				</tbody>
				<tfoot>
					<tr>
						<td colspan="7">
							<button type="button" class="button button-secondary" id="lw-cookie-add-row">
								<?php esc_html_e( '+ Add Cookie', 'lw-cookie' ); ?>
							</button>
							<button type="button" class="button button-secondary" id="lw-cookie-add-common">
								<?php esc_html_e( '+ Add Common Cookies', 'lw-cookie' ); ?>
							</button>
						</td>
					</tr>
				</tfoot>
			</table>

			<template id="lw-cookie-row-template">
				<?php $this->render_cookie_row( '{{INDEX}}', [], $categories ); ?>
			</template>

			<p class="description" style="margin-top: 15px;">
				<?php esc_html_e( 'Use the shortcode [lw_cookie_declaration] to display this cookie list on any page.', 'lw-cookie' ); ?>
			</p>
		</div>

		<?php $this->render_scanner_styles(); ?>
		<?php $this->render_scanner_script( $existing_names ); ?>
		<?php
	}

	/**
	 * Render scanner styles.
	 *
	 * @return void
	 */
	private function render_scanner_styles(): void {
		?>
		<style>
		/* Scanner Box - WordPress native style */
		.lw-cookie-scanner-box {
			background: #fff;
			border: 1px solid #c3c4c7;
			padding: 20px;
			margin-bottom: 20px;
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 20px;
		}
		.lw-cookie-scanner-header {
			display: flex;
			align-items: center;
			gap: 12px;
		}
		.lw-cookie-scanner-header .dashicons {
			font-size: 32px;
			width: 32px;
			height: 32px;
			color: #2271b1;
		}
		.lw-cookie-scanner-header h3 {
			margin: 0 0 4px 0;
			font-size: 14px;
			font-weight: 600;
			color: #1d2327;
		}
		.lw-cookie-scanner-header p {
			margin: 0;
			color: #646970;
			font-size: 13px;
		}
		#lw-cookie-scan-btn .dashicons {
			font-size: 16px;
			width: 16px;
			height: 16px;
			margin-right: 4px;
			vertical-align: text-bottom;
		}
		#lw-cookie-scan-btn.scanning .dashicons {
			animation: lw-spin 1s linear infinite;
		}
		@keyframes lw-spin {
			100% { transform: rotate(360deg); }
		}

		/* Modal - WordPress native style */
		.lw-cookie-modal {
			position: fixed;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background: rgba(0,0,0,0.5);
			z-index: 100000;
			display: flex;
			align-items: center;
			justify-content: center;
		}
		.lw-cookie-modal-content {
			background: #fff;
			border: 1px solid #c3c4c7;
			box-shadow: 0 3px 6px rgba(0,0,0,0.15);
			width: 90%;
			max-width: 700px;
			max-height: 80vh;
			display: flex;
			flex-direction: column;
		}
		.lw-cookie-modal-header {
			padding: 15px 20px;
			border-bottom: 1px solid #c3c4c7;
			background: #f6f7f7;
			display: flex;
			align-items: center;
			justify-content: space-between;
		}
		.lw-cookie-modal-header h2 {
			margin: 0;
			font-size: 14px;
			font-weight: 600;
			display: flex;
			align-items: center;
			gap: 8px;
		}
		.lw-cookie-modal-header .dashicons {
			color: #2271b1;
		}
		.lw-cookie-modal-close {
			background: none;
			border: none;
			font-size: 24px;
			cursor: pointer;
			color: #646970;
			padding: 0;
			line-height: 1;
		}
		.lw-cookie-modal-close:hover { color: #1d2327; }
		.lw-cookie-modal-body {
			padding: 20px;
			overflow-y: auto;
			flex: 1;
		}
		.lw-cookie-modal-footer {
			padding: 15px 20px;
			border-top: 1px solid #c3c4c7;
			background: #f6f7f7;
			display: flex;
			justify-content: flex-end;
			gap: 10px;
		}
		.lw-cookie-modal-footer .button-primary .dashicons {
			font-size: 14px;
			width: 14px;
			height: 14px;
			margin-right: 4px;
			vertical-align: text-bottom;
		}

		/* Progress */
		.lw-cookie-scan-progress {
			text-align: center;
			padding: 40px 20px;
		}
		.lw-cookie-scan-spinner {
			width: 40px;
			height: 40px;
			border: 3px solid #c3c4c7;
			border-top-color: #2271b1;
			border-radius: 50%;
			margin: 0 auto 15px;
			animation: lw-spin 0.8s linear infinite;
		}
		.lw-cookie-scan-progress p {
			color: #646970;
			font-size: 13px;
		}

		/* Results - WordPress notice style */
		.lw-cookie-scan-summary {
			background: #fff;
			border: 1px solid #c3c4c7;
			border-left-width: 4px;
			border-left-color: #00a32a;
			padding: 12px 15px;
			margin-bottom: 15px;
			display: flex;
			align-items: center;
			gap: 10px;
		}
		.lw-cookie-scan-summary.warning {
			border-left-color: #dba617;
		}
		.lw-cookie-scan-summary .dashicons {
			font-size: 20px;
			width: 20px;
			height: 20px;
			color: #00a32a;
		}
		.lw-cookie-scan-summary.warning .dashicons { color: #dba617; }
		.lw-cookie-scan-summary strong { font-size: 13px; }

		/* Cookie List - WordPress table row style */
		.lw-cookie-scan-item {
			border: 1px solid #c3c4c7;
			padding: 12px 15px;
			margin-bottom: 8px;
			display: flex;
			align-items: flex-start;
			gap: 10px;
			background: #fff;
		}
		.lw-cookie-scan-item:hover {
			background: #f6f7f7;
		}
		.lw-cookie-scan-item.already-added {
			background: #f6f7f7;
			opacity: 0.6;
		}
		.lw-cookie-scan-item input[type="checkbox"] {
			margin-top: 2px;
		}
		.lw-cookie-scan-item-content { flex: 1; }
		.lw-cookie-scan-item-name {
			font-weight: 600;
			font-family: Consolas, Monaco, monospace;
			font-size: 13px;
			color: #1d2327;
			background: #f0f0f1;
			padding: 2px 6px;
			display: inline-block;
		}
		.lw-cookie-scan-item-meta {
			margin-top: 4px;
			font-size: 12px;
			color: #646970;
		}
		.lw-cookie-scan-item-category {
			display: inline-block;
			font-size: 10px;
			font-weight: 600;
			text-transform: uppercase;
			padding: 2px 6px;
			margin-left: 6px;
			background: #f0f0f1;
			color: #646970;
		}
		.lw-cookie-scan-item-category.necessary { background: #d5e5f2; color: #135e96; }
		.lw-cookie-scan-item-category.functional { background: #f0e6f4; color: #6b3276; }
		.lw-cookie-scan-item-category.analytics { background: #fcf0c8; color: #826200; }
		.lw-cookie-scan-item-category.marketing { background: #facfd2; color: #a02222; }
		.lw-cookie-scan-item-category.unknown { background: #f0f0f1; color: #646970; }
		.lw-cookie-scan-item-badge {
			font-size: 11px;
			color: #00a32a;
			font-weight: 500;
		}
		.lw-cookie-scan-item-source {
			display: inline-block;
			font-size: 9px;
			font-weight: 600;
			text-transform: uppercase;
			padding: 2px 5px;
			margin-left: 5px;
			background: #d5e5f2;
			color: #135e96;
		}
		</style>
		<?php
	}

	/**
	 * Render scanner script.
	 *
	 * @param array $existing_names Existing cookie names.
	 * @return void
	 */
	private function render_scanner_script( array $existing_names ): void {
		$scan_urls = Scanner::get_scan_urls();
		$rest_url  = rest_url( 'lw-cookie/v1/' );
		$nonce     = wp_create_nonce( 'wp_rest' );
		?>
		<script>
		jQuery(document).ready(function($) {
			var rowIndex = <?php echo count( Options::get( 'declared_cookies', [] ) ); ?>;
			var existingCookies = <?php echo wp_json_encode( $existing_names ); ?>;
			var scanUrls = <?php echo wp_json_encode( $scan_urls ); ?>;
			var restUrl = <?php echo wp_json_encode( $rest_url ); ?>;
			var restNonce = <?php echo wp_json_encode( $nonce ); ?>;

			// Add new row.
			$('#lw-cookie-add-row').on('click', function() {
				var template = $('#lw-cookie-row-template').html();
				template = template.replace(/\{\{INDEX\}\}/g, rowIndex);
				$('#lw-cookie-rows').append(template);
				rowIndex++;
			});

			// Remove row.
			$(document).on('click', '.lw-cookie-remove-row', function() {
				$(this).closest('tr').remove();
			});

			// Add common cookies.
			$('#lw-cookie-add-common').on('click', function() {
				var commonCookies = [
					{ name: 'lw_cookie_consent', provider: '<?php echo esc_js( get_bloginfo( 'name' ) ); ?>', purpose: '<?php echo esc_js( __( 'Stores cookie consent preferences', 'lw-cookie' ) ); ?>', duration: '<?php echo esc_js( __( '1 year', 'lw-cookie' ) ); ?>', category: 'necessary', type: 'persistent' },
					{ name: 'wordpress_sec_*', provider: 'WordPress', purpose: '<?php echo esc_js( __( 'Authentication cookie for logged-in users', 'lw-cookie' ) ); ?>', duration: '<?php echo esc_js( __( 'Session', 'lw-cookie' ) ); ?>', category: 'necessary', type: 'session' },
					{ name: 'wordpress_logged_in_*', provider: 'WordPress', purpose: '<?php echo esc_js( __( 'Indicates when user is logged in', 'lw-cookie' ) ); ?>', duration: '<?php echo esc_js( __( 'Session', 'lw-cookie' ) ); ?>', category: 'necessary', type: 'session' }
				];
				addCookiesToTable(commonCookies);
			});

			// Scanner - loads pages in iframe, PHP collects cookies, then fetches enriched results via API.
			var scanIndex = 0;
			var iframe = document.getElementById('lw-cookie-scan-frame');

			$('#lw-cookie-scan-btn').on('click', function() {
				var $btn = $(this);
				$btn.addClass('scanning');
				$('#lw-cookie-scanner-modal').fadeIn(200);
				$('#lw-cookie-scan-progress').show().find('p').text('<?php echo esc_js( __( 'Scanning your website for cookies...', 'lw-cookie' ) ); ?>');
				$('#lw-cookie-scan-results').hide();
				$('#lw-cookie-add-selected').hide();

				// Clear previous scan results, then prescan HTTP headers, then scan pages.
				$.post(restUrl + 'clear-scan', {}, function() {
					// Pre-scan HTTP headers for Set-Cookie (catches cookies before JS runs).
					$.ajax({
						url: restUrl + 'prescan-headers',
						method: 'POST',
						headers: { 'X-WP-Nonce': restNonce },
						complete: function() {
							scanIndex = 0;
							scanNextUrl();
						}
					});
				}).fail(function() {
					scanIndex = 0;
					scanNextUrl();
				});
			});

			// Listen for networkidle message from iframe.
			window.addEventListener('message', function(e) {
				if (e.data === 'networkidle0') {
					scanIndex++;
					scanNextUrl();
				}
			});

			function scanNextUrl() {
				if (scanIndex < scanUrls.length) {
					iframe.src = scanUrls[scanIndex];
					// Fallback timeout if networkidle doesn't fire.
					setTimeout(function() {
						if (scanIndex < scanUrls.length) {
							scanIndex++;
							scanNextUrl();
						}
					}, 10000);
				} else {
					// All URLs scanned, fetch enriched results from API.
					fetchScanResults();
				}
			}

			function fetchScanResults() {
				// Update progress text for deep scan phase.
				$('#lw-cookie-scan-progress').find('p').text('<?php echo esc_js( __( 'Deep scanning for additional cookies...', 'lw-cookie' ) ); ?>');

				// Run remote scan to catch additional cookies.
				$.ajax({
					url: restUrl + 'remote-scan',
					method: 'POST',
					headers: { 'X-WP-Nonce': restNonce },
					timeout: 120000,
					complete: function() {
						// Fetch combined results (local + remote merged on server).
						$.ajax({
							url: restUrl + 'scan-results',
							method: 'GET',
							headers: { 'X-WP-Nonce': restNonce },
							success: function(response) {
								$('#lw-cookie-scan-btn').removeClass('scanning');
								if (response.success) {
									displayScanResults(response.cookies || [], response.domains || [], response.fonts || []);
								} else {
									displayScanResults([], [], []);
								}
							},
							error: function() {
								$('#lw-cookie-scan-btn').removeClass('scanning');
								displayScanResults([], [], []);
							}
						});
					}
				});
			}

			function displayScanResults(cookies, domains, fonts) {
				$('#lw-cookie-scan-progress').hide();
				$('#lw-cookie-scan-results').show();

				var $summary = $('.lw-cookie-scan-summary');
				var $list = $('.lw-cookie-scan-list');
				$list.empty();

				var newCookies = cookies.filter(function(c) { return !c.is_declared; });
				var totalItems = cookies.length + domains.length + fonts.length;

				if (totalItems === 0) {
					$summary.removeClass('warning').html('<span class="dashicons dashicons-info"></span><strong><?php echo esc_js( __( 'No cookies detected. Try visiting your site first to set some cookies.', 'lw-cookie' ) ); ?></strong>');
				} else if (newCookies.length === 0 && domains.length === 0 && fonts.length === 0) {
					$summary.removeClass('warning').html('<span class="dashicons dashicons-yes-alt"></span><strong><?php echo esc_js( __( 'All detected cookies are already in your list!', 'lw-cookie' ) ); ?></strong>');
				} else {
					var msg = '';
					if (newCookies.length > 0) msg += newCookies.length + ' <?php echo esc_js( __( 'new cookie(s)', 'lw-cookie' ) ); ?>';
					if (domains.length > 0) msg += (msg ? ', ' : '') + domains.length + ' <?php echo esc_js( __( 'external domain(s)', 'lw-cookie' ) ); ?>';
					if (fonts.length > 0) msg += (msg ? ', ' : '') + fonts.length + ' <?php echo esc_js( __( 'external font(s)', 'lw-cookie' ) ); ?>';
					$summary.addClass('warning').html('<span class="dashicons dashicons-warning"></span><strong>' + msg + ' <?php echo esc_js( __( 'found.', 'lw-cookie' ) ); ?></strong>');
					if (newCookies.length > 0) $('#lw-cookie-add-selected').show();
				}

				// Display cookies.
				if (cookies.length > 0) {
					$list.append('<h4 style="margin:15px 0 10px;color:#1d2327;"><?php echo esc_js( __( 'Cookies', 'lw-cookie' ) ); ?> (' + cookies.length + ')</h4>');
					cookies.forEach(function(cookie) {
						var checked = !cookie.is_declared ? 'checked' : '';
						var disabledClass = cookie.is_declared ? 'already-added' : '';
						var badge = cookie.is_declared ? '<span class="lw-cookie-scan-item-badge">✓ <?php echo esc_js( __( 'Already added', 'lw-cookie' ) ); ?></span>' : '';
						var sourceTag = cookie.source === 'api' ? '<span class="lw-cookie-scan-item-source">API</span>' : '';
						var categoryClass = cookie.category || 'unknown';

						$list.append(
							'<div class="lw-cookie-scan-item ' + disabledClass + '" data-cookie=\'' + JSON.stringify(cookie).replace(/'/g, '&#39;') + '\'>' +
							'<input type="checkbox" ' + checked + ' ' + (cookie.is_declared ? 'disabled' : '') + '>' +
							'<div class="lw-cookie-scan-item-content">' +
							'<span class="lw-cookie-scan-item-name">' + escapeHtml(cookie.original_name || cookie.name) + '</span>' +
							'<span class="lw-cookie-scan-item-category ' + categoryClass + '">' + escapeHtml(cookie.category || '<?php echo esc_js( __( 'Unknown', 'lw-cookie' ) ); ?>') + '</span>' +
							sourceTag + badge +
							'<div class="lw-cookie-scan-item-meta">' + escapeHtml(cookie.provider || '<?php echo esc_js( __( 'Unknown provider', 'lw-cookie' ) ); ?>') + ' — ' + escapeHtml(cookie.purpose || '<?php echo esc_js( __( 'Purpose not specified', 'lw-cookie' ) ); ?>') + '</div>' +
							'</div>' +
							'</div>'
						);
					});
				}

				// Display external domains.
				if (domains.length > 0) {
					$list.append('<h4 style="margin:15px 0 10px;color:#1d2327;"><?php echo esc_js( __( 'External Domains', 'lw-cookie' ) ); ?> (' + domains.length + ')</h4>');
					domains.forEach(function(domain) {
						$list.append(
							'<div class="lw-cookie-scan-item lw-cookie-scan-domain">' +
							'<div class="lw-cookie-scan-item-content">' +
							'<span class="lw-cookie-scan-item-name">' + escapeHtml(domain) + '</span>' +
							'<span class="lw-cookie-scan-item-category functional"><?php echo esc_js( __( 'External', 'lw-cookie' ) ); ?></span>' +
							'</div>' +
							'</div>'
						);
					});
				}

				// Display external fonts.
				if (fonts.length > 0) {
					$list.append('<h4 style="margin:15px 0 10px;color:#1d2327;"><?php echo esc_js( __( 'External Fonts', 'lw-cookie' ) ); ?> (' + fonts.length + ')</h4>');
					fonts.forEach(function(font) {
						var parts = font.split('|');
						var family = parts[0] || font;
						var host = parts[1] || '';
						$list.append(
							'<div class="lw-cookie-scan-item lw-cookie-scan-font">' +
							'<div class="lw-cookie-scan-item-content">' +
							'<span class="lw-cookie-scan-item-name">' + escapeHtml(family) + '</span>' +
							'<span class="lw-cookie-scan-item-category functional"><?php echo esc_js( __( 'Font', 'lw-cookie' ) ); ?></span>' +
							'<div class="lw-cookie-scan-item-meta">' + escapeHtml(host) + '</div>' +
							'</div>' +
							'</div>'
						);
					});
				}
			}

			function escapeHtml(text) {
				if (!text) return '';
				var div = document.createElement('div');
				div.textContent = text;
				return div.innerHTML;
			}

			// Add selected cookies.
			$('#lw-cookie-add-selected').on('click', function() {
				var cookiesToAdd = [];
				$('.lw-cookie-scan-item:not(.already-added) input:checked').each(function() {
					var data = $(this).closest('.lw-cookie-scan-item').data('cookie');
					if (data) cookiesToAdd.push(data);
				});

				if (cookiesToAdd.length > 0) {
					addCookiesToTable(cookiesToAdd);
					closeModal();
				}
			});

			function addCookiesToTable(cookies) {
				cookies.forEach(function(cookie) {
					var template = $('#lw-cookie-row-template').html();
					template = template.replace(/\{\{INDEX\}\}/g, rowIndex);
					var $row = $(template);

					$row.find('[name*="[name]"]').val(cookie.name || cookie.original_name || '');
					$row.find('[name*="[provider]"]').val(cookie.provider || '');
					$row.find('[name*="[purpose]"]').val(cookie.purpose || '');
					$row.find('[name*="[duration]"]').val(cookie.duration || '');
					$row.find('[name*="[category]"]').val(cookie.category || 'necessary');
					$row.find('[name*="[type]"]').val(cookie.type || 'persistent');

					$('#lw-cookie-rows').append($row);
					existingCookies.push(cookie.name || cookie.original_name);
					rowIndex++;
				});
			}

			// Close modal.
			function closeModal() {
				$('#lw-cookie-scanner-modal').fadeOut(200);
				iframe.src = '';
			}

			$('.lw-cookie-modal-close, #lw-cookie-scan-close').on('click', closeModal);
			$('#lw-cookie-scanner-modal').on('click', function(e) {
				if (e.target === this) closeModal();
			});
		});
		</script>
		<?php
	}

	/**
	 * Render a single cookie row.
	 *
	 * @param int|string $index      Row index.
	 * @param array      $cookie     Cookie data.
	 * @param array      $categories Available categories.
	 * @return void
	 */
	private function render_cookie_row( $index, array $cookie, array $categories ): void {
		$name   = esc_attr( Options::OPTION_NAME );
		$cookie = wp_parse_args(
			$cookie,
			[
				'name'     => '',
				'provider' => '',
				'purpose'  => '',
				'duration' => '',
				'category' => 'necessary',
				'type'     => 'persistent',
			]
		);
		?>
		<tr>
			<td>
				<input type="text"
					name="<?php echo esc_attr( $name ); ?>[declared_cookies][<?php echo esc_attr( $index ); ?>][name]"
					value="<?php echo esc_attr( $cookie['name'] ); ?>"
					placeholder="<?php esc_attr_e( 'e.g. _ga', 'lw-cookie' ); ?>"
					class="widefat" />
			</td>
			<td>
				<input type="text"
					name="<?php echo esc_attr( $name ); ?>[declared_cookies][<?php echo esc_attr( $index ); ?>][provider]"
					value="<?php echo esc_attr( $cookie['provider'] ); ?>"
					placeholder="<?php esc_attr_e( 'e.g. Google', 'lw-cookie' ); ?>"
					class="widefat" />
			</td>
			<td>
				<input type="text"
					name="<?php echo esc_attr( $name ); ?>[declared_cookies][<?php echo esc_attr( $index ); ?>][purpose]"
					value="<?php echo esc_attr( $cookie['purpose'] ); ?>"
					placeholder="<?php esc_attr_e( 'Purpose description', 'lw-cookie' ); ?>"
					class="widefat" />
			</td>
			<td>
				<input type="text"
					name="<?php echo esc_attr( $name ); ?>[declared_cookies][<?php echo esc_attr( $index ); ?>][duration]"
					value="<?php echo esc_attr( $cookie['duration'] ); ?>"
					placeholder="<?php esc_attr_e( 'e.g. 1 year', 'lw-cookie' ); ?>"
					class="widefat" />
			</td>
			<td>
				<select name="<?php echo esc_attr( $name ); ?>[declared_cookies][<?php echo esc_attr( $index ); ?>][category]" class="widefat">
					<?php foreach ( $categories as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $cookie['category'], $key ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</td>
			<td>
				<select name="<?php echo esc_attr( $name ); ?>[declared_cookies][<?php echo esc_attr( $index ); ?>][type]" class="widefat">
					<option value="session" <?php selected( $cookie['type'], 'session' ); ?>>
						<?php esc_html_e( 'Session', 'lw-cookie' ); ?>
					</option>
					<option value="persistent" <?php selected( $cookie['type'], 'persistent' ); ?>>
						<?php esc_html_e( 'Persistent', 'lw-cookie' ); ?>
					</option>
				</select>
			</td>
			<td>
				<button type="button" class="button button-link-delete lw-cookie-remove-row">
					<?php esc_html_e( 'Remove', 'lw-cookie' ); ?>
				</button>
			</td>
		</tr>
		<?php
	}
}
