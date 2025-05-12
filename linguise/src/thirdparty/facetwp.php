<?php

namespace Linguise\WordPress\Integrations;

use Linguise\WordPress\FragmentHandler;
use Linguise\WordPress\Helper as WPHelper;

defined('ABSPATH') || die('');

/**
 * Integration for FacetWP
 *
 * Without using dynamic content, we translate FacetWP render output data.
 */
class FacetWPIntegration extends LinguiseBaseIntegrations
{
    /**
     * Plugin name
     *
     * @var string
     */
    public static $name = 'FacetWP';

    /**
     * A collection of fragment keys that will be translated
     *
     * @var array{key:string, mode:'exact'|'path'|'wildcard'|'regex'|'regex_full',kind:'allow'|'deny'}
     */
    protected static $fragment_keys = [
        [
            'key' => 'facets\..*',
            'mode' => 'regex_full',
            'kind' => 'allow',
        ],
        [
            'key' => 'settings\.labels\..*',
            'mode' => 'regex_full',
            'kind' => 'allow',
        ],
        [
            'key' => 'sort',
            'mode' => 'path',
            'kind' => 'allow',
        ],
        [
            'key' => 'nonce',
            'mode' => 'path',
            'kind' => 'deny',
        ],
        [
            'key' => 'preload_data\.settings\.(num_choices|pager)\..*',
            'mode' => 'regex_full',
            'kind' => 'deny',
        ],
        [
            'key' => 'preload_data.template',
            'mode' => 'path',
            'kind' => 'deny',
        ],
        [
            'key' => 'preload_data\.settings\.(\w+)\.show_expanded',
            'mode' => 'regex_full',
            'kind' => 'deny',
        ],
    ];

    /**
     * A collection of fragment overrides that we want to translate
     *
     * @var array
     */
    protected static $fragment_overrides = [
        [
            'name' => 'facetwp-json',
            'match' => 'window\.FWP_JSON = (.*?);',
            'replacement' => 'window.FWP_JSON = $$JSON_DATA$$;',
        ],
    ];

    /**
     * Decides if the FacetWP integration should be loaded.
     *
     * @return boolean
     */
    public function shouldLoad()
    {
        return is_plugin_active('facetwp/facetwp.php') || is_plugin_active('facetwp/index.php');
    }

    /**
     * Load the integration
     *
     * @return void
     */
    public function init()
    {
        add_filter('facetwp_render_output', [$this, 'translateRenderOutput'], 1000, 1);
    }

    /**
     * Unload the integration
     *
     * @return void
     */
    public function destroy()
    {
        remove_filter('facetwp_render_output', [$this, 'translateRenderOutput'], 1000);
    }

    /**
     * Hooked into FacetWP's render output filter to translate the facetwp-output
     * fragment.
     *
     * @param array $output The rendered output from FacetWP
     *
     * @see https://facetwp.com/help-center/developers/hooks/output-hooks/facetwp_render_output/
     *
     * @return string The translated FacetWP output HTML
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
                'key' => 'template',
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

        $html_fragments = FragmentHandler::intoHTMLFragments('facetwp-filters', 'render-output', [
            'mode' => 'auto',
            'fragments' => $fragments,
        ]);

        $html_content = '<html><head></head><body>';
        $html_content .= $html_fragments;
        $html_content .= '</body></html>';

        $translated_result = $this->translateFragments($html_content, $language, '/facetwp-refresh');

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

        // Get $translated_fragments['facetwp-filters']['render-output']
        if (!isset($translated_fragments['facetwp-filters'])) {
            return $output;
        }

        if (!isset($translated_fragments['facetwp-filters']['render-output'])) {
            return $output;
        }

        $tl_json_frag = $translated_fragments['facetwp-filters']['render-output'];
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
