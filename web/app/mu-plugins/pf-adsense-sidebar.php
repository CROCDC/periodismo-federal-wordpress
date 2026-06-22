<?php
/**
 * Plugin Name: Periodismo Federal — Sidebar AdSense slot
 * Description: Injects a single AdSense display unit as the FIRST widget of the main sidebar (sidebar-1), i.e. the XPath aside#secondary > section[1]. Code-managed (mu-plugin) so the placement is reproducible from Git — it does NOT live in the database like an Advanced Ads placement.
 * Version: 1.0.0
 *
 * Why a mu-plugin and not Advanced Ads: the ad POSITION is kept in version control
 * here. This file owns the whole AdSense wiring for the slot — it prints the loader
 * (adsbygoogle.js) in <head> AND the <ins> unit in the sidebar. Site Kit's own
 * AdSense snippet was intentionally disabled (it was pinned to a stale hosted
 * account, ca-pub-9137871268664469); if you ever re-enable Site Kit's snippet for
 * THIS account, define PF_ADSENSE_SIDEBAR_SKIP_LOADER to avoid loading the script
 * twice.
 *
 * AMP note: on AMP requests a plain <ins>/<script> unit is invalid, and the AMP
 * plugins on this site render their own layout (not the theme's sidebar.php), so
 * this slot bails out on AMP. AMP monetization is configured separately.
 *
 * Fill in the two placeholders below once AdSense approves the account and you
 * create the display ad unit. Until then the slot stays inert in production and
 * shows only a dev-preview box on local/staging.
 *
 * @package pf_adsense_sidebar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

// AdSense publisher ID (ca-pub-XXXXXXXXXXXXXXXX). From AdSense → Account → Settings.
if ( ! defined( 'PF_ADSENSE_SIDEBAR_CLIENT' ) ) {
	define( 'PF_ADSENSE_SIDEBAR_CLIENT', 'ca-pub-1978971118006130' );
}

// Ad unit slot ID (data-ad-slot, a numeric string). Create a responsive Display ad
// in AdSense → Ads → By ad unit, then paste its slot ID here.
if ( ! defined( 'PF_ADSENSE_SIDEBAR_SLOT' ) ) {
	define( 'PF_ADSENSE_SIDEBAR_SLOT', '7326770339' );
}

// The registered sidebar this ad is injected into, and the position is "first".
if ( ! defined( 'PF_ADSENSE_SIDEBAR_TARGET' ) ) {
	define( 'PF_ADSENSE_SIDEBAR_TARGET', 'sidebar-1' );
}

/**
 * Whether the real AdSense unit is fully configured (both IDs filled in).
 *
 * @return bool
 */
function pf_adsense_sidebar_is_configured() {
	$client = (string) PF_ADSENSE_SIDEBAR_CLIENT;
	$slot   = (string) PF_ADSENSE_SIDEBAR_SLOT;
	if ( '' === $client || false !== strpos( $client, 'X' ) ) {
		return false;
	}
	if ( '' === $slot || false !== strpos( $slot, 'X' ) ) {
		return false;
	}
	return true;
}

/**
 * Whether this request is an AMP page, where a plain <ins> unit is invalid. Covers
 * the official AMP plugin (amp_is_request) and AMP for WP (ampforwp_is_amp_endpoint).
 *
 * @return bool
 */
function pf_adsense_sidebar_is_amp() {
	if ( function_exists( 'amp_is_request' ) && amp_is_request() ) {
		return true;
	}
	if ( function_exists( 'ampforwp_is_amp_endpoint' ) && ampforwp_is_amp_endpoint() ) {
		return true;
	}
	return false;
}

/**
 * Whether to emit the real AdSense markup (loader + unit) to live visitors:
 * production, fully configured, and not an AMP request.
 *
 * @return bool
 */
function pf_adsense_sidebar_should_serve() {
	if ( ! defined( 'WP_ENV' ) || 'production' !== WP_ENV ) {
		return false;
	}
	if ( ! pf_adsense_sidebar_is_configured() ) {
		return false;
	}
	if ( pf_adsense_sidebar_is_amp() ) {
		return false;
	}
	return true;
}

/**
 * Print the AdSense loader in <head>. This plugin owns the loader because Site
 * Kit's AdSense snippet is disabled (it pointed at a stale hosted account). The
 * same loader also enables Auto Ads if Auto Ads is turned on for this account.
 */
function pf_adsense_sidebar_loader() {
	if ( defined( 'PF_ADSENSE_SIDEBAR_SKIP_LOADER' ) && PF_ADSENSE_SIDEBAR_SKIP_LOADER ) {
		return; // Loader provided elsewhere (e.g. Site Kit re-enabled for this account).
	}
	if ( ! pf_adsense_sidebar_should_serve() ) {
		return;
	}
	printf(
		'<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=%s" crossorigin="anonymous"></script>' . "\n",
		esc_attr( PF_ADSENSE_SIDEBAR_CLIENT )
	);
}
add_action( 'wp_head', 'pf_adsense_sidebar_loader', 20 );

/**
 * Print the ad as the first widget of the target sidebar. Hooked on
 * dynamic_sidebar_before, which fires before the widget loop, so the emitted
 * <section> lands at section[1]. The markup mirrors the theme's before_widget /
 * after_widget wrapper (a <section class="widget ...">) so it blends in visually.
 *
 * @param int|string $index       The sidebar ID currently being rendered.
 * @param bool       $has_widgets Whether the sidebar has widgets (unused).
 */
function pf_adsense_sidebar_inject( $index, $has_widgets = true ) {
	if ( PF_ADSENSE_SIDEBAR_TARGET !== $index ) {
		return; // Only the main sidebar.
	}
	if ( pf_adsense_sidebar_is_amp() ) {
		return; // AMP handled separately; plain <ins> is invalid there.
	}

	$is_production = defined( 'WP_ENV' ) && 'production' === WP_ENV;

	if ( $is_production ) {
		if ( ! pf_adsense_sidebar_is_configured() ) {
			return; // Not configured yet: show nothing to real visitors.
		}
		printf(
			'<section id="pf-adsense-sidebar" class="widget widget_pf_adsense_sidebar">' .
				'<ins class="adsbygoogle" style="display:block" data-ad-client="%1$s" data-ad-slot="%2$s" data-ad-format="auto" data-full-width-responsive="true"></ins>' .
				'<script>(adsbygoogle = window.adsbygoogle || []).push({});</script>' .
			'</section>',
			esc_attr( PF_ADSENSE_SIDEBAR_CLIENT ),
			esc_attr( PF_ADSENSE_SIDEBAR_SLOT )
		);
		return;
	}

	// Non-production (local/staging): a visible placeholder so the position is
	// reviewable locally without loading real ads (which would be invalid traffic).
	echo '<section id="pf-adsense-sidebar" class="widget widget_pf_adsense_sidebar">' .
		'<div style="border:2px dashed #b0b0b0;background:#fafafa;color:#888;' .
		'font:12px/1.4 sans-serif;text-align:center;padding:24px 8px;min-height:120px;' .
		'display:flex;align-items:center;justify-content:center;">' .
		'AdSense sidebar slot<br>(sidebar-1 &middot; section[1] &middot; dev preview)' .
		'</div></section>';
}
add_action( 'dynamic_sidebar_before', 'pf_adsense_sidebar_inject', 10, 2 );
