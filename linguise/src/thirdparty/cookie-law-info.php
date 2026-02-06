<?php

namespace Linguise\WordPress\Integrations;

use Linguise\WordPress\FragmentHandler;
use Linguise\WordPress\HTMLHelper;

defined('ABSPATH') || die('');

/**
 * Cookie law info integration
 */
class CookieLawInfoIntegration extends LinguiseBaseIntegrations
{
    /**
     * Plugin name
     *
     * @var string
     */
    public static $name = 'CookieYes / Cookie Law Info';

    /**
     * A collection of fragment keys that will be translated
     *
     * @var array{key:string, mode:'exact'|'path'|'wildcard'|'regex'|'regex_full',kind:'allow'|'deny'}
     */
    protected static $fragment_keys = [
        [
            'key' => '_shortCodes\.\d+\..*', // TODO: Figure out how to allow content later
            'mode' => 'regex_full',
            'kind' => 'deny',
        ],
        [
            'key' => '_categories\.\d+\.name',
            'mode' => 'regex_full',
            'kind' => 'allow',
        ]
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
        return is_plugin_active('cookie-law-info/cookie-law-info.php');
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
        add_filter('linguise_after_fragment_collection', [$this, 'hookLinguiseCliAfterCollection'], 20, 3);
        add_filter('linguise_after_fragment_translation', [$this, 'hookLinguiseCliAfterTranslation'], 20, 2);
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
        remove_filter('linguise_after_fragment_collection', [$this, 'hookLinguiseCliAfterCollection'], 20);
        remove_filter('linguise_after_fragment_translation', [$this, 'hookLinguiseCliAfterTranslation'], 20);
    }

    /**
     * Add more fragment specifically for Cookie Law template scripts
     *
     * @param array        $fragments   The fragments to be translated
     * @param string       $html_string The HTML string containing the fragments
     * @param \DOMDocument $html_dom    The DOMDocument object of the HTML
     *
     * @return array The modified fragments to be translated
     */
    public function hookLinguiseCliAfterCollection($fragments, $html_string, $html_dom)
    {
        require_once realpath(__DIR__ . '/../HTMLHelper.php'); // in case not loaded yet
        require_once realpath(__DIR__ . '/../FragmentBase.php'); // in case not loaded yet
        require_once realpath(__DIR__ . '/../FragmentHandler.php'); // in case not loaded yet

        $scripts = $html_dom->getElementsByTagName('script');
        foreach ($scripts as $script) {
            // Check id and type
            $script_id = $script->getAttribute('id');
            $script_type = $script->getAttribute('type');
            if (strpos($script_id, 'ckyBanner') !== false && strpos($script_id, 'Template') !== false) {
                $script_content = HTMLHelper::unclobberCdataInternal($script->textContent);
    
                // This is already an HTML template script, add it directly
                if (!isset($fragments['cli-cky-template'])) {
                    $fragments['cli-cky-template'] = [];
                }
                if (!isset($fragments['cli-cky-template'][$script_id])) {
                    // Replace any script tag inner content with linguise-script to avoid issues with HTML parsing
                    $script_content = str_replace('<script', '<linguise-script', $script_content);
                    $script_content = str_replace('</script>', '</linguise-script>', $script_content);
                    
                    $fragments['cli-cky-template'][$script_id] = [
                        'mode' => 'skip',
                        'fragments' => [
                            [
                                'key' => 'content',
                                'value' => $script_content,
                                'format' => 'html-main',
                                'skip' => true,
                            ]
                        ]
                    ];
                }
            }

            if (strpos($script_id, 'cookie-law-info-js-extra') !== false) {
                $script_content = HTMLHelper::unclobberCdataInternal($script->textContent);

                if (preg_match('/var _ckyConfig = (.*?);/s', $script_content, $matches)) {
                    $json_data = $matches[1];

                    $parsed_json = json_decode($json_data, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        continue;
                    }
                    $collected = FragmentHandler::collectFragmentFromJson($parsed_json, true);
                    foreach ($collected as &$frag) {
                        $frag['skip'] = true;
                    }

                    // Add fragment for _ckyConfig
                    if (!isset($fragments['cli-cky-config'])) {
                        $fragments['cli-cky-config'] = [];
                    }

                    if (!isset($fragments['cli-cky-config']['_ckyConfig'])) {
                        $fragments['cli-cky-config']['_ckyConfig'] = [
                            'mode' => 'skip',
                            'fragments' => $collected,
                        ];
                    }
                }
            }
        }

        return $fragments;
    }

    /**
     * Apply back the manually adjusted HTML after fragment translation
     *
     * @param string $html_data The HTML data after fragment translation
     * @param array  $fragments The fragments that were translated
     *
     * @return string The modified HTML data after applying back the manually adjusted HTML
     */
    public function hookLinguiseCliAfterTranslation($html_data, $fragments)
    {
        require_once realpath(__DIR__ . '/../HTMLHelper.php'); // in case not loaded yet
        require_once realpath(__DIR__ . '/../FragmentBase.php'); // in case not loaded yet
        require_once realpath(__DIR__ . '/../FragmentHandler.php'); // in case not loaded yet

        foreach ($fragments as $fragment_name => $fragment_jsons) {
            if ($fragment_name === 'cli-cky-template') {
                foreach ($fragment_jsons as $fragment_param => $fragment_list) {
                    $mode = $fragment_list['mode'];
                    if ($mode !== 'skip') {
                        continue;
                    }

                    $fragment_data = $fragment_list['fragments'][0];
                    $tled_value = $fragment_data['value'];

                    $modified_script = html_entity_decode(HTMLHelper::unprotectEntity($tled_value), ENT_QUOTES, 'UTF-8');
                    $modified_script = str_replace('<linguise-script', '<script', $modified_script);
                    $modified_script = str_replace('</linguise-script>', '</script>', $modified_script);

                    // Find the HTML data to replace
                    $script_re = '/<script[^>]*id=["\']' . preg_quote($fragment_param, '/') . '["\'][^>]*>(.*?)<\/script>/is';
                    $html_data = preg_replace_callback($script_re, function ($matches) use ($modified_script) {
                        $original_script = $matches[0];
                        return str_replace($matches[1], $modified_script, $original_script);
                    }, $html_data);
                }
            } elseif ($fragment_name === 'cli-cky-config') {
                foreach ($fragment_jsons as $fragment_param => $fragment_list) {
                    $mode = $fragment_list['mode'];
                    if ($mode !== 'skip') {
                        continue;
                    }

                    // find the text in the HTML
                    if (preg_match('/var _ckyConfig = (.*?);/s', $html_data, $matches)) {
                        $original_json = $matches[1];
                        $parsed_json = json_decode($original_json, true);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            continue;
                        }

                        // Remove the $skip key
                        foreach ($fragment_list['fragments'] as &$frag) {
                            if (isset($frag['skip'])) {
                                unset($frag['skip']);
                            }
                        }

                        $fragment_data = $fragment_list['fragments'];
                        $reconstructed_json = FragmentHandler::applyTranslatedFragmentsForAuto($parsed_json, $fragment_data);
                        $json_data = json_encode($reconstructed_json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

                        // Find the HTML data to replace
                        $script_re = '/(var _ckyConfig = )(.*?);/s';
                        $html_data = preg_replace_callback($script_re, function ($matches) use ($json_data) {
                            return $matches[1] . $json_data . ';';
                        }, $html_data);
                    }
                }
            }
        }

        return $html_data;
    }
}
