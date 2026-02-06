<?php

namespace Linguise\WordPress\Integrations;

use Linguise\WordPress\FragmentHandler;
use Linguise\WordPress\Helper as WPHelper;

defined('ABSPATH') || die('');

/**
 * Elementor Pro integration class
 *
 * Mainly hooking to some AJAX response
 */
class ElementorProIntegration extends LinguiseBaseIntegrations
{
    /**
     * Plugin name
     *
     * @var string
     */
    public static $name = 'Elementor Pro';

    /**
     * A collection of fragment keys that will be translated
     *
     * @var array{key:string, mode:'exact'|'path'|'wildcard'|'regex'|'regex_full',kind:'allow'|'deny'}
     */
    protected static $fragment_keys = [
        [
            'key' => '^data\.message$',
            'mode' => 'regex_full',
            'kind' => 'allow',
            'cast' => 'html-main',
        ],
        [
            'key' => '^data\.data\.redirect_url$',
            'mode' => 'regex_full',
            'kind' => 'allow',
            'cast' => 'link',
        ],
        [
            'key' => '^data\.errors\.(?:[\d]+|[\w_\-]+)$',
            'mode' => 'regex_full',
            'kind' => 'allow',
            'cast' => 'html-main',
        ]
    ];

    /**
     * Decides if the integration should be loaded.
     *
     * @return boolean
     */
    public function shouldLoad()
    {
        return is_plugin_active('elementor-pro/elementor-pro.php') && is_plugin_active('elementor/elementor.php');
    }

    /**
     * Load the integration.
     *
     * @return void
     */
    public function init()
    {
        add_action('wp_ajax_elementor_pro_forms_send_form', function () {
            ob_start(function ($buffer) {
                return $this->hookTranslateJSONResponseFragments($buffer, 'send-forms');
            });
        }, 5);

        add_action('wp_ajax_nopriv_elementor_pro_forms_send_form', function () {
            ob_start(function ($buffer) {
                return $this->hookTranslateJSONResponseFragments($buffer, 'send-forms');
            });
        }, 5);
    }

    /**
     * Unload the integration
     *
     * @return void
     */
    public function destroy()
    {
        // Stub
    }

    /**
     * Reload the integration
     *
     * @return void
     */
    public function reload()
    {
        $this->destroy();
        // Don't re-init (for now)
        // $this->init();
    }

    /**
     * Translate Elementor JSON response fragments
     *
     * @param mixed  $data      The data returned from the fragment override
     * @param string $ajax_name The AJAX method being called
     *
     * @return mixed
     */
    public function hookTranslateJSONResponseFragments($data, $ajax_name)
    {
        if (empty($data)) {
            return $data;
        }

        $language = WPHelper::getLanguage();

        // For referer, ensure it's POST request
        if ($language === null && strtoupper($_SERVER['REQUEST_METHOD']) === 'POST') {
            $language = WPHelper::getLanguageFromReferer();
        }

        if (empty($language)) {
            return $data;
        }

        $parsed_json = json_decode($data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $data; // Return original data if JSON parsing fails
        }

        $fragments = FragmentHandler::collectFragmentFromJson($parsed_json, true);
        if (empty($fragments)) {
            return $data; // Return original data if no fragments found
        }

        $html_fragments = FragmentHandler::intoHTMLFragments('elementor-pro', $ajax_name, [
            'mode' => 'auto',
            'fragments' => $fragments,
        ]);

        $html_content = '<html><head></head><body>';
        $html_content .= $html_fragments;
        $html_content .= '</body></html>';

        $translated_result = $this->translateFragments($html_content, $language, '/');
        if ($translated_result === false) {
            return $data; // Return original data if translation fails
        }

        if (isset($translated_result->redirect)) {
            // Somehow we got this...?
            return $data;
        }

        $translated_fragments = FragmentHandler::intoJSONFragments($translated_result->content);
        if (empty($translated_fragments)) {
            return $data;
        }

        if (!isset($translated_fragments['elementor-pro'])) {
            return $data;
        }

        if (!isset($translated_fragments['elementor-pro'][$ajax_name])) {
            return $data;
        }

        $tl_json_frag = $translated_fragments['elementor-pro'][$ajax_name];
        if (empty($tl_json_frag)) {
            return $data;
        }

        $tl_json_frag_list = $tl_json_frag['fragments'];
        if (empty($tl_json_frag_list)) {
            return $data;
        }

        $replaced_content = FragmentHandler::applyTranslatedFragmentsForAuto($parsed_json, $tl_json_frag_list);
        if ($replaced_content !== false) {
            $encoded_data = json_encode($replaced_content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($encoded_data === false) {
                return $data; // Return original data if JSON encoding fails
            }
            return $encoded_data; // Return the translated JSON data
        }

        return $data; // Return original data if no replacement was made
    }
}
