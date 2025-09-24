<?php

namespace Linguise\WordPress;

use Linguise\WordPress\FragmentBase;
use Linguise\WordPress\Helper;
use Linguise\WordPress\HTMLHelper;
use Linguise\Vendor\JsonPath\JsonObject;
use Linguise\Vendor\Linguise\Script\Core\Debug;

defined('ABSPATH') || die('');

/**
 * Class FragmentHandler
 */
class FragmentHandler extends FragmentBase
{
    /**
     * Regex/matcher for our custom HTML fragment
     *
     * @var string
     */
    protected static $frag_html_match = '/<(div|a|linguise-main|img) class="linguise-fragment" data-fragment-name="([^"]*)" data-fragment-param="([^"]*)" data-fragment-key="([^"]*)" data-fragment-format="(link|html|html-main|text|media-img|media-imgset)" data-fragment-mode="(auto|override|attribute)"(?: data-fragment-extra-id="([^"]*)")?(?: (?:href|src|srcset)="([^"]*)")?>(.*?)<\/\1>/si';
    /**
     * Regex/matcher for our custom HTML fragment
     *
     * This version support self-closing tag, e.g. <img>
     *
     * @var string
     */
    protected static $frag_html_match_self_close = '/<(img) class="linguise-fragment" data-fragment-name="([^"]*)" data-fragment-param="([^"]*)" data-fragment-key="([^"]*)" data-fragment-format="(link|html|html-main|text|media-img|media-imgset)" data-fragment-mode="(auto|override|attribute)"(?: data-fragment-extra-id="([^"]*)")?(?: (?:href|src|srcset)="([^"]*)")?\s*\/?>/si';

    /**
     * Collect the fragment from the JSON data.
     *
     * @param string  $key                 The key of the fragment
     * @param mixed   $value               The value of the fragment
     * @param array   $collected_fragments The array of collected fragments
     * @param string  $current_key         The current key of the fragment
     * @param boolean $strict              If strict mode, only "allow"-ed fragments will be collected
     * @param integer $array_index         The index of the array, if it's an array
     *
     * @return array - The array of collected fragments
     */
    private static function collectFragment($key, $value, $collected_fragments, $current_key, $strict = false, $array_index = null)
    {
        $use_key = self::wrapKey($key);
        if ($array_index !== null) {
            $use_key .= '.' . $array_index;
        }
        if (!empty($current_key)) {
            if ($key === $use_key) {
                $use_key = '.' . $use_key;
            }

            $use_key = $current_key . $use_key;
        }

        if (Helper::isActualObject($value)) {
            $collected_fragments = self::collectFragmentFromJson($value, $strict, $collected_fragments, $use_key);
        } elseif (is_array($value)) {
            for ($arr_i = 0; $arr_i < count($value); $arr_i++) { // phpcs:ignore Generic.CodeAnalysis.ForLoopWithTestFunctionCall.NotAllowed
                $inner_value = $value[$arr_i];
                $collected_fragments = self::collectFragment('', $inner_value, $collected_fragments, $use_key, $strict, $arr_i);
            }
        } elseif (is_string($value)) {
            // By default, we assume "text" for now.
            $allowed_filters = self::isKeyAllowed($key, $use_key);
            if ($strict && ($allowed_filters !== true && !is_string($allowed_filters))) {
                return $collected_fragments;
            }
            if (!is_null($allowed_filters) && $allowed_filters === false) {
                // If it's not null and it's false, then we don't need to check further
                return $collected_fragments;
            }
            $tl_string = self::isTranslatableString($value);
            $tl_link = self::isStringLink($value);
            $tl_dom = self::isHTMLElement($value);

            $format = 'text';
            if ($tl_link) {
                $format = 'link';
                if (self::isImageLink($value)) {
                    $format = 'media-img';
                }
            } elseif (is_string($tl_dom)) {
                $format = $tl_dom;
            }
            if (is_string($allowed_filters)) {
                $format = $allowed_filters;
            }
            if ($tl_string || $tl_link || is_string($tl_dom) || $allowed_filters) {
                $collected_fragments[] = [
                    'key' => $use_key,
                    'value' => $value,
                    'format' => $format,
                ];
            }
        }

        return $collected_fragments;
    }

    /**
     * A recursive function that iterates through the json data and collects the fragments
     *
     * @param array   $json_data           The JSON data to be iterated
     * @param boolean $strict              Default to false, if True this will only collect "allow"-ed fragments
     * @param array   $collected_fragments Default to empty array, when it's being called inside the function it will be appended for the parent key
     * @param string  $current_key         Default to empty string, when it's being called inside the function it will be appended for the parent key
     *
     * @return array
     */
    public static function collectFragmentFromJson($json_data, $strict = false, $collected_fragments = null, $current_key = null)
    {
        // set default if null
        if ($collected_fragments === null) {
            $collected_fragments = [];
        }
        if ($current_key === null) {
            $current_key = '';
        }
        foreach ($json_data as $key => $value) {
            $collected_fragments = self::collectFragment($key, $value, $collected_fragments, $current_key, $strict);
        }
        return $collected_fragments;
    }

    /**
     * Convert the array fragments created previously into a HTML
     * data that will be injected into the page.
     *
     * @param string $fragment_name  The name of the fragment, e.g. 'woocommerce'
     * @param string $fragment_param The param of the fragment, e.g. 'params' used in the actual variables
     * @param array  $json_fragments The array of fragments, generated with collectFragmentFromJson
     *
     * @return string - The HTML data that will be injected into the page
     */
    public static function intoHTMLFragments($fragment_name, $fragment_param, $json_fragments)
    {
        $html = '';
        foreach ($json_fragments['fragments'] as $fragment) {
            $tag = 'div';
            if ($fragment['format'] === 'link') {
                $tag = 'a';
            }
            $frag_value = $fragment['value'];
            if ($fragment['format'] === 'html') {
                // check if html has div, if yes change it to divlinguise
                $frag_value = preg_replace('/<div(.*?)>(.*?)<\/div>$/si', '<linguise-div$1>$2</linguise-div>', $frag_value, 1);
            }
            if ($fragment['format'] === 'html-main') {
                $tag = 'linguise-main';
            }
            if ($fragment['format'] === 'media-img' || $fragment['format'] === 'media-imgset') {
                $tag = 'img';
            }
            $html .= '<' . $tag . ' class="linguise-fragment" data-fragment-name="' . $fragment_name . '" data-fragment-param="' . $fragment_param . '" data-fragment-key="';
            $html .= $fragment['key'] . '" data-fragment-format="' . $fragment['format'] . '" data-fragment-mode="' . $json_fragments['mode'] . '"';
            if ($json_fragments['mode'] === 'attribute' && isset($json_fragments['attribute'])) {
                $html .= ' data-fragment-extra-id="' . $json_fragments['attribute'] . '"';
            }
            if ($fragment['format'] === 'link') {
                $html .= ' href="' . $fragment['value'] . '">';
            } elseif ($fragment['format'] === 'media-img' || $fragment['format'] === 'media-imgset') {
                $tag_select = $fragment['format'] === 'media-imgset' ? 'srcset' : 'src';
                $html .= ' ' . $tag_select . '="' . $fragment['value'] . '">';
            } else {
                $html .= '>' . $frag_value;
            }
            $html .= '</' . $tag . '>' . "\n";
        }

        return trim($html);
    }

    /**
     * Convert back the translated fragments into the original JSON data
     *
     * @param string $html_fragments The HTML fragments that was injected into the page
     *
     * @return array - The array of fragments, generated with collectFragmentFromJson
     */
    public static function intoJSONFragments($html_fragments)
    {
        $fragments = [];
        // Let's just use regex to get anything with "linguise-fragment" class
        preg_match_all(self::$frag_html_match, $html_fragments, $std_matches, PREG_SET_ORDER, 0);
        preg_match_all(self::$frag_html_match_self_close, $html_fragments, $self_close_matches, PREG_SET_ORDER, 0);
        $matches = array_merge($std_matches, $self_close_matches);
        foreach ($matches as $match) {
            $fragment_name = $match[2];
            $fragment_param = $match[3];
            $fragment_key = $match[4];
            $fragment_format = $match[5];
            $fragment_mode = $match[6];

            $fragment_value = isset($match[9]) ? $match[9] : '';

            if ($fragment_format === 'link' || $fragment_format === 'media-img' || $fragment_format === 'media-imgset') {
                $fragment_value = $match[8];
            }

            if ($fragment_format === 'html') {
                // parse back the linguise-dev
                $fragment_value = preg_replace('/<linguise-div(.*?)>(.*?)<\/linguise-div>$/si', '<div$1>$2</div>', $fragment_value, 1);
            } elseif ($fragment_format === 'html-main') {
                // parse back the linguise-main
                $fragment_value = preg_replace('/<linguise-main>(.*?)<\/linguise-main>$/si', '$1', $fragment_value, 1);
            }

            // Strip any data-editor="linguise" wrap
            $result_strip = preg_replace('/^\s*<span data-editor="linguise" data-linguise-hash="(?:.*?)">(.*?)<\/span>$/', '$1', $fragment_value, 1);
            if (!empty($result_strip)) {
                $fragment_value = $result_strip;
            }

            if (!isset($fragments[$fragment_name])) {
                $fragments[$fragment_name] = [];
            }
            if (!isset($fragments[$fragment_name][$fragment_param])) {
                $temp_fragment_sets = [
                    'mode' => $fragment_mode,
                    'fragments' => [],
                ];

                if ($fragment_mode === 'attribute' && isset($match[7]) && !empty($match[7])) {
                    $temp_fragment_sets['attribute'] = $match[7];
                }

                $fragments[$fragment_name][$fragment_param] = $temp_fragment_sets;
            }

            // The returned data is encoded HTML entities for non-ASCII characters
            // Decode it back to UTF-8 for link and text, for HTML since it would be rendered
            // the browser will decode it back to UTF-8 automatically
            if ($fragment_format !== 'html' && $fragment_format !== 'html-main') {
                $fragment_value = html_entity_decode($fragment_value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }

            // make it into list for each fragment name
            $fragments[$fragment_name][$fragment_param]['fragments'][] = [
                'key' => $fragment_key,
                'value' => $fragment_value,
                'format' => $fragment_format,
                'match' => $match[0],
            ];
        }

        Debug::log('FragmentHandler -> Dump fragments: ' . json_encode($fragments, JSON_PRETTY_PRINT));

        return $fragments;
    }

    /**
     * === HTML Injector Related ===
     */

    /**
     * Try to match the script with the override list.
     *
     * @param \DOMNode|\DOMElement $script    The script to be matched
     * @param string               $html_data The raw HTML data to be checked
     *
     * @return array|null
     */
    private static function tryMatchWithOverride($script, $html_data)
    {
        $script_content = $script->textContent;
        $script_id = $script->getAttribute('id');

        $override_list = self::getJSONOverrideMatcher($html_data);

        foreach ($override_list as $override_item) {
            $script_content = HTMLHelper::unclobberCdataInternal($script_content);
            if (isset($override_item['mode']) && $override_item['mode'] === 'app_json') {
                // If mode is app_json and key is the same
                if (isset($override_item['key']) &&  $override_item['key'] === $script_id) {
                    $json_data = json_decode($script_content, true);
                    $collected_temp = self::collectFragmentFromJson($json_data);
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
                continue;
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
            $collected_temp = self::collectFragmentFromJson($json_data, $is_strict);
            if (!empty($collected_temp)) {
                return [
                    'name' => $override_item['name'],
                    'fragments' => $collected_temp,
                ];
            }
        }

        return null;
    }

    /**
     * Parse the HTML input or data into the fragments.
     * XXX: Maybe normal regex and not DOMDocument if possible.
     *
     * @param string $html_data The HTML data to be parsed
     *
     * @return array
     */
    public static function findWPFragments(&$html_data)
    {
        $html_dom = HTMLHelper::loadHTML($html_data);
        if (empty($html_dom)) {
            return [];
        }

        $scripts = $html_dom->getElementsByTagName('script');

        $all_fragments = [];
        foreach ($scripts as $script) {
            $attr_id = $script->getAttribute('id');
            $match_res = preg_match('/^(.*)-js-extra$/', $attr_id, $attr_match);
            if ($match_res === false || $match_res === 0) {
                $overridden_temp = self::tryMatchWithOverride($script, $html_data);
                if (is_array($overridden_temp)) {
                    $all_fragments[$overridden_temp['name']][$overridden_temp['name']] = [
                        'mode' => 'override',
                        'fragments' => $overridden_temp['fragments'],
                    ];
                }
                continue;
            }

            $frag_id = $attr_match[1];

            $script_content = HTMLHelper::unclobberCdataInternal($script->textContent);

            $match_res = preg_match('/var ' . str_replace('-', '_', $frag_id) . '_params = (.*);/', $script_content, $json_matches);
            if ($match_res === false || $match_res === 0) {
                $unmatched_res = preg_match_all('/var (.+)_params = (.*);/', $script_content, $json_multi_matches, PREG_SET_ORDER, 0);
                if ($unmatched_res === false || $unmatched_res === 0) {
                    $overridden_temp = self::tryMatchWithOverride($script, $html_data);
                    if (is_array($overridden_temp)) {
                        $all_fragments[$frag_id][$overridden_temp['name']] = [
                            'mode' => 'override',
                            'fragments' => $overridden_temp['fragments'],
                        ];
                    }
                    continue;
                }

                // Since this is might have multiple matches, we need to check each of them
                foreach ($json_multi_matches as $json_multi_match) {
                    $json_par_key = $json_multi_match[1];
                    $json_data = $json_multi_match[2];
                    $collected_temp = self::collectFragmentFromJson(json_decode($json_data, true));
                    if (!empty($collected_temp)) {
                        $all_fragments[$frag_id][$json_par_key] = [
                            'mode' => 'auto',
                            'fragments' => $collected_temp,
                        ];
                    }
                }

                continue;
            }

            $json_data = $json_matches[1];
            $collected_temp = self::collectFragmentFromJson(json_decode($json_data, true));
            if (!empty($collected_temp)) {
                $all_fragments[$frag_id][str_replace('-', '_', $frag_id)] = [
                    'mode' => 'auto',
                    'fragments' => $collected_temp,
                ];
            }
        }

        Debug::log('FragmentHandler -> Collected: ' . json_encode($all_fragments, JSON_PRETTY_PRINT));

        return $all_fragments;
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
                $decoded_key = self::unwrapKey($fragment['key']);
                try {
                    $json_data->set('$.' . $decoded_key, $fragment['value']);
                } catch (\Linguise\Vendor\JsonPath\InvalidJsonPathException $e) {
                    Debug::log('Failed to set key in override: ' . $decoded_key . ' -> ' . $e->getMessage());
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
                $decoded_key = self::unwrapKey($fragment['key']);
                try {
                    $json_data->set('$.' . $decoded_key, $fragment['value']);
                } catch (\Linguise\Vendor\JsonPath\InvalidJsonPathException $e) {
                    Debug::log('Failed to set key in override: ' . $decoded_key . ' -> ' . $e->getMessage());
                }
            }

            $replaced_json = $json_data->getJson();

            if (function_exists('apply_filters')) {
                $replaced_json = apply_filters('linguise_after_apply_translated_fragments_override', $replaced_json, $fragment_name);
            }

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

    /**
     * Apply the translated fragments for the auto mode.
     *
     * @param array $json_data The JSON data to be injected
     * @param array $fragments The array of fragments to be injected, from intoJSONFragments
     *
     * @return array
     */
    public static function applyTranslatedFragmentsForAuto($json_data, $fragments)
    {
        $json_path = new JsonObject($json_data);
        // Warn if $json_data is empty
        if (empty($json_path->getValue())) {
            return false;
        }

        foreach ($fragments as $fragment) {
            // remove the html fragment from the translated page
            $decoded_key = self::unwrapKey($fragment['key']);
            try {
                $json_path->set('$.' . $decoded_key, $fragment['value']);
            } catch (\Linguise\Vendor\JsonPath\InvalidJsonPathException $e) {
                Debug::log('Failed to set key in auto: ' . $decoded_key . ' -> ' . $e->getMessage());
            }
        }

        $replaced_json = $json_path->getValue();
        return $replaced_json;
    }

    /**
     * Clean up the fragments from the HTML data.
     *
     * @param string $html_data The HTML data to be cleaned up
     * @param array  $fragments The array of fragments to be cleaned up, from intoJSONFragments
     *
     * @return string
     */
    protected static function cleanupFragments($html_data, $fragments)
    {
        foreach ($fragments as $fragment) {
            // remove the html fragment from the translated page
            $html_data = str_replace($fragment['match'], '', $html_data);
        }

        return $html_data;
    }

    /**
     * Inject the fragments into the HTML data.
     *
     * @param string $html_data The HTML data to be injected
     * @param array  $fragments The array of fragments to be injected, from intoJSONFragments
     *
     * @return string
     */
    public static function injectTranslatedWPFragments($html_data, $fragments)
    {
        Debug::log('FragmentHandler -> Injecting: ' . json_encode($fragments, JSON_PRETTY_PRINT));

        foreach ($fragments as $fragment_name => $fragment_jsons) {
            foreach ($fragment_jsons as $fragment_param => $fragment_list) {
                $mode = $fragment_list['mode'];

                if (!in_array($mode, ['auto', 'override'])) {
                    continue;
                }

                if ($mode === 'override') {
                    $html_data = self::applyTranslatedFragmentsForOverride($html_data, $fragment_param, $fragment_name, $fragment_list);
                    $html_data = self::cleanupFragments($html_data, $fragment_list['fragments']);
                    continue;
                }

                $id       = preg_quote($fragment_name . '-js-extra', '/');
                $variable = preg_quote($fragment_param . '_params', '/');

                $match_ptrn = '/<script (type=[\'"][\w\/]+[\'"] )?id=[\'"]' . $id . '[\'"]>(.*?)var ' . $variable . ' = \{(.*?)\};(.*?)<\/script>/s';

                preg_match($match_ptrn, $html_data, $html_matches);
                if (empty($html_matches)) {
                    $html_data = self::cleanupFragments($html_data, $fragment_list['fragments']);
                    continue;
                }

                $replaced_json = self::applyTranslatedFragmentsForAuto(json_decode('{' . $html_matches[3] . '}', true), $fragment_list['fragments']);

                if (function_exists('apply_filters')) {
                    $replaced_json = apply_filters('linguise_after_apply_translated_fragments_auto', $replaced_json, $fragment_name);
                }

                if ($replaced_json === false) {
                    throw new \LogicException('FragmentHandler -> Injection -> ' . $fragment_name . '/' . $fragment_param . ' -> JSON data is empty!');
                }

                $html_data = self::cleanupFragments($html_data, $fragment_list['fragments']);
                $subst_ptrn = '<script $1id="' . $fragment_name . '-js-extra">$2var ' .  $fragment_param . '_params = ' . json_encode($replaced_json) . ';$4</script>';

                $replacement = preg_replace($match_ptrn, $subst_ptrn, $html_data, 1, $count);
                if ($count) {
                    $html_data = $replacement;
                }
            }
        }

        $mod_html_data = apply_filters('linguise_after_fragment_translation', $html_data);
        if (!empty($mod_html_data)) {
            $html_data = $mod_html_data;
        }
        return $html_data;
    }
}
