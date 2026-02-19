<?php

namespace Linguise\WordPress\Integrations;

use Linguise\WordPress\Helper as WPHelper;

defined('ABSPATH') || die('');

/**
 * Integration for Add Search To Menu
 *
 * Exclude some js vars
 */
class AddSearchToMenuIntegration extends LinguiseBaseIntegrations
{
    /**
     * Plugin name
     *
     * @var string
     */
    public static $name = 'Add Search To Menu (Ivory Search)';

    /**
     * The original query
     *
     * @var string
     */
    protected $original_query = '';

    /**
     * Decides if the Add Search To Menu integration should be loaded.
     *
     * @codeCoverageIgnore
     *
     * @return boolean
     */
    public function shouldLoad()
    {
        return is_plugin_active('add-search-to-menu/add-search-to-menu.php') || is_plugin_active('add-search-to-menu-premium/add-search-to-menu.php');
    }

    /**
     * Checks if the current request is an AJAX request for the Add Search To Menu plugin.
     *
     * @return boolean Returns true if is an AJAX request for the Add Search To Menu plugin else false.
     */
    public function isAjaxSearchRequest()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No nonce verification required for this AJAX action
        if (! is_admin() || ( defined('DOING_AJAX') && DOING_AJAX && isset($_POST['action']) && 'is_ajax_load_posts' === $_POST['action'] )) {
            return true;
        }

        return false;
    }

    /**
     * Load the integration
     *
     * @return void
     */
    public function init()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action
        if ($this->isAjaxSearchRequest()) {
            add_filter('is_ajax_search_args', [$this, 'translateSearchQuery'], 10, 1);

            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Action check only
            if (defined('DOING_AJAX') && DOING_AJAX && isset($_POST['action']) && 'is_ajax_load_posts' === $_POST['action']) {
                add_action('init', [$this, 'overrideAjaxLoadPostsHandler'], 9999999);
            }
        }
    }

    /**
     * Replaces third-party AJAX callbacks with proxy callback.
     *
     * @return void
     */
    public function overrideAjaxLoadPostsHandler()
    {
        if (!class_exists('IS_Ajax')) {
            return;
        }

        $ajax = \IS_Ajax::getInstance();

        remove_action('wp_ajax_is_ajax_load_posts', [$ajax, 'ajax_load_posts']);
        remove_action('wp_ajax_nopriv_is_ajax_load_posts', [$ajax, 'ajax_load_posts']);

        add_action('wp_ajax_is_ajax_load_posts', [$this, 'proxyAjaxLoadPosts'], 10);
        add_action('wp_ajax_nopriv_is_ajax_load_posts', [$this, 'proxyAjaxLoadPosts'], 10);
    }

    /**
     * Proxies Ivory Search AJAX callback and translates returned HTML.
     *
     * @return void
     */
    public function proxyAjaxLoadPosts()
    {
        if (!class_exists('IS_Ajax')) {
            wp_die();
        }

        $ajax = \IS_Ajax::getInstance();

        ob_start();
        add_filter('wp_die_ajax_handler', [$this, 'captureAjaxDieHandler']);

        try {
            $ajax->ajax_load_posts();
        } catch (\Exception $e) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
            /** Stub */
        }

        remove_filter('wp_die_ajax_handler', [$this, 'captureAjaxDieHandler']);

        $search_result = ob_get_clean();
        $translated = $this->translateSearchResult($search_result);

        echo wp_kses_post($translated);
        exit;
    }

    /**
     * Provides custom wp_die AJAX handler to avoid immediate exit while proxying.
     *
     * @return callable
     */
    public function captureAjaxDieHandler()
    {
        return [$this, 'throwAjaxDieException'];
    }

    /**
     * Throws exception instead of terminating immediately for wp_die().
     *
     * @return void
     */
    public function throwAjaxDieException()
    {
        throw new \Exception('ivory_search_ajax_die');
    }

    /**
     * Unload the integration
     *
     * @codeCoverageIgnore
     *
     * @return void
     */
    public function destroy()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action
        if ($this->isAjaxSearchRequest()) {
            remove_filter('is_ajax_search_args', [$this, 'translateSearchQuery'], 10);
        }
    }

    /**
     * Hooked into Add Search To Menu query args filter to translate the query.
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
        if ($this->isAjaxSearchRequest()) {
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
     * Translates a search result.
     *
     * @param string $search_result Search result to be translated.
     *
     * @return string translated search result.
     */
    public function translateSearchResult($search_result)
    {
        if (empty($search_result)) {
            return $search_result;
        }

        $language = WPHelper::getLanguageFromReferer();

        if (!$language) {
            return $search_result;
        }

        $html_content = '<html><head></head><body>';
        $html_content .= $search_result;
        $html_content .= '</body></html>';

        $translated_content = $this->translateFragments($html_content, $language, '/');

        if (empty($translated_content)) {
            return $search_result;
        }

        if (isset($translated_content->redirect)) {
            return $search_result;
        }

        preg_match('/<body>(.*)<\/body>/s', $translated_content->content, $matches);

        if (!$matches) {
            return $search_result;
        }

        return $matches[1];
    }
}
