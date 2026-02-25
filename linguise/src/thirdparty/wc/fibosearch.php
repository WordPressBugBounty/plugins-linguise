<?php

namespace Linguise\WordPress\Integrations;

use Linguise\WordPress\FragmentHandler;
use Linguise\WordPress\Helper as WPHelper;

defined('ABSPATH') || die('');

/**
 * Integration with FiboSearch – Ajax Search for WooCommerce
 *
 * Helps translate extra attributes and fragments for FiboSearch
 */
class WCFiboSearchIntegration extends LinguiseBaseIntegrations
{
    /**
     * Plugin name
     *
     * @var string
     */
    public static $name = 'FiboSearch - Ajax Search for WooCommerce';

    /**
     * A collection of fragment overrides that we want to translate
     *
     * @var array
     */
    protected static $fragment_overrides = [
        [
            'name' => 'ajax-search-for-woocommerce',
            'key' => 'jquery-dgwt-wcas-js-extra',
            'match' => 'var dgwt_wcas = (.*?);',
            'replacement' => 'var dgwt_wcas = $$JSON_DATA$$;',
        ]
    ];

    /**
     * Initialize the integration.
     * Sets up fragment keys to be translated for Payment Plugin PayPal
     */
    public function __construct()
    {
        // Override so we can add custom keys loop
        $fragment_filters = [];

        // Disallowed keys
        $regex_full_key_disallowed = [
            'img_url',
            'magnifier_icon',
            'custom_params',
        ];

        foreach ($regex_full_key_disallowed as $regex_full_key) {
            $fragment_filters[] = [
                'key' => $regex_full_key,
                'mode' => 'regex_full',
                'kind' => 'deny',
            ];
        }

        /**
         * Allow translate labels.no_results key
         */
        $fragment_filters[] = [
            'key' => 'labels\.no_results',
            'mode' => 'regex_full',
            'kind' => 'allow',
        ];

        // Deny ajax_search_endpoint key
        $fragment_filters[] = [
            'key' => 'ajax_search_endpoint',
            'mode' => 'exact',
            'kind' => 'deny',
        ];

        self::$fragment_keys = $fragment_filters;
    }

    /**
     * Decides if the integration should be loaded.
     *
     * @codeCoverageIgnore
     *
     * @return boolean
     */
    public function shouldLoad()
    {
        return is_plugin_active('ajax-search-for-woocommerce/ajax-search-for-woocommerce.php') || is_plugin_active('ajax-search-for-woocommerce-premium/ajax-search-for-woocommerce.php');
    }

    /**
     * Initializes the integration.
     *
     * @codeCoverageIgnore
     *
     * @return void
     */
    public function init()
    {
        add_filter('dgwt/wcas/endpoint/search', [$this, 'hookEndpointSearch'], 10, 1);
        add_filter('dgwt/wcas/phrase', [$this, 'hookPhraseTranslate'], 10, 1);
        add_filter('dgwt/wcas/search_results/output', [$this, 'hookTranslateOutput'], 10, 1);
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
        remove_filter('dgwt/wcas/endpoint/search', [$this, 'hookEndpointSearch'], 10);
        remove_filter('dgwt/wcas/phrase', [$this, 'hookPhraseTranslate'], 10);
        remove_filter('dgwt/wcas/search_results/output', [$this, 'hookTranslateOutput'], 10);
    }

    /**
     * Append linguise lang to FiboSearch ajax endpoint url
     *
     * @param string $url The URL to be modified
     *
     * @return string The modified URL with the language parameter appended
     */
    public function hookEndpointSearch($url)
    {
        $linguise_lang = WPHelper::getLanguage();
        if (!$linguise_lang) {
            return $url;
        }

        $url_with_lang = add_query_arg('linguise_language', $linguise_lang, $url);
        return $url_with_lang;
    }

    /**
     * Do a search translation for the given keyword
     *
     * @param string $keyword The keyword to be translated
     *
     * @return string The translated keyword
     */
    public function hookPhraseTranslate($keyword)
    {
        $is_ajax = defined('DGWT_WCAS_AJAX');
        $linguise_language = WPHelper::getLanguage();

        if (!$is_ajax || !$linguise_language) {
            // return original keyword
            return $keyword;
        }

        $options = linguiseGetOptions();

        if (!$options['search_translation']) {
            return $keyword;
        }

        $translation = $this->translateSearch(['search' => $keyword], $linguise_language);
        if (empty($translation)) {
            return $keyword;
        }

        if (empty($translation->search)) {
            return $keyword;
        }

        $keyword = $translation->search;

        return $keyword;
    }

    /**
     * Translate the AJAX result from FiboSearch
     *
     * @param string $output The HTML output to be translated
     *
     * @codeCoverageIgnore
     *
     * @return string The translated HTML output
     */
    public function hookTranslateOutput($output)
    {
        $language = WPHelper::getLanguage();
        if ($language === null) {
            $language = WPHelper::getLanguageFromReferer();
        }

        if ($language === null) {
            return $output;
        }

        // Add extra template to output
        add_filter('linguise_fragment_filters', function ($filters) {
            // @codeCoverageIgnoreStart
            $filters[] = [
                'key' => 'value',
                'mode' => 'regex_full',
                'kind' => 'allow',
            ];

            $filters[] = [
                'key' => 'url',
                'mode' => 'regex_full',
                'kind' => 'allow',
            ];

            return $filters;
            // @codeCoverageIgnoreEnd
        }, 15, 1);

        // Loop through the output and translate the values
        $fragments = FragmentHandler::collectFragmentFromJson($output, true);
        if (empty($fragments)) {
            return $output;
        }

        $html_fragments = FragmentHandler::intoHTMLFragments('fibosearch-tmpl', 'render-output', [
            'mode' => 'auto',
            'fragments' => $fragments,
        ]);

        $content = '<html><head></head><body>';
        $content .= $html_fragments;
        $content .= '</body></html>';

        $result = $this->translateFragments($content, $language, '/');

        if (empty($result)) {
            return $output; // @codeCoverageIgnore
        }

        if (isset($result->redirect)) {
            // Somehow we got this...?
            return $output; // @codeCoverageIgnore
        }

        $translated_fragments = FragmentHandler::intoJSONFragments($result->content);
        if (empty($translated_fragments)) {
            return $output; // @codeCoverageIgnore
        }

        if (!isset($translated_fragments['fibosearch-tmpl'])) {
            return $output; // @codeCoverageIgnore
        }
    
        if (!isset($translated_fragments['fibosearch-tmpl']['render-output'])) {
            return $output; // @codeCoverageIgnore
        }
    
        $tl_json_frag = $translated_fragments['fibosearch-tmpl']['render-output'];
        if (empty($tl_json_frag)) {
            return $output; // @codeCoverageIgnore
        }
    
        $tl_json_frag_list = $tl_json_frag['fragments'];
        if (empty($tl_json_frag_list)) {
            return $output; // @codeCoverageIgnore
        }

        $replaced_content = FragmentHandler::applyTranslatedFragmentsForAuto($output, $tl_json_frag_list);
        if ($replaced_content !== false) {
            return $replaced_content;
        }

        return $output; // @codeCoverageIgnore
    }
}
