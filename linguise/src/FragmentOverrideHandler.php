<?php

namespace Linguise\WordPress;

use Linguise\WordPress\FragmentBase;
use Linguise\WordPress\FragmentHandler;
use Linguise\WordPress\HTMLHelper;
use Linguise\Vendor\JsonPath\JsonObject;
use Linguise\Vendor\Linguise\Script\Core\Debug;

defined('ABSPATH') || die('');

/**
 * Fragment override mode handler.
 */
class FragmentOverrideHandler extends FragmentBase
{
    /**
     * Try to match the script with the override list.
     *
     * @param \DOMNode|\DOMElement $script    The script to be matched
     * @param string               $html_data The raw HTML data to be checked
     *
     * @return array|null
     */
    public static function tryMatchWithOverride($script, $html_data)
    {
        $script_content = $script->textContent;
        $script_id = $script->getAttribute('id');

        $override_list = self::getJSONOverrideMatcher($html_data);

        $multi_matched = [];
        foreach ($override_list as $override_item) {
            $script_content = HTMLHelper::unclobberCdataInternal($script_content);
            if (isset($override_item['mode']) && $override_item['mode'] === 'app_json') {
                // If mode is app_json and key is the same
                if (isset($override_item['key']) &&  $override_item['key'] === $script_id) {
                    $json_data = json_decode($script_content, true);
                    $collected_temp = FragmentHandler::collectFragmentFromJson($json_data);
                    if (!empty($collected_temp)) {
                        return [
                            'name' => $override_item['name'],
                            'fragments' => $collected_temp,
                        ];
                    }
                }

                continue;
            }

            if (isset($override_item['key']) && $override_item['key'] !== $script_id) {
                // If the key is set and it's not the same, then we skip
                continue; // @codeCoverageIgnore
            }

            $match_res = preg_match('/' . $override_item['match'] . '/s', $script_content, $match);
            if ($match_res === false || $match_res === 0) {
                continue;
            }

            // since it matches, get the JSON value.
            $match_index = 1;
            if (isset($override_item['position'])) {
                $match_index = $override_item['position'];
            }

            $matched = $match[$match_index];

            if (isset($override_item['encode']) && $override_item['encode']) {
                // decode the matched string
                $matched = urldecode($matched);
            }

            $json_data = json_decode($matched, true);

            // collect the fragment
            $is_strict = false;
            if (isset($override_item['strict']) && $override_item['strict']) {
                $is_strict = true;
            }

            $collected_temp = FragmentHandler::collectFragmentFromJson($json_data, $is_strict);
            if (!empty($collected_temp)) {
                $multi_matched[] = [
                    'name' => $override_item['name'],
                    'fragments' => $collected_temp,
                ];
            }
        }

        if (empty($multi_matched)) {
            return null;
        }

        return $multi_matched;
    }

    /**
     * Apply the translated fragments for the override.
     *
     * @param string $html_data      The HTML data to be injected
     * @param string $fragment_name  The name of the fragment, e.g. 'woocommerce'
     * @param string $fragment_param The param of the fragment, e.g. 'woocommerce$$attr-1'
     * @param array  $fragment_info  The array of fragments to be injected, from intoJSONFragments
     *
     * @return string
     */
    public static function applyTranslatedFragmentsForOverride($html_data, $fragment_name, $fragment_param, $fragment_info)
    {
        $fragment_matcher = self::getJSONOverrideMatcher($html_data);

        // Find the one that match $fragment_name
        $matched_fragment = null;
        foreach ($fragment_matcher as $fragment_match) {
            if ($fragment_match['name'] === $fragment_name) {
                $matched_fragment = $fragment_match;
                break;
            }
        }

        if (is_null($matched_fragment)) {
            return $html_data;
        }

        if (isset($matched_fragment['mode']) && $matched_fragment['mode'] === 'app_json') {
            if (!isset($matched_fragment['key'])) {
                return $html_data;
            }

            $match_res = preg_match('/<script.*? id=["\']' . $matched_fragment['key'] . '["\'].+?>{(.*)}<\/script>/s', $html_data, $html_matches);

            if ($match_res === false || $match_res === 0) {
                return $html_data;
            }

            $match_data = $html_matches[1];
            $json_data = new JsonObject(json_decode('{' . $match_data . '}', true));
            foreach ($fragment_info['fragments'] as $fragment) {
                if (isset($fragment['skip']) && $fragment['skip']) {
                    // If skip is true, then don't replace this fragment (but remove it)
                    continue;
                }
                $decoded_key = self::unwrapKey($fragment['key']);
                $merged_key = strpos($decoded_key, '[') === 0 ? '$' . $decoded_key : '$.' . $decoded_key;
                try {
                    $json_data->set($merged_key, $fragment['value']);
                } catch (\Linguise\Vendor\JsonPath\InvalidJsonPathException $e) { // @codeCoverageIgnore
                    Debug::log('Failed to set key in override: ' . $merged_key . ' -> ' . $e->getMessage()); // @codeCoverageIgnore
                }
            }

            $replaced_json = $json_data->getJson();

            $html_data = str_replace('{' . $match_data . '}', $replaced_json, $html_data);
        } else {
            $match_res = preg_match('/' . $matched_fragment['match'] . '/s', $html_data, $html_matches);
            if ($match_res === false || $match_res === 0) {
                return $html_data;
            }

            $index_match = 1;
            if (isset($matched_fragment['position'])) {
                $index_match = $matched_fragment['position'];
            }

            $before_match = $html_matches[$index_match];
            $should_encode = isset($matched_fragment['encode']) && $matched_fragment['encode'];
            if ($should_encode) {
                $before_match = urldecode($before_match);
            }

            $json_data = new JsonObject(json_decode($before_match, true));
            foreach ($fragment_info['fragments'] as $fragment) {
                if (isset($fragment['skip']) && $fragment['skip']) {
                    // If skip is true, then don't replace this fragment (but remove it)
                    continue;
                }
                $decoded_key = self::unwrapKey($fragment['key']);
                $merged_key = strpos($decoded_key, '[') === 0 ? '$' . $decoded_key : '$.' . $decoded_key;
                try {
                    $json_data->set($merged_key, $fragment['value']);
                } catch (\Linguise\Vendor\JsonPath\InvalidJsonPathException $e) { // @codeCoverageIgnore
                    Debug::log('Failed to set key in override: ' . $merged_key . ' -> ' . $e->getMessage()); // @codeCoverageIgnore
                }
            }

            $replaced_json = $json_data->getJson();

            $replaced_json = apply_filters('linguise_after_apply_translated_fragments_override', $replaced_json, $fragment_name);

            if ($should_encode) {
                $replaced_json = rawurlencode($replaced_json);
            }
            $subst_ptrn = $matched_fragment['replacement'];
            $subst_ptrn = str_replace('$$JSON_DATA$$', $replaced_json, $subst_ptrn);

            $replacement = preg_replace('/' . $matched_fragment['match'] . '/', $subst_ptrn, $html_data, 1, $count);
            if ($count) {
                $html_data = $replacement;
            }
        }

        return $html_data;
    }
}
