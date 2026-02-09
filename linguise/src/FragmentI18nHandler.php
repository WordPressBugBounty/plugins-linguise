<?php

namespace Linguise\WordPress;

use Linguise\WordPress\FragmentBase;
use Linguise\WordPress\HTMLHelper;

defined('ABSPATH') || die('');

/**
 * Fragment i18n mode handler.
 */
class FragmentI18nHandler extends FragmentBase
{
    /**
     * Parse the translation block from the script tag.
     *
     * @param \DOMNode|\DOMElement $script The script to be matched
     *
     * @return array|null
     */
    public static function tryMatchTranslationBlock($script)
    {
        $script_content = HTMLHelper::unclobberCdataInternal($script->textContent);
        $match_res = preg_match('/\(\s*\"([\w\-_]*)\",\s*(\{.*?\})\s*\);/si', $script_content, $json_match);
        if ($match_res === false || $match_res === 0) {
            return null;
        }

        $block_name = $json_match[1];
        $json_data = json_decode($json_match[2], true);
        if (is_null($json_data)) {
            return null;
        }

        // WP uses Jed format for translation blocks
        // https://messageformat.github.io/Jed/
        $selected_locale_data = null;
        if (isset($json_data['locale_data'][$block_name]) && !empty($json_data['locale_data'][$block_name])) {
            $selected_locale_data = $json_data['locale_data'][$block_name];
            return null;
        } elseif (isset($json_data['locale_data']['messages']) && !empty($json_data['locale_data']['messages'])) {
            $block_name = 'messages'; // use messages as block name
            $selected_locale_data = $json_data['locale_data']['messages'];
        }

        if (is_null($selected_locale_data)) {
            return null;
        }

        $collected_temp = [];
        foreach ($selected_locale_data as $msg_key => $msg_values) {
            if ($msg_key === '') {
                // Skip the header
                continue;
            }

            if (is_array($msg_values)) {
                // for use index
                for ($i = 0; $i < count($msg_values); $i++) { // phpcs:ignore Generic.CodeAnalysis.ForLoopWithTestFunctionCall.NotAllowed
                    // hash $msg_key in sha256 to avoid issues with quotes/special chars
                    $key_hashed = hash('sha256', $msg_key);
                    $collected_temp[] = [
                        'key' => $key_hashed,
                        'value' => $msg_values[$i],
                        'format' => 'text',
                        'index' => $i
                    ];
                }
            }
        }

        return [
            'name' => $block_name,
            'fragments' => $collected_temp
        ];
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
        $full_param_name = $param_name . '-js-translations';
        $overall_matchers = preg_match('/id=["\']' . preg_quote($full_param_name) . '["\'].*?>.*?\(\s*function\(\s*domain,\s*translations\s*\)\s*\{.*?\}\s*\)\s*\(\s*["\']([\w_\-]+)["\']\s*,\s*\{(.*?)\}\s*\)\s*;.*?<\/script>/si', $html_data, $html_matches);
        if ($overall_matchers === false || $overall_matchers === 0) {
            return $html_data;
        }

        // Get the JSON data for replacement
        $code_contents = $html_matches[0];
        $match_res = preg_match('/\(\s*\"([\w\-_]+)\",\s*(\{.*?\})\s*\);/si', $code_contents, $json_match);
        if ($match_res === false || $match_res === 0) {
            return $html_data; // @codeCoverageIgnore
        }

        $json_data = json_decode($json_match[2], true);
        if (is_null($json_data)) {
            return $html_data;
        }

        $message_data = $json_data['locale_data'][$param_key];
        if (empty($message_data)) {
            return $html_data;
        }

        $remapped_fragments = [];
        foreach ($fragments as $fragment) {
            if (isset($fragment['skip']) && $fragment['skip']) {
                // If skip is true, then don't replace this fragment (but remove it)
                continue;
            }

            $msg_key = $fragment['key'];
            $msg_index = isset($fragment['index']) ? $fragment['index'] : 0;

            $remapped_fragments[$msg_key] = [];
            $remapped_fragments[$msg_key][$msg_index] = $fragment['value'];
        }

        foreach ($message_data as $msg_key => &$msg_values) {
            if (is_array($msg_values)) {
                $hashed_key = hash('sha256', $msg_key);
                if (!isset($remapped_fragments[$hashed_key])) {
                    continue;
                }

                // for use index
                for ($i = 0; $i < count($msg_values); $i++) { // phpcs:ignore Generic.CodeAnalysis.ForLoopWithTestFunctionCall.NotAllowed
                    if (isset($remapped_fragments[$hashed_key][$i])) {
                        $msg_values[$i] = $remapped_fragments[$hashed_key][$i];
                    }
                }
            }
        }

        $json_data['locale_data'][$param_key] = $message_data;

        // dump back to JSON
        $replaced_json = json_encode($json_data);
        $substr_ptrn = '/(id=["\']' . preg_quote($full_param_name) . '["\'].*?>.*?\(\s*function\(\s*domain,\s*translations\s*\)\s*\{.*?\}\s*\)\s*\(\s*["\']([\w_\-]+)["\']\s*,\s*)(.*?)(\s*\)\s*;.*?<\/script>)/si';

        $find_matchers = preg_replace_callback($substr_ptrn, function ($matches) use ($replaced_json) {
            return $matches[1] . $replaced_json . $matches[4];
        }, $html_data, 1, $count);
        if ($count) {
            $html_data = $find_matchers;
        }

        return $html_data;
    }
}
