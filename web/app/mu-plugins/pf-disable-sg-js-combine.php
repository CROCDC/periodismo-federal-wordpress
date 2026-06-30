<?php
/**
 * Plugin Name: PF Disable SG Optimizer JS Combine
 * Description: SiteGround Optimizer's "Combine JavaScript Files" is incompatible with Bedrock's
 *              non-standard docroot: it builds the combined bundle's URL from the absolute
 *              filesystem path (/home/customer/www/.../bedrock/web/app/uploads/...) instead of the
 *              web path (/app/uploads/...), so the <script> 404s and every script SG folded into
 *              that bundle never executes. Force the combine_javascript option off so scripts load
 *              individually with correct URLs. mu-plugins load before SG reads the option, so the
 *              filter is in place in time. Reversible: delete this file.
 */

add_filter( 'pre_option_siteground_optimizer_combine_javascript', '__return_zero' );
