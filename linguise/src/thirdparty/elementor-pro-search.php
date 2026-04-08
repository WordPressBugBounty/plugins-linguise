<?php

namespace Linguise\WordPress\Integrations;

use Linguise\WordPress\Helper as WPHelper;

defined('ABSPATH') || die('');

/**
 * Elementor Pro Search widget integration class
 *
 * Intercepts the Elementor Pro Search widget query to translate the search term.
 */
class ElementorProSearchIntegration extends LinguiseBaseIntegrations
{
    /**
     * Plugin name
     *
     * @var string
     */
    public static $name = 'Elementor Pro Search';

    /**
     * The original (untranslated) query, saved during frontend page requests.
     *
     * @var string
     */
    protected $original_query = '';

    /**
     * A collection of fragment keys that will be translated
     *
     * @var array{key:string, mode:'exact'|'path'|'wildcard'|'regex'|'regex_full',kind:'allow'|'deny'}
     */
    protected static $fragment_keys = [
        [
            'key' => '^data',
            'mode' => 'regex_full',
            'kind' => 'allow',
            'cast' => 'html-main',
        ]
    ];

    /**
     * A list of WP-REST ajax methods that should be intercepted.
     *
     * @var string[]
     */
    protected static $wp_rest_ajax_intercept = [
        'elementor-pro/v1/refresh-search',
    ];

    /**
     * Decides if the integration should be loaded.
     *
     * @codeCoverageIgnore
     *
     * @return boolean
     */
    public function shouldLoad()
    {
        $options = linguiseGetOptions();
        return is_plugin_active('elementor-pro/elementor-pro.php') && is_plugin_active('elementor/elementor.php') && $options['search_translation'];
    }

    /**
     * Load the integration.
     *
     * @codeCoverageIgnore
     *
     * @return void
     */
    public function init()
    {
        add_filter('elementor/query/query_args', [$this, 'translateSearchQuery'], 50, 2);
    }

    /**
     * Unload the integration.
     *
     * @codeCoverageIgnore
     *
     * @return void
     */
    public function destroy()
    {
        remove_filter('elementor/query/query_args', [$this, 'translateSearchQuery'], 50);
    }

    /**
     * Hooked into Elementor's query args filter to translate the search term.
     *
     * For AJAX REST requests (e.g. elementor-pro/v1/refresh-search), the search term
     * is translated from the POST body using the referer language.
     * For regular frontend page requests, the already-translated query var is used.
     *
     * @param array                  $query_args WP_Query arguments
     * @param \Elementor\Widget_Base $widget     The Elementor widget instance
     *
     * @return array
     */
    public function translateSearchQuery($query_args, $widget)
    {
        $options = linguiseGetOptions();
        if (!$options['search_translation']) {
            return $query_args;
        }

        if ($widget->get_name() !== 'search') {
            return $query_args;
        }

        if (empty($query_args['s'])) {
            return $query_args;
        }

        // AJAX REST request (e.g. elementor-pro/v1/refresh-search)
        if (strtoupper($_SERVER['REQUEST_METHOD']) === 'POST') {
            $language = WPHelper::getLanguageFromReferer();

            if (!$language) {
                return $query_args;
            }

            $original_query = $query_args['s'];

            $translation = $this->translateSearch(['search' => $original_query], $language);

            if (empty($translation)) {
                return $query_args;
            }

            if (empty($translation->search)) {
                return $query_args;
            }

            $query_args['s'] = $translation->search;
        } else {
            $language = WPHelper::getLanguage();

            if (!$language) {
                return $query_args;
            }

            /**
             * Was translated by linguise.php
             *
             * @see linguise.php
             */
            $this->original_query = $query_args['s'];
            $query_args['s'] = get_query_var('s');
        }

        return $query_args;
    }
}
