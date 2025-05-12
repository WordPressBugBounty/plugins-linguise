<?php

namespace Linguise\WordPress\Integrations;

use Linguise\WordPress\Helper as WPHelper;

defined('ABSPATH') || die('');

/**
 * WP-Rocket compatibility for Linguise
 *
 * This integration will made sure that Linguise script is ignored.
 */
class WPRocketIntegration extends LinguiseBaseIntegrations
{
    /**
     * Plugin name
     *
     * @var string
     */
    public static $name = 'WP-Rocket';

    /**
     * Decides if the integration should be loaded.
     *
     * @return boolean
     */
    public function shouldLoad()
    {
        return is_plugin_active('wp-rocket/wp-rocket.php');
    }

    /**
     * Registers the filter for the integration.
     *
     * @return void
     */
    public function init()
    {
        add_filter('rocket_exclude_js', [$this, 'excludeJS'], 10, 1);
        add_filter('rocket_exclude_defer_js', [$this, 'excludeJS'], 10, 1);
        add_filter('rocket_exclude_css', [$this, 'excludeCSS'], 10, 1);
        add_filter('rocket_rucss_external_exclusions', 'excludeCSS', 10, 1);
    }

    /**
     * Destroys the integration
     *
     * @return void
     */
    public function destroy()
    {
        remove_filter('rocket_exclude_js', [$this, 'excludeJS'], 10, 1);
        remove_filter('rocket_exclude_defer_js', [$this, 'excludeJS'], 10, 1);
        remove_filter('rocket_exclude_css', [$this, 'excludeCSS'], 10, 1);
    }

    /**
     * Excludes Linguise scripts from JS minification
     *
     * @param array $excluded_js An array of JS handles enqueued in WordPress.
     *
     * @return array the updated array of handles
     */
    public function excludeJS($excluded_js)
    {
        $excluded_js[] = str_replace(home_url(), '', WPHelper::getScriptUrl('/assets/js/front.bundle.js'));

        return $excluded_js;
    }

    /**
     * Excludes Linguise CSS from WP Rocket CSS minification
     *
     * @param array $excluded_css An array of CSS handles enqueued in WordPress.
     *
     * @return array the updated array of handles
     */
    public function excludeCSS($excluded_css)
    {
        $excluded_css[] = str_replace(home_url(), '', WPHelper::getScriptUrl('/assets/css/front.bundle.css'));

        return $excluded_css;
    }
}
