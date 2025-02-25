<?php
use Linguise\WordPress\Helper;

include_once ABSPATH . 'wp-admin/includes/plugin.php';
if (!is_plugin_active('wp-rocket/wp-rocket.php')) {
    return;
}

/**
 * Excludes Linguise scripts from JS minification
 *
 * @param array $excluded_js An array of JS handles enqueued in WordPress.
 *
 * @return array the updated array of handles
 */
function linguise_rocket_exclude_js($excluded_js)
{
    $excluded_js[] = str_replace(home_url(), '', Helper::getScriptUrl('/assets/js/front.bundle.js'));

    return $excluded_js;
}
add_filter('rocket_exclude_js', 'linguise_rocket_exclude_js', 10, 1);
add_filter('rocket_exclude_defer_js', 'linguise_rocket_exclude_js', 10, 1);

/**
 * Excludes Linguise CSS from WP Rocket CSS minification
 *
 * @param array $excluded_js An array of CSS handles enqueued in WordPress.
 *
 * @return array the updated array of handles
 */
function linguise_rocket_exclude_css($excluded_js)
{
    $excluded_js[] = str_replace(home_url(), '', Helper::getScriptUrl('/assets/css/front.bundle.css'));

    return $excluded_js;
}
add_filter('rocket_exclude_css', 'linguise_rocket_exclude_css', 10, 1);
