<?php

namespace Linguise\WordPress;

use Linguise\WordPress\FragmentBase;
use Linguise\WordPress\Helper;
use Linguise\WordPress\HTMLHelper;
use Linguise\Vendor\Linguise\Script\Core\Debug;

defined('ABSPATH') || die('');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'FragmentOverrideHandler.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'FragmentAutoHandler.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'FragmentI18nHandler.php';

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
    protected static $frag_html_match = '/<(div|a|linguise-main|img) class="linguise-fragment" data-fragment-name="([^"]*)" data-fragment-param="([^"]*)" data-fragment-key="([^"]*)" data-fragment-format="((?:link|html|html-main|text|media-img|media-imgset)(?:-skip)?)" data-fragment-mode="(auto|override|skip|i18n|attribute)"(?: data-fragment-extra-id="([^"]*)")?(?: (?:href|src|srcset)="([^"]*)")?>(.*?)<\/\1>/si';
    /**
     * Regex/matcher for our custom HTML fragment
     *
     * This version support self-closing tag, e.g. <img>
     *
     * @var string
     */
    protected static $frag_html_match_self_close = '/<(img) class="linguise-fragment" data-fragment-name="([^"]*)" data-fragment-param="([^"]*)" data-fragment-key="([^"]*)" data-fragment-format="((?:link|html|html-main|text|media-img|media-imgset)(?:-skip)?)" data-fragment-mode="(auto|override|skip|i18n|attribute)"(?: data-fragment-extra-id="([^"]*)")?(?: (?:href|src|srcset)="([^"]*)")?\s*\/?>/si';

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
                // Extra check
                if (is_string($value) && !empty($value)) {
                    $collected_fragments[] = [
                        'key' => $use_key,
                        'value' => $value,
                        'format' => $format,
                    ];
                }
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
        if (empty($json_data) || !is_array($json_data)) {
            return $collected_fragments;
        }
        foreach ($json_data as $key => $value) {
            $collected_fragments = self::collectFragment($key, $value, $collected_fragments, $current_key, $strict);
        }
        return $collected_fragments;
    }

    /**
     * Protect sprintf templates in the text by wrapping them in a span tag
     *
     * @param string $text The text to be protected
     *
     * @return string The protected text
     */
    private static function protectSprintfTemplates($text)
    {
        $pattern = '/(%(?:\d+\$)?[+-]?(?:\d+)?(?:\.\d+)?[bcdeEfFgGosuxX])/';
        $replacement = '<span-ling translate="no">$1</span-ling>';
        $result = preg_replace($pattern, $replacement, $text);
        if (!empty($result)) {
            return $result;
        }
        return $text; // @codeCoverageIgnore
    }

    /**
     * Restore sprintf templates in the text by unwrapping them from the span tag
     *
     * @param string $text The text to be restored
     *
     * @return string The restored text
     */
    private static function restoreSprintfTemplates($text)
    {
        $pattern = '/<span-ling translate="no">(%(?:\d+\$)?[+-]?(?:\d+)?(?:\.\d+)?[bcdeEfFgGosuxX])<\/span-ling>/';
        $result = preg_replace($pattern, '$1', $text);
        if (!empty($result)) {
            return $result;
        }
        return $text; // @codeCoverageIgnore
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
            $frag_format = $fragment['format'];
            if (isset($fragment['skip']) && $fragment['skip']) {
                $frag_format .= '-skip';
            }

            $html .= '<' . $tag . ' class="linguise-fragment" data-fragment-name="' . $fragment_name . '" data-fragment-param="' . $fragment_param . '" data-fragment-key="';
            $html .= $fragment['key'] . '" data-fragment-format="' . $frag_format . '" data-fragment-mode="' . $json_fragments['mode'] . '"';
            if ($json_fragments['mode'] === 'attribute' && isset($json_fragments['attribute'])) {
                $html .= ' data-fragment-extra-id="' . $json_fragments['attribute'] . '"';
            }
            if ($json_fragments['mode'] === 'i18n' && isset($fragment['index'])) {
                $html .= ' data-fragment-extra-id="' . $fragment['index'] . '"';
            }
            if ($fragment['format'] === 'link') {
                $html .= ' href="' . $fragment['value'] . '">';
            } elseif ($fragment['format'] === 'media-img' || $fragment['format'] === 'media-imgset') {
                $tag_select = $fragment['format'] === 'media-imgset' ? 'srcset' : 'src';
                $html .= ' ' . $tag_select . '="' . $fragment['value'] . '">';
            } else {
                $html .= '>' . self::protectSprintfTemplates($frag_value);
            }
            $html .= '</' . $tag . '>' . "\n";
        }

        return trim($html) . "\n";
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

            $is_skipped = false;
            if (substr($fragment_format, -5) === '-skip') {
                $is_skipped = true;
                $fragment_format = substr($fragment_format, 0, -5);
            }

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

            $fragment_value = self::restoreSprintfTemplates($fragment_value);

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

            $fragment_index = null;
            if ($fragment_mode === 'i18n' && isset($match[7]) && is_numeric($match[7])) {
                $fragment_index = (int)$match[7];
            }

            // make it into list for each fragment name
            $fragments[$fragment_name][$fragment_param]['fragments'][] = [
                'key' => $fragment_key,
                'value' => $fragment_value,
                'format' => $fragment_format,
                'match' => $match[0],
                'index' => $fragment_index,
                'skip' => $is_skipped, // If `skip` is true, then don't replace this fragment (but remove it)
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
        return FragmentOverrideHandler::tryMatchWithOverride($script, $html_data);
    }

    /**
     * Parse the translation block from the script tag.
     *
     * @param \DOMNode|\DOMElement $script The script to be matched
     *
     * @return array|null
     */
    public static function tryMatchTranslationBlock($script)
    {
        return FragmentI18nHandler::tryMatchTranslationBlock($script);
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
            return []; // @codeCoverageIgnore
        }

        $scripts = $html_dom->getElementsByTagName('script');

        $all_fragments = [];
        foreach ($scripts as $script) {
            $attr_id = $script->getAttribute('id');
            $match_res = preg_match('/^(.*)-js-extra$/', $attr_id, $attr_match);
            if ($match_res === false || $match_res === 0) {
                $overridden_temp = self::tryMatchWithOverride($script, $html_data);
                if (is_array($overridden_temp)) {
                    foreach ($overridden_temp as $overridden_temp_item) {
                        $all_fragments[$overridden_temp_item['name']][$overridden_temp_item['name']] = [
                            'mode' => 'override',
                            'fragments' => $overridden_temp_item['fragments'],
                        ];
                    }

                    continue;
                }

                // Try matching -js-translations
                $trans_match_res = preg_match('/^(.*)-js-translations$/', $attr_id, $trans_attr_match);
                if ($trans_match_res !== false && $trans_match_res !== 0) {
                    $translation_block = self::tryMatchTranslationBlock($script);
                    $param_name = $trans_attr_match[1];
                    if (is_array($translation_block)) {
                        $all_fragments[$param_name][$translation_block['name']] = [
                            'mode' => 'i18n',
                            'fragments' => $translation_block['fragments'],
                        ];
                    }
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
                        foreach ($overridden_temp as $overridden_temp_item) {
                            $all_fragments[$frag_id][$overridden_temp_item['name']] = [
                                'mode' => 'override',
                                'fragments' => $overridden_temp_item['fragments'],
                            ];
                        }
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

        // Run filters
        $filtered_fragments = $all_fragments;
        $filtered_fragments = apply_filters('linguise_after_fragment_collection', $filtered_fragments, $html_data, $html_dom);
        if (is_array($filtered_fragments)) {
            $all_fragments = $filtered_fragments;
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
        return FragmentOverrideHandler::applyTranslatedFragmentsForOverride($html_data, $fragment_name, $fragment_param, $fragment_info);
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
        return FragmentAutoHandler::applyTranslatedFragmentsForAuto($json_data, $fragments);
    }

    /**
     * Apply the translated fragments for the i18n mode.
     *
     * @param string $html_data  The HTML data to be injected
     * @param string $param_name The name of the fragment, e.g. 'woocommerce'
     * @param string $param_key  The param of the fragment, e.g. 'messages'
     * @param array  $fragments  The array of fragments to be injected, from intoJSON
     *
     * @return string
     */
    public static function applyTranslatedFragmentsForI18n($html_data, $param_name, $param_key, $fragments)
    {
        return FragmentI18nHandler::applyTranslatedFragmentsForI18n($html_data, $param_name, $param_key, $fragments);
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

                if (!in_array($mode, ['auto', 'override', 'skip', 'i18n'])) {
                    continue;
                }

                if ($mode === 'skip') {
                    $html_data = self::cleanupFragments($html_data, $fragment_list['fragments']);
                    continue;
                }

                if ($mode === 'override') {
                    $html_data = self::applyTranslatedFragmentsForOverride($html_data, $fragment_param, $fragment_name, $fragment_list);
                    $html_data = self::cleanupFragments($html_data, $fragment_list['fragments']);
                    continue;
                }

                if ($mode === 'i18n') {
                    // i18n mode for translation blocks
                    $html_data = self::applyTranslatedFragmentsForI18n($html_data, $fragment_name, $fragment_param, $fragment_list['fragments']);
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

                $replaced_json = apply_filters('linguise_after_apply_translated_fragments_auto', $replaced_json, $fragment_name);

                if ($replaced_json === false) {
                    throw new \LogicException('FragmentHandler -> Injection -> ' . $fragment_name . '/' . $fragment_param . ' -> JSON data is empty!'); // @codeCoverageIgnore
                }

                $html_data = self::cleanupFragments($html_data, $fragment_list['fragments']);
                $subst_ptrn = '<script $1id="' . $fragment_name . '-js-extra">$2var ' .  $fragment_param . '_params = ' . json_encode($replaced_json) . ';$4</script>';

                $replacement = preg_replace($match_ptrn, $subst_ptrn, $html_data, 1, $count);
                if ($count) {
                    $html_data = $replacement;
                }
            }
        }

        $mod_html_data = apply_filters('linguise_after_fragment_translation', $html_data, $fragments);
        if (!empty($mod_html_data)) {
            $html_data = $mod_html_data;
        }
        return $html_data;
    }
}
