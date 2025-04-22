<?php

namespace Linguise\WordPress;

use Linguise\Vendor\JsonPath\JsonObject;
use Linguise\WordPress\FragmentHandler;
use Linguise\Vendor\Linguise\Script\Core\Debug;

defined('ABSPATH') || die('');

/**
 * AttributeHandler, derived from FragmentHandler
 *
 * This class handle parsing and injecting
 * JSON data from HTML attributes.
 */
class AttributeHandler extends FragmentHandler
{
    /**
     * Get attribute JSON fragment matching
     *
     * Similar to `getJSONOverrideMatcher()` but we use DOMDocument to do the matching
     * - name: The name of the plugin, e.g. 'wpca', must be unique
     * - key: the data attribute to match, e.g. 'data-wpca'
     * - encode: If the JSON data is URL encoded or not, default to false (optional)
     * - strict: If the JSON data should be strict or not, default to false (optional)
     *
     * @param string $html_data The HTML data input
     *
     * @return array The array of JSON to match with fragment
     */
    private static function getMatcher($html_data)
    {
        $current_list = [];

        if (defined('LINGUISE_WP_PLUGIN_TEST_MODE')) {
            $current_list[] = [
                'name' => 'linguise-demo-test',
                'key' => 'data-linguise-demo',
            ];
        }

        if (function_exists('apply_filters')) {
            $current_list = apply_filters('linguise_fragment_attributes', $current_list, $html_data);
        }

        return $current_list;
    }

    /**
     * Find the matcher by name.
     *
     * @param string $matcher_name The name of the matcher to find
     * @param array  $matchers     The list of matchers to search in
     *
     * @return array|null The matcher if found, null otherwise
     */
    private static function findMatcher($matcher_name, $matchers)
    {
        foreach ($matchers as $matcher) {
            if ($matcher['name'] === $matcher_name) {
                return $matcher;
            }
        }

        return null;
    }

    /**
     * Parse the HTML input or data into the fragments.
     *
     * @param string $html_data The HTML data to be parsed
     *
     * @return array
     */
    public static function findWPFragments(&$html_data)
    {
        $html_dom = self::loadHTML($html_data);

        $matchers = self::getMatcher($html_data);
        $internal_counter = 0;

        $all_fragments = [];
        foreach ($matchers as $matcher) {
            $attr_html = $html_dom->querySelector('[' . $matcher['key'] . ']');
            if (!empty($attr_html)) {
                $key_data = $attr_html->getAttribute($matcher['key']);
                if (empty($key_data)) {
                    continue;
                }

                // Is the data URL encoded?
                $should_encode = isset($matched['encode']) && $matched['encode'];
                if ($should_encode) {
                    $key_data = urldecode($key_data);
                }

                $json_data = json_decode($attr_html->getAttribute($matcher['key']), true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    continue;
                }

                $is_strict = isset($matcher['strict']) && $matcher['strict'];

                $collected_temp = self::collectFragmentFromJson($json_data, $is_strict);
                if (!empty($collected_temp)) {
                    $int_marker = $matcher['name'] . '-' . $internal_counter;
                    $attr_html->setAttribute('data-linguise-attribute-matched', $int_marker);
                    $all_fragments[$matcher['name']][$int_marker] = [
                        'mode' => 'attribute',
                        'fragments' => $collected_temp,
                    ];
                    $internal_counter++;
                }
            }
        }

        if ($internal_counter > 0) {
            $html_data = $html_dom->saveHTML();
        }

        Debug::log('AttributeHandler -> Collected: ' . json_encode($all_fragments, JSON_PRETTY_PRINT));

        return $all_fragments;
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
        Debug::log('AttributeHandler -> Injecting: ' . json_encode($fragments, JSON_PRETTY_PRINT));

        $html_dom = self::loadHTML($html_data);

        $queued_deletions = [];
        $fragment_matchers = self::getMatcher($html_data);
        foreach ($fragments as $fragment_name => $fragment_jsons) {
            $matched = self::findMatcher($fragment_name, $fragment_matchers);
            foreach ($fragment_jsons as $fragment_param => $fragment_list) {
                $attr_html = $html_dom->querySelector('[data-linguise-attribute-matched="' . $fragment_param . '"]');
                $queued_deletions[] = $fragment_list['fragments'];
                if (empty($attr_html)) {
                    continue;
                }
                if (empty($matched)) {
                    continue;
                }

                $match_data = $attr_html->getAttribute($matched['key']);
                if (empty($match_data)) {
                    continue;
                }

                $should_encode = isset($matched['encode']) && $matched['encode'];
                if ($should_encode) {
                    $match_data = urldecode($match_data);
                }

                $json_decoded = json_decode($match_data, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    continue;
                }

                $json_data = new JsonObject($json_decoded);
                foreach ($fragment_list['fragments'] as $fragment) {
                    try {
                        $json_data->set('$.' . self::decodeKeyName($fragment['key']), $fragment['value']);
                    } catch (\Linguise\Vendor\JsonPath\InvalidJsonPathException $e) {
                        Debug::log('Failed to set key in attributes: ' . self::decodeKeyName($fragment['key']) . ' -> ' . $e->getMessage());
                    }
                }

                $replaced_json = $json_data->getJson();
                if ($should_encode) {
                    $replaced_json = rawurlencode($replaced_json);
                }

                $attr_html->setAttribute($matched['key'], $replaced_json);
                $attr_html->removeAttribute('data-linguise-attribute-matched');
            }
        }

        $html_data = $html_dom->saveHTML();
        foreach ($queued_deletions as $deletion) {
            foreach ($deletion as $fragment) {
                $html_data = str_replace($fragment['match'], '', $html_data);
            }
        }

        return $html_data;
    }
}
