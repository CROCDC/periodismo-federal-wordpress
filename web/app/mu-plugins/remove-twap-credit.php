<?php
/**
 * Plugin Name: Remove Twitter Auto Publish Credit
 * Description: Hides the "WP Twitter Auto Publish Powered By XYZScripts.com" backlink that the
 *              twitter-auto-publish plugin prints in wp_footer (the <body> > div[2] element).
 *              Short-circuits the xyz_credit_link option so the credit action is never registered.
 *              mu-plugins load before regular plugins, so this filter is in place when the plugin
 *              reads the option at load time.
 */

add_filter('pre_option_xyz_credit_link', static function () {
    // Any value other than "twap" prevents the wp_footer credit hook from being added.
    return 'none';
});
