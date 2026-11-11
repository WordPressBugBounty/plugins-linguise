<?php

namespace Linguise\WordPress\Integrations;

defined('ABSPATH') || die('');

/**
 * SiteGround Speed Optimizer compatibility for Linguise
 *
 * This integration will make sure that the Linguise assets are not
 * minified, combined, or asynchronously loaded by SiteGround Speed
 * Optimizer, so the switcher and its configuration stay intact.
 */
class SiteGroundOptimizerIntegration extends LinguiseBaseIntegrations
{
    /**
     * Plugin name
     *
     * @var string
     */
    public static $name = 'SiteGround Optimizer';

    /**
     * The WordPress handle used by Linguise for both the JS and CSS assets.
     *
     * @see src/switcher.php
     *
     * @var string
     */
    const LINGUISE_HANDLE = 'linguise_switcher';

    /**
     * Decides if the integration should be loaded.
     *
     * @return boolean
     */
    public function shouldLoad()
    {
        return is_plugin_active('sg-cachepress/sg-cachepress.php');
    }

    /**
     * Registers the filter for the integration.
     *
     * @return void
     */
    public function init()
    {
        add_filter('sgo_js_minify_exclude', [$this, 'excludeLinguiseHandle'], 10, 1);
        add_filter('sgo_javascript_combine_exclude', [$this, 'excludeLinguiseHandle'], 10, 1);
        add_filter('sgo_js_async_exclude', [$this, 'excludeLinguiseHandle'], 10, 1);
        add_filter('sgo_css_minify_exclude', [$this, 'excludeLinguiseHandle'], 10, 1);
        add_filter('sgo_css_combine_exclude', [$this, 'excludeLinguiseHandle'], 10, 1);
    }

    /**
     * Destroys the integration
     *
     * @return void
     */
    public function destroy()
    {
        remove_filter('sgo_js_minify_exclude', [$this, 'excludeLinguiseHandle'], 10);
        remove_filter('sgo_javascript_combine_exclude', [$this, 'excludeLinguiseHandle'], 10);
        remove_filter('sgo_js_async_exclude', [$this, 'excludeLinguiseHandle'], 10);
        remove_filter('sgo_css_minify_exclude', [$this, 'excludeLinguiseHandle'], 10);
        remove_filter('sgo_css_combine_exclude', [$this, 'excludeLinguiseHandle'], 10);
    }

    /**
     * Adds the Linguise handle to an SGO asset exclusion list.
     *
     * The list is kept as-is otherwise, so exclusions coming from SGO
     * settings or other plugins are preserved and never duplicated.
     *
     * @param array $excluded_handles The handles already excluded from the optimization.
     *
     * @return array The updated list of excluded handles.
     */
    public function excludeLinguiseHandle($excluded_handles)
    {
        if (!is_array($excluded_handles)) {
            return $excluded_handles;
        }

        if (!in_array(self::LINGUISE_HANDLE, $excluded_handles, true)) {
            $excluded_handles[] = self::LINGUISE_HANDLE;
        }

        return $excluded_handles;
    }
}
