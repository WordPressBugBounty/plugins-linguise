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
     * - mode: The mode of the matcher, default to 'json', can be a 'string' or 'json' (optional)
     * - entity: The entity name to match, e.g. 'sc-wpca', default to nil (optional)
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

            $current_list[] = [
                'name' => 'linguise-demo-string',
                'key' => 'data-label',
                'mode' => 'string',
                'matchers' => [
                    [
                        'key' => 'sc-linguise-demo',
                        'type' => 'tag'
                    ]
                ],
            ];
        }

        if (function_exists('apply_filters')) {
            $current_list = apply_filters('linguise_fragment_attributes', $current_list, $html_data);
        }

        // loop through the list and add extra field, use reference
        foreach ($current_list as &$matcher) {
            $name_sanitized = str_replace(' ', '-', $matcher['name']);
            $name_sanitized = sanitize_key($name_sanitized);
            $matcher['attr_ids'] = 'data-linguise-attribute-' . $name_sanitized;
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
     * Process a list of matchers and see if it would match the entity.
     *
     * @param array       $matchers   The list of matchers to process
     * @param \DomElement $entity     The entity to match against
     * @param string      $match_mode The match mode, can be 'any' or 'all', default to 'any'
     *
     * @return boolean True if the entity matches any of the matchers, false otherwise
     */
    private static function processMatcher($matchers, $entity, $match_mode = 'any')
    {
        if (empty($matchers)) {
            return true;
        }

        $matchers_results = [];
        foreach ($matchers as $matcher) {
            $kind = isset($matcher['type']) ? $matcher['type'] : 'attribute';
            if ($kind === 'attribute') {
                $key = isset($matcher['key']) ? $matcher['key'] : '';
                if (!empty($key) && $entity->hasAttribute($key)) {
                    $value_orig = $entity->getAttribute($key);
                    if (isset($matcher['value'])) {
                        $matchers_results[] = $value_orig === $matcher['value'];
                    } else {
                        $matchers_results[] = true;
                    }
                }
            } elseif ($kind === 'tag') {
                // Check with tag name
                if (isset($matcher['key']) && $matcher['key'] === $entity->tagName) {
                    $matchers_results[] = true;
                } else {
                    $matchers_results[] = false;
                }
            } elseif ($kind === 'class') {
                // Values
                $values = isset($matcher['value']) ? $matcher['value'] : [];
                $class_list = explode(' ', $entity->getAttribute('class'));
                $mode = isset($matcher['mode']) ? $matcher['mode'] : 'any';
                if ($mode === 'all') {
                    // If all of the class matches, we return true
                    $matchers_results[] = empty(array_diff($values, $class_list));
                } else {
                    // If any of the class matches, we return true
                    $matchers_results[] = !empty(array_intersect($values, $class_list));
                }
            }
        }

        if ($match_mode === 'all') {
            // If all of the matchers matched, we return true
            return !in_array(false, $matchers_results, true);
        } else {
            // If any of the matchers matched, we return true
            return in_array(true, $matchers_results, true);
        }
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
        if (empty($html_dom)) {
            return [];
        }

        $matchers = self::getMatcher($html_data);
        $internal_counter = 0;

        $all_fragments = [];
        // Loop through all valid DOM elements
        $elements = $html_dom->getElementsByTagName('*');
        foreach ($elements as $element) {
            if ($element->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            foreach ($matchers as $matcher) {
                $key_data = $element->getAttribute($matcher['key']);
                if (empty($key_data)) {
                    continue;
                }

                // Unroll the entity
                $key_data = html_entity_decode(self::unprotectEntity($key_data), ENT_QUOTES, 'UTF-8');

                // Is the data URL encoded?
                $should_encode = isset($matcher['encode']) && $matcher['encode'];
                if ($should_encode) {
                    $key_data = urldecode($key_data);
                }

                $additional_matchers = isset($matcher['matchers']) ? $matcher['matchers'] : [];
                $additional_match_mode = isset($matcher['match_mode']) ? $matcher['match_mode'] : 'any';
                if (!empty($additional_matchers)) {
                    $is_matched = self::processMatcher($additional_matchers, $element, $additional_match_mode);
                    if (!$is_matched) {
                        // Skip unmatched element
                        continue;
                    }
                }

                if (isset($matcher['entity']) && $matcher['entity'] !== $element->tagName) {
                    // Skip unmatched entity name
                    continue;
                }

                if (isset($matcher['mode']) && $matcher['mode'] === 'string') {
                    // If the mode is a string we just pass the string as is
                    $collected_temp = [
                        [
                            'key' => $matcher['key'],
                            'value' => $key_data,
                            'format' => 'html-main', // in case we need to format it later
                        ]
                    ];
                } else {
                    $json_data = json_decode($key_data, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        continue;
                    }

                    $is_strict = isset($matcher['strict']) && $matcher['strict'];
                    $collected_temp = self::collectFragmentFromJson($json_data, $is_strict);
                }

                if (!empty($collected_temp)) {
                    $int_marker = $matcher['attr_ids'] . '-' . $internal_counter;
                    $element->setAttribute($matcher['attr_ids'], $int_marker);
                    $all_fragments[$matcher['name']][$int_marker] = [
                        'mode' => 'attribute',
                        'attribute' => $element->tagName,
                        'fragments' => $collected_temp,
                    ];
                    $internal_counter++;
                }
            }
        }

        if (!empty($all_fragments)) {
            $html_data = self::saveHTML($html_dom);
        }

        Debug::log('AttributeHandler -> Collected: ' . json_encode($all_fragments, JSON_PRETTY_PRINT));

        return $all_fragments;
    }

    /**
     * Find the element by attribute and matcher.
     *
     * @param \DOMDocument $html_dom   The HTML DOM object
     * @param string       $tag_name   The tag name to search for
     * @param string       $attr_name  The attribute name to search for
     * @param string       $attr_value The attribute value to search for
     *
     * @return \DOMElement|null The found element or null if not found
     */
    private static function findElementByAttributeAndMatcher($html_dom, $tag_name, $attr_name, $attr_value)
    {
        $elements = $html_dom->getElementsByTagName($tag_name);
        foreach ($elements as $element) {
            if ($element->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            if ($element->hasAttribute($attr_name) && $element->getAttribute($attr_name) === $attr_value) {
                return $element;
            }
        }
        return null;
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
        if (empty($fragments)) {
            return $html_data;
        }

        $html_dom = self::loadHTML($html_data);
        if (empty($html_dom)) {
            return $html_data;
        }

        Debug::log('AttributeHandler -> Injecting: ' . json_encode($fragments, JSON_PRETTY_PRINT));

        $queued_deletions = [];
        $fragment_matchers = self::getMatcher($html_data);
        foreach ($fragments as $fragment_name => $fragment_jsons) {
            $matched = self::findMatcher($fragment_name, $fragment_matchers);
            foreach ($fragment_jsons as $fragment_param => $fragment_list) {
                $queued_deletions[] = $fragment_list['fragments'];

                if (!isset($fragment_list['attribute'])) {
                    continue;
                }
                if (empty($matched)) {
                    continue;
                }

                $attr_html = self::findElementByAttributeAndMatcher(
                    $html_dom,
                    $fragment_list['attribute'],
                    $matched['attr_ids'],
                    $fragment_param
                );

                if (empty($attr_html)) {
                    continue;
                }

                $match_data = $attr_html->getAttribute($matched['key']);
                if (empty($match_data)) {
                    continue;
                }

                // Since we have protection enabled around this!
                $match_data = html_entity_decode(self::unprotectEntity($match_data), ENT_QUOTES, 'UTF-8');

                $should_encode = isset($matched['encode']) && $matched['encode'];
                if ($should_encode) {
                    $match_data = urldecode($match_data);
                }

                // Check mode, if string mode, we just pass the string as is
                if (isset($matched['mode']) && $matched['mode'] === 'string') {
                    // Get first item
                    $first_fragment = isset($fragment_list['fragments'][0]) ? $fragment_list['fragments'][0] : null;
                    if (empty($first_fragment)) {
                        continue;
                    }

                    $first_value = isset($first_fragment['value']) ? $first_fragment['value'] : null;
                    if (empty($first_value)) {
                        continue;
                    }

                    // Replace the data
                    $replaced_text = $first_value;
                    if ($should_encode) {
                        $replaced_text = rawurlencode($replaced_text);
                    }

                    $protected_json = self::protectEntity($replaced_text);
                } else {
                    // JSON mode, we need to decode the JSON data
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

                    // Protect the entity back
                    $protected_json = self::protectEntity(htmlspecialchars($replaced_json, ENT_QUOTES, 'UTF-8', false));
                }

                $attr_html->setAttribute($matched['key'], $protected_json);
                $attr_html->removeAttribute($matched['attr_ids']);
            }
        }

        $html_data = self::saveHTML($html_dom);
        foreach ($queued_deletions as $deletion) {
            foreach ($deletion as $fragment) {
                $decoded_match = html_entity_decode($fragment['match'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $html_data = str_replace($fragment['match'], '', $html_data);
                $html_data = str_replace($decoded_match, '', $html_data);

                // Replace single & to &amp;
                $simple_sub = preg_replace('/&(?!(?:amp|lt|gt|quot|apos);)/', '&amp;', $decoded_match);
                if (!empty($simple_sub)) {
                    $html_data = str_replace($simple_sub, '', $html_data);
                }
            }
        }

        // Unmangle stuff like &amp;#xE5;
        $html_data = preg_replace('/&amp;#x([0-9A-Fa-f]+);/', '&#x$1;', $html_data);

        return $html_data;
    }
}
