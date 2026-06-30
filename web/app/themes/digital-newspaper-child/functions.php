<?php
/**
 * Digital Newspaper child theme.
 *
 * The parent stays the pinned Composer dep (keeps upstream updates); this child is
 * committed and is where edits live: CSS in assets/css/custom.css, markup by copying
 * a parent template file into this directory and editing it there.
 */

// Customizer settings (theme_mods) are stored per-theme, so activating this child would
// otherwise drop the parent's entire configuration (colors, layout, logo, menus) back to
// defaults. Inherit the parent's mods as the base; anything saved on the child (its own
// theme_mods) still wins, so future Customizer edits on the child keep working.
add_filter( 'option_theme_mods_digital-newspaper-child', function ( $child_mods ) {
	$parent_mods = get_option( 'theme_mods_digital-newspaper' );
	if ( ! is_array( $parent_mods ) ) {
		return $child_mods;
	}
	if ( ! is_array( $child_mods ) ) {
		return $parent_mods;
	}
	return array_merge( $parent_mods, $child_mods );
} );

// The Customizer's "Additional CSS" is also stored per-theme (a custom_css post keyed
// by stylesheet). Fall back to the parent's when the child has none of its own.
add_filter( 'wp_get_custom_css', function ( $css, $stylesheet ) {
	if ( '' === $css && 'digital-newspaper-child' === $stylesheet ) {
		$parent_post = wp_get_custom_css_post( 'digital-newspaper' );
		if ( $parent_post instanceof WP_Post ) {
			$css = $parent_post->post_content;
		}
	}
	return $css;
}, 10, 2 );

add_action( 'wp_enqueue_scripts', function () {
	$styles = wp_styles();

	// The parent loads its base/normalize layer via get_stylesheet_uri(), which now
	// resolves to this child's near-empty style.css — so re-enqueue the parent's
	// style.css to keep that layer. Made a dependency of the parent's main.css so it
	// still prints before it, preserving the original base -> main cascade.
	wp_enqueue_style(
		'digital-newspaper-parent-style',
		get_template_directory_uri() . '/style.css',
		array(),
		wp_get_theme( get_template() )->get( 'Version' )
	);
	if ( isset( $styles->registered['digital-newspaper-main-style'] ) ) {
		$styles->registered['digital-newspaper-main-style']->deps[] = 'digital-newspaper-parent-style';
	}

	// Child overrides print after the parent's design styles so they win. Depend only
	// on handles actually registered, so a parent version that drops one won't suppress this.
	$override_deps = array_values( array_filter(
		array( 'digital-newspaper-main-style', 'digital-newspaper-responsive-style' ),
		function ( $handle ) use ( $styles ) {
			return isset( $styles->registered[ $handle ] );
		}
	) );
	wp_enqueue_style(
		'digital-newspaper-child-style',
		get_stylesheet_directory_uri() . '/assets/css/custom.css',
		$override_deps,
		wp_get_theme()->get( 'Version' )
	);
}, 20 );

// Next Tech shared footer. Printed via wp_footer (fires just before </body> in the parent's
// footer.php) so the parent template stays an unmodified, upstream-updatable dependency. The
// wrapper reuses --dark-bk-color so the band matches the site's dark footer in both light and
// dark mode; the component itself is transparent and Shadow-DOM isolated. The remote script
// registers <nexttech-footer> on load; source= tags clicks as utm_source for nexttech's Umami.
add_action( 'wp_footer', function () {
	?>
	<div class="nexttech-footer-wrap" style="background-color: var(--dark-bk-color);">
		<script src="https://nexttech.com.ar/static/js/nexttechFooter.js"></script>
		<nexttech-footer source="periodismo-federal"></nexttech-footer>
	</div>
	<?php
}, 100 );
