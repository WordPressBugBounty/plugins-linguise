<?php

namespace Linguise\WordPress\Integrations;

use Linguise\WordPress\Helper;

defined('ABSPATH') || die('');

/**
 * Compatibility for Plugin: Make Column Clickable Elementor
 * Addon for Elementor
 *
 * Ref: https://wordpress.org/plugins/make-column-clickable-elementor/
 */
class ElementorIntegration extends LinguiseBaseIntegrations
{
    /**
     * Plugin name
     *
     * @var string
     */
    public static $name = 'Elementor';

    /**
     * Decides if the integration should be loaded.
     *
     * @codeCoverageIgnore
     *
     * @return boolean
     */
    public function shouldLoad()
    {
        return is_plugin_active('elementor/elementor.php');
    }

    /**
     * Registers the filter for the integration.
     *
     * @codeCoverageIgnore
     *
     * @return void
     */
    public function init()
    {
        add_filter('wpml_translate_single_string', [$this, 'hookElementorClickableElement'], 10, 2);
    }


    /**
     * Destroys the integration
     *
     * @codeCoverageIgnore
     *
     * @return void
     */
    public function destroy()
    {
        remove_filter('wpml_translate_single_string', [$this, 'hookElementorClickableElement'], 10, 2);
    }

    /**
     * Translates the source link for Elementor's Make Column Clickable addon.
     *
     * @param string $source_link The source link to translate.
     * @param string $domain      The domain that the link is related to.
     *
     * @return string The translated link.
     */
    public function hookElementorClickableElement($source_link, $domain)
    {
        // Ensure it's elementor related domain
        if (strpos($domain, 'Make Column Clickable Elementor') === false) {
            return $source_link;
        }

        $language = Helper::getLanguage();
        if (empty($language)) {
            return $source_link;
        }

        $source_path = rtrim(str_replace(wp_parse_url(linguiseGetSite(), PHP_URL_PATH), '', parse_url($source_link, PHP_URL_PATH)), '/');

        global $wpdb;
        $prefix = $wpdb->prefix;
        $results = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT translation
                  FROM ' . $prefix . 'linguise_urls
                  WHERE source = %s
                  AND language = %s
                  LIMIT 1',
                $source_path,
                $language
            )
        );

        if ($results) {
            $source_link = esc_url(linguiseGetSite() . $results[0]->translation);
        }

        return $source_link;
    }
}
