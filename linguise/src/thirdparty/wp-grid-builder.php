<?php

namespace Linguise\WordPress\Integrations;

use Linguise\Vendor\Linguise\Script\Core\Debug;
use Linguise\WordPress\FragmentHandler;
use Linguise\WordPress\Helper as WPHelper;

defined('ABSPATH') || die('');

/**
 * Integration for WP Grid Builder plugin
 *
 * Without using dynamic content, we translate WP Grid Builder renders data.
 */
class WPGridBuilderIntegration extends LinguiseBaseIntegrations
{
    /**
     * Plugin name
     *
     * @var string
     */
    public static $name = 'WP Grid Builder';

    /**
     * A collection of fragment keys that will be translated
     *
     * @var array{key:string, mode:'exact'|'path'|'wildcard'|'regex'|'regex_full',kind:'allow'|'deny'}
     */
    protected static $fragment_keys = [
        [
            'key' => 'facets\.\d+\.(html|name)',
            'mode' => 'regex_full',
            'kind' => 'allow',
            'cast' => 'html-main'
        ],
        [
            'key' => 'facet_name',
            'mode' => 'exact',
            'kind' => 'allow',
            'cast' => 'html-main'
        ],
    ];

    /**
     * Decides if the WPGB integration should be loaded.
     *
     * @codeCoverageIgnore
     *
     * @return boolean
     */
    public function shouldLoad()
    {
        return is_plugin_active('wp-grid-builder/wp-grid-builder.php');
    }

    /**
     * Load the integration
     *
     * @codeCoverageIgnore
     *
     * @return void
     */
    public function init()
    {
        // Capture preview responses which call wp_send_json() before WPGB response filters run.
        add_action('wp_grid_builder/async/render', [$this, 'captureFacetPreviewResponse'], PHP_INT_MIN, 1);
        add_action('wp_grid_builder/async/refresh', [$this, 'captureFacetPreviewResponse'], PHP_INT_MIN, 1);

        // Has 'posts' and 'facets'
        add_filter('wp_grid_builder/async/refresh_response', [$this, 'translateRenderOutput'], 1000, 1);
        // Has 'facets'
        add_filter('wp_grid_builder/async/render_response', [$this, 'translateRenderOutput'], 1000, 1);
        // Array of facets data
        add_filter('wp_grid_builder/async/search_response', [$this, 'translateRenderOutput'], 1000, 1);
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
        remove_action('wp_grid_builder/async/render', [$this, 'captureFacetPreviewResponse'], PHP_INT_MIN);
        remove_action('wp_grid_builder/async/refresh', [$this, 'captureFacetPreviewResponse'], PHP_INT_MIN);

        remove_filter('wp_grid_builder/async/refresh_response', [$this, 'translateRenderOutput'], 1000);
        remove_filter('wp_grid_builder/async/render_response', [$this, 'translateRenderOutput'], 1000);
        remove_filter('wp_grid_builder/async/search_response', [$this, 'translateRenderOutput'], 1000);
    }

    /**
     * Capture facet preview JSON before an early wp_send_json() ends the request.
     *
     * @param array $attributes Grid/template attributes
     *
     * @return void
     */
    public function captureFacetPreviewResponse($attributes)
    {
        // if (!is_array($attributes) || !isset($attributes['id']) || $attributes['id'] !== 'wpgb_facet_preview') {
        //     return;
        // }

        ob_start([$this, 'translateBufferedJson']);
    }

    /**
     * Translate a complete JSON response captured from wp_send_json().
     *
     * @param string $json JSON response body
     *
     * @return string Translated JSON, or original body when it cannot be processed
     */
    public function translateBufferedJson($json)
    {
        $output = json_decode($json, true);
        if (!is_array($output) || json_last_error() !== JSON_ERROR_NONE) {
            return $json;
        }

        $this->initializeConfiguration();
        $translated_output = $this->translateRenderOutput($output);
        $translated_json = wp_json_encode($translated_output);

        return $translated_json !== false ? $translated_json : $json;
    }

    /**
     * Hooked into WPGB's render output filter to translate the wpgb-output
     * fragment.
     *
     * @param array $output The rendered output from WPGB
     *
     * @return string The translated WPGB output HTML
     */
    public function translateRenderOutput($output)
    {
        $language = WPHelper::getLanguage();

        // For referer, ensure it's POST request
        if ($language === null && strtoupper($_SERVER['REQUEST_METHOD']) === 'POST') {
            $language = WPHelper::getLanguageFromReferer();
        }

        if ($language === null) {
            return $output;
        }

        // Add template to output
        add_filter('linguise_fragment_filters', function ($filters) {
            $filters[] = [
                'key' => 'posts',
                'mode' => 'path',
                'kind' => 'allow',
                // Since sometimes this return link
                'cast' => 'html-main',
            ];

            return $filters;
        }, 15, 1);

        // Loop through facets
        $fragments = FragmentHandler::collectFragmentFromJson($output, true);
        if (empty($fragments)) {
            return $output;
        }

        $html_fragments = FragmentHandler::intoHTMLFragments('wpgb-filters', 'render-output', [
            'mode' => 'auto',
            'fragments' => $fragments,
        ]);

        $html_content = '<html><head></head><body>';
        $html_content .= '<divlinguise data-wp-linguise-class="wpgb-filters-stub"></divlinguise>'; // stub this so linguise-ignore works
        $html_content .= $html_fragments;
        $html_content .= '</body></html>';

        $translated_result = $this->translateFragments($html_content, $language, '/');

        if ($translated_result === false) {
            return $output;
        }

        if (isset($translated_result->redirect)) {
            // Somehow we got this...?
            return $output;
        }

        $translated_fragments = FragmentHandler::intoJSONFragments($translated_result->content);
        if (empty($translated_fragments)) {
            return $output;
        }

        // Get $translated_fragments['wpgb-filters']['render-output']
        if (!isset($translated_fragments['wpgb-filters'])) {
            return $output;
        }

        if (!isset($translated_fragments['wpgb-filters']['render-output'])) {
            return $output;
        }

        $tl_json_frag = $translated_fragments['wpgb-filters']['render-output'];
        if (empty($tl_json_frag)) {
            return $output;
        }

        $tl_json_frag_list = $tl_json_frag['fragments'];
        if (empty($tl_json_frag_list)) {
            return $output;
        }

        $replaced_content = FragmentHandler::applyTranslatedFragmentsForAuto($output, $tl_json_frag_list);
        if ($replaced_content !== false) {
            return $replaced_content;
        }

        return $output;
    }
}
