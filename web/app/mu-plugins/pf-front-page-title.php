<?php
/**
 * Plugin Name: Periodismo Federal — Front page <title>
 * Description: Sets the home page <title> tag, which renders empty because the WordPress Site Title and Tagline are blank (Settings -> General). Scoped to the front page only via document_title_parts, so it never changes article titles (already correct, they use the post title) nor the site title the theme prints in its header.
 * Version: 1.0.0
 *
 * Why not just set the blogname option: blogname is also rendered in the theme
 * header, so overriding it globally could surface text next to the logo. This
 * stays surgical — it fixes only the front-page document title. It pairs with
 * pf-open-graph.php, which fills the matching home og:title/og:description (the
 * <title> tag and Open Graph are produced by different code paths, so each needs
 * its own fix).
 *
 * Rollback = delete this file.
 *
 * @package pf_front_page_title
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

/**
 * Fill the front-page document title parts. Only the home comes out empty;
 * single posts, archives and pages already produce correct titles.
 *
 * @param array $parts Title segments: 'title', 'page', 'tagline', 'site'.
 * @return array
 */
function pf_front_page_title_parts( $parts ) {
	if ( is_front_page() || is_home() ) {
		$parts['title']   = 'Periodismo Federal';
		$parts['tagline'] = 'Noticias de Argentina y las provincias';
	}
	return $parts;
}
add_filter( 'document_title_parts', 'pf_front_page_title_parts' );
