<?php
/**
 * Inline fallback script for cache plugin compatibility.
 *
 * Outputs a minimal inline <script> that handles banner
 * interactions when consent.js is delayed by cache plugins
 * (LiteSpeed Cache, WP Rocket delay, etc.).
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Banner;

/**
 * Inline click handler fallback for cache-delayed consent.js.
 */
final class InlineFallback {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_footer', [ $this, 'output' ], 101 );
	}

	/**
	 * Output minimal inline script after banner HTML.
	 *
	 * Uses event delegation and defers to consent.js when loaded.
	 * Attributes prevent cache plugins from delaying this script.
	 *
	 * @return void
	 */
	public function output(): void {
		if ( is_admin() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted inline JS, no user input.
		echo '<script data-no-optimize="1" data-no-defer="1" data-no-lazy="1" data-cfasync="false" id="lw-cookie-fallback">' . $this->get_script() . '</script>';
	}

	/**
	 * Get the fallback JavaScript source.
	 *
	 * @return string
	 */
	private function get_script(): string {
		// @codingStandardsIgnoreStart
		return <<<'JS'
(function(){
document.addEventListener('click',function(e){
if(window.LWCookie)return;
var g=window.__lwGuardCfg||{},cn=g.cookieName||'lw_cookie_consent',
pv=g.policyVersion||'1.0',bn=document.getElementById('lw-cookie-notice'),
md=document.getElementById('lw-cookie-preferences');
function sv(cats){
var d=JSON.stringify({id:Math.random().toString(36).substr(2,9),version:pv,
timestamp:Math.floor(Date.now()/1000),categories:cats});
var ex=new Date();ex.setDate(ex.getDate()+365);
document.cookie=cn+'='+btoa(d)+';expires='+ex.toUTCString()+';path=/;SameSite=Lax';
if(bn)bn.classList.add('lw-cookie-hidden');
if(md)md.style.display='none';
if(window.__lwGuard)window.__lwGuard.refresh(cats);
setTimeout(function(){location.reload()},100);
}
if(e.target.closest('[data-lw-cookie-accept]')){
sv({necessary:true,functional:true,analytics:true,marketing:true});
}else if(e.target.closest('[data-lw-cookie-reject]')){
sv({necessary:true,functional:false,analytics:false,marketing:false});
}else if(e.target.closest('[data-lw-cookie-save]')){
var cats={necessary:true};
['functional','analytics','marketing'].forEach(function(k){
var cb=document.querySelector('[data-category="'+k+'"]');
cats[k]=cb?cb.checked:false;
});sv(cats);
}else if(e.target.closest('[data-lw-cookie-customize],[data-lw-cookie-open-preferences]')){
if(bn)bn.classList.add('lw-cookie-hidden');
if(md){md.style.display='flex';md.classList.remove('lw-cookie-hidden');}
}else if(e.target.closest('[data-lw-cookie-close-modal]')){
if(md){md.style.display='none';md.classList.add('lw-cookie-hidden');}
if(bn)bn.classList.remove('lw-cookie-hidden');
}
});
})();
JS;
		// @codingStandardsIgnoreEnd
	}
}
