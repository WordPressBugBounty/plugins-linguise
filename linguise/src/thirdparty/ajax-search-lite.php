<?php

namespace Linguise\WordPress\Integrations;

use Linguise\WordPress\Helper as WPHelper;

defined('ABSPATH') || die('');

/**
 * Integration for Ajax Search Lite – Live Search & Filter
 *
 * Exclude some js vars
 */
class AjaxSearchLiteIntegration extends LinguiseBaseIntegrations
{
    /**
     * Plugin name
     *
     * @var string
     */
    public static $name = 'Ajax Search Lite - Live Search & Filter';

    /**
     * A collection of fragment overrides that we want to translate
     *
     * @var array
     */
    protected static $fragment_overrides = [
        [
            'name' => 'ajax-search-lite-context',
            'match' => 'window\.ASL_INSTANCES\[1\] = (.*?);',
            'replacement' => 'window.ASL_INSTANCES[1] = $$JSON_DATA$$;',
        ]
    ];

    /**
     * A collection of fragment keys that will be translated
     *
     * @var array{key:string, mode:'exact'|'path'|'wildcard'|'regex'|'regex_full',kind:'allow'|'deny'}
     */
    protected static $fragment_keys = [
        [
            'key' => 'homeurl',
            'mode' => 'exact',
            'kind' => 'allow',
        ],
    ];

    /**
     * The original query
     *
     * @var string
     */
    protected $original_query = '';

    /**
     * Load the integration
     *
     * @return void
     */
    public function init()
    {
        add_filter('asl_query_args', [$this, 'translateSearchQuery'], 10, 1);
        add_filter('asl_print_search_query', [$this, 'replaceBackoriginalQuery'], 10, 1);
        add_filter('script_loader_tag', [$this, 'addHtmlAttribute'], 10, 3);

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action
        if (!empty($_REQUEST['action']) && $_REQUEST['action'] === 'ajaxsearchlite_search') {
            add_filter('asl_before_ajax_output', [$this, 'translateAjaxResult'], 10, 1);
        }
    }

    /**
     * Decides if the Ajax Search Lite – Live Search & Filter integration should be loaded.
     *
     * @return boolean
     */
    public function shouldLoad()
    {
        return is_plugin_active('ajax-search-lite/ajax-search-lite.php');
    }

    /**
     * Unload the integration
     *
     * @return void
     */
    public function destroy()
    {
        remove_filter('asl_query_args', [$this, 'translateSearchQuery'], 10, 1);
        remove_filter('asl_print_search_query', [$this, 'replaceBackoriginalQuery'], 10, 1);
        remove_filter('script_loader_tag', [$this, 'addHtmlAttribute'], 10, 3);

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action
        if (!empty($_REQUEST['action']) && $_REQUEST['action'] === 'ajaxsearchlite_search') {
            add_filter('asl_before_ajax_output', [$this, 'translateAjaxResult'], 10, 1);
        }
    }

    /**
     * Hooked into Ajax Search Lite's query args filter to translate the query.
     *
     * @param array $query_args Query args
     *
     * @return array
     */
    public function translateSearchQuery($query_args)
    {
        $options = linguiseGetOptions();
        if (!$options['search_translation']) {
            return $query_args;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action
        if (!empty($_REQUEST['action']) && $_REQUEST['action'] === 'ajaxsearchlite_search') {
            $language = WPHelper::getLanguageFromReferer();

            if (!$language) {
                return $query_args;
            }

            /**
             * Was translated by linguise.php
             *
             * @see linguise.php
             */
            $original_query = $query_args['s'];

            $translation = $this->translateSearch(['search' => $original_query], $language);

            if (empty($translation)) {
                return $query_args;
            }

            if (empty($translation->search)) {
                return $query_args;
            }

            $translated_query = $translation->search;

            $query_args['s'] = $translated_query;
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
            $translated_query = get_query_var('s');
            $this->original_query = $query_args['s'];

            $query_args['s'] = $translated_query;
        }
        
        return $query_args;
    }

    /**
     * Hooked into Ajax Search Lite's search query filter to replace back the original untranslated search query.
     *
     * @param string $search_query Search query
     *
     * @return string
     */
    public function replaceBackoriginalQuery($search_query)
    {
        $options = linguiseGetOptions();
        if (!$options['search_translation']) {
            return $search_query;
        }

        if (!$this->original_query) {
            return $search_query;
        }

        return $this->original_query;
    }

    /**
     * Filter to add data-no-optimize attribute to Ajax Search Lite's javascript file when LiteSpeed Cache is installed.
     *
     * @param string $tag    The html tag.
     * @param string $handle The script handle.
     * @param string $src    The script source.
     *
     * @return string
     */
    public function addHtmlAttribute($tag, $handle, $src)
    {

        if (is_plugin_active('litespeed-cache/litespeed-cache.php')) {
            if ($handle === 'wd-asl-ajaxsearchlite') {
                // Add data-no-optimize to prevent LiteSpeed from optimizing it
                return str_replace('<script ', '<script data-no-optimize="1" ', $tag);
            }
        }

        return $tag;
    }

    /**
     * Translates the given AJAX search result with the current language.
     *
     * @param string $html_result The AJAX search result
     *
     * @return string The translated AJAX search result
     */
    public function translateAjaxResult($html_result)
    {

        $language = WPHelper::getLanguageFromReferer();

        if (!$language) {
            return $html_result;
        }

        $html_content = '<html><head></head><body>';
        $html_content .= $html_result;
        $html_content .= '</body></html>';

        $translated_content = $this->translateFragments($html_content, $language, '/');

        if (empty($translated_content)) {
            // Failed to translate
            return $html_result;
        }

        if (isset($translated_content->redirect)) {
            // We got redirect URL for some reason?
            return $html_result;
        }

        preg_match('/<body>(.*)<\/body>/s', $translated_content->content, $matches);

        if (!$matches) {
            // No body match
            return $html_result;
        }

        return $matches[1];
    }
}
