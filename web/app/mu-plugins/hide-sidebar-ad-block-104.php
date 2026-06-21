<?php
/**
 * Plugin Name: Hide Sidebar Ad (block-104)
 * Description: Removes the 300x250 ad banner widget (block-104) from the sidebar. The widget lives
 *              in the database (Appearance > Widgets), not in code, so it is stripped at render time
 *              via the sidebars_widgets filter instead of via the admin. Removing it server-side
 *              means the banner is never output and the ad image is never requested (unlike a CSS hide).
 */

add_filter('sidebars_widgets', static function ($sidebars_widgets) {
    if (is_admin()) {
        // Keep it visible/editable in the Widgets screen so it can still be managed from the admin.
        return $sidebars_widgets;
    }

    foreach ($sidebars_widgets as $sidebar => $widgets) {
        if (!is_array($widgets)) {
            continue;
        }
        $sidebars_widgets[$sidebar] = array_values(
            array_filter($widgets, static fn ($widget_id) => $widget_id !== 'block-104')
        );
    }

    return $sidebars_widgets;
});
