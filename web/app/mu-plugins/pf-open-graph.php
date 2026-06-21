<?php
/**
 * Plugin Name: Periodismo Federal — Open Graph supplement
 * Description: Fills the gaps in Jetpack's Open Graph output without disturbing its per-article tags. Jetpack already emits correct dynamic OG/Twitter tags on single posts (title, excerpt, featured image); this only supplements the SITE-WIDE and HOME values Jetpack leaves empty: og:site_name, og:image:secure_url, og:image:type, and the front-page og:title/og:description.
 * Version: 1.0.0
 *
 * Why a filter and not a static <meta> block: this is a news site, so every
 * article must keep its own preview (featured image + headline + excerpt).
 * Hardcoding a single OG block in the theme <head> would override Jetpack and
 * make every article share one image/title. Hooking jetpack_open_graph_tags
 * edits the array Jetpack is about to print, so per-article behaviour is intact.
 *
 * Root-cause note: the home og:title was "(no title)" and the <title> tag was
 * empty because the WordPress Site Title/Tagline are blank (Settings -> General).
 * Setting those at the source fixes both; the front-page override below keeps the
 * preview correct until then (and stays harmless once the Site Title is set).
 *
 * Rollback = delete this file.
 *
 * @package pf_open_graph
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

/**
 * Map an OG image URL to its MIME type by extension. Jetpack routes images
 * through Photon (i0.wp.com/.../name.png?fit=...), so the real extension is in
 * the path, before the query string.
 *
 * @param string $url Image URL.
 * @return string MIME type, or '' if not recognized.
 */
function pf_og_image_mime( $url ) {
	$path = wp_parse_url( (string) $url, PHP_URL_PATH );
	$ext  = strtolower( pathinfo( (string) $path, PATHINFO_EXTENSION ) );
	switch ( $ext ) {
		case 'png':
			return 'image/png';
		case 'jpg':
		case 'jpeg':
			return 'image/jpeg';
		case 'webp':
			return 'image/webp';
		case 'gif':
			return 'image/gif';
		default:
			return '';
	}
}

/**
 * Supplement Jetpack's Open Graph tag array. Jetpack prints every key it returns
 * as a generic <meta property="KEY" content="VALUE">, so adding keys here is enough.
 *
 * @param array $tags Associative array of og:* tags Jetpack is about to print.
 * @return array Modified tags.
 */
function pf_open_graph_tags( $tags ) {
	if ( ! is_array( $tags ) ) {
		return $tags;
	}

	// Jetpack never emits og:site_name; add it site-wide for branding.
	$tags['og:site_name'] = 'Periodismo Federal';

	// Front page: Site Title is blank, so Jetpack falls back to "(no title)"
	// and emits no description. Supply both (see root-cause note in the header).
	if ( is_front_page() || is_home() ) {
		$tags['og:title']       = 'Periodismo Federal';
		$tags['og:description'] = 'Periodismo Federal — Noticias de Argentina y las provincias: política, economía, sociedad y actualidad federal.';
	}

	// WhatsApp/Facebook ignore the image on HTTPS pages when og:image:secure_url
	// is missing. Jetpack's og:image is already the HTTPS Photon URL, so mirror
	// it and declare the MIME so clients handle the asset correctly.
	if ( ! empty( $tags['og:image'] ) ) {
		$image = is_array( $tags['og:image'] ) ? reset( $tags['og:image'] ) : $tags['og:image'];

		if ( empty( $tags['og:image:secure_url'] ) ) {
			$tags['og:image:secure_url'] = $image;
		}

		if ( empty( $tags['og:image:type'] ) ) {
			$mime = pf_og_image_mime( $image );
			if ( '' !== $mime ) {
				$tags['og:image:type'] = $mime;
			}
		}
	}

	return $tags;
}
add_filter( 'jetpack_open_graph_tags', 'pf_open_graph_tags' );
