<?php

namespace Linguise\WordPress;

use Linguise\Vendor\JsonPath\JsonObject;
use Linguise\Vendor\Linguise\Script\Core\Debug;

defined('ABSPATH') || die('');

/**
 * Check if the array is an actual object or not.
 *
 * @param array|object $arr_or_object The array or object to be checked
 *
 * @return boolean - True if it's an actual object, false if it's an array
 */
function is_actual_object($arr_or_object): bool
{
    if (is_object($arr_or_object)) {
        return true;
    }

    if (!is_array($arr_or_object)) {
        // preliminary check
        return false;
    }

    // https://stackoverflow.com/a/72949244 (PHP 7 compatible)
    if (function_exists('array_is_list')) {
        return array_is_list($arr_or_object) === false; // phpcs:ignore PHPCompatibility.FunctionUse.NewFunctions.array_is_listFound
    }

    $keys = array_keys($arr_or_object);
    return implode('', $keys) !== implode(range(0, count($keys) - 1));
}

/**
 * Check if the string has space or not.
 *
 * @param string $str The string to be checked
 *
 * @return boolean - True if it has space, false if it doesn't
 */
function has_space($str)
{
    return preg_match('/\s/', $str) > 0;
}

/**
 * Class FragmentHandler
 */
class FragmentHandler
{
    /**
     * Regex/matcher for our custom HTML fragment
     *
     * @var string
     */
    protected static $frag_html_match = '/<(div|a|linguise-main) class="linguise-fragment" data-fragment-name="([^"]*)" data-fragment-param="([^"]*)" data-fragment-key="([^"]*)" data-fragment-format="(link|html|html-main|text)" data-fragment-mode="(auto|override)"(?: href="([^"]*)")?>(.*?)<\/\1>/si';

    /**
     * Default filters for the fragments
     *
     * @var array
     */
    protected static $default_filters = [
        [
            'key' => 'nonce',
            'mode' => 'wildcard',
            'kind' => 'deny',
        ],
        [
            'key' => 'i18n_.+',
            'mode' => 'regex',
            'kind' => 'allow',
            'cast' => 'html-main',
        ],
        [
            'key' => 'currency\..*',
            'mode' => 'regex_full',
            'kind' => 'deny',
        ],
        [
            'key' => 'wc.*?_currency',
            'mode' => 'regex',
            'kind' => 'deny',
        ],
        [
            'key' => 'dateFormat',
            'mode' => 'exact',
            'kind' => 'deny',
        ],
        [
            'key' => 'baseLocation.country',
            'mode' => 'path',
            'kind' => 'deny',
        ],
        [
            'key' => 'baseLocation.state',
            'mode' => 'path',
            'kind' => 'deny',
        ],
        [
            'key' => 'admin.wccomHelper.storeCountry',
            'mode' => 'path',
            'kind' => 'deny',
        ],
        [
            'key' => '.*-version',
            'mode' => 'regex',
            'kind' => 'deny',
        ],
        [
            'key' => 'orderStatuses\..*',
            'mode' => 'regex_full',
            'kind' => 'allow',
        ],
        [
            'key' => 'wc.*Url',
            'mode' => 'regex_full',
            'kind' => 'deny',
        ],
        [
            'key' => '^defaultFields\..*\.(autocapitalize|autocomplete)',
            'mode' => 'regex_full',
            'kind' => 'deny',
        ],
        [
            'key' => 'defaultAddressFormat',
            'mode' => 'exact',
            'kind' => 'deny',
        ],
        [
            'key' => 'dateFormat',
            'mode' => 'exact',
            'kind' => 'deny',
        ],
        [
            'key' => '^checkoutData\..*',
            'mode' => 'regex_full',
            'kind' => 'deny',
        ],
        [
            'key' => 'api_key',
            'mode' => 'exact',
            'kind' => 'deny',
        ],
        [
            'key' => '^countryData\..*\.format',
            'mode' => 'regex_full',
            'kind' => 'deny',
        ],
        [
            'key' => '.*?(hash_key|fragment_name|storage_key)',
            'mode' => 'regex',
            'kind' => 'deny',
        ],
    ];

    /**
     * Check with the Configuration for the allow list and deny list.
     *
     * @param string $key      The key to be checked
     * @param string $full_key The full JSON path key to be checked
     *
     * @return boolean|null - True if it's allowed, false if it's not
     */
    public static function isKeyAllowed($key, $full_key)
    {
        // the allow/deny list are formatted array like:
        // [
        //    [
        //        'key' => 'woocommerce',
        //        'mode' => 'regex' | 'exact | 'path' | 'wildcard',
        //        'kind' => 'allow' | 'deny',
        //    ]
        // ]

        $merged_defaults = self::$default_filters;
        if (is_plugin_active('woocommerce/woocommerce.php')) {
            // Payment methods data
            $payment_keys_regex_full = [
                '^(paymentMethods|paymentMethodData)\..*\.style\..*',
                '^(paymentMethods|paymentMethodData)\..*\.cardOptions.*',
                '^(paymentMethods|paymentMethodData)\..*\.cards\..*',
                '^(paymentMethods|paymentMethodData)\..*\.icons\..*',
                '^(paymentMethods|paymentMethodData)\..*\.customFieldOptions.*',
                '^(paymentMethods|paymentMethodData)\..*\.elementOptions.*',
                '^(paymentMethods|paymentMethodData)\..*\.features.*',
                '^(paymentMethods|paymentMethodData)\..*\.icons.*',
                '^(paymentMethods|paymentMethodData)\..*\.(name|paymentType|countryCode)',
                '^(paymentMethods|paymentMethodData)\..*\.(countries|currencies)\..*',
                '^(paymentMethods|paymentMethodData)\..*\.(requiredParams|paymentSections|specificCountries).*',
                '^paymentMethodSortOrder\..*',
                '^collectableMethodIds\..*',
            ];
            foreach ($payment_keys_regex_full as $payment_key) {
                $merged_defaults[] = [
                    'key' => $payment_key,
                    'mode' => 'regex_full',
                    'kind' => 'deny',
                ];
            }

            /* For AJAX requests */
            $merged_defaults[] = [
                'key' => '^items\.\d+\.(name|(short_)?description|permalink)', // -> cart request
                'mode' => 'regex_full',
                'kind' => 'allow',
            ];
            $merged_defaults[] = [
                'key' => '^responses\.\d+\.body\.items\.\d+\.(name|(short_)?description|permalink)', // -> batch request
                'mode' => 'regex_full',
                'kind' => 'allow',
            ];
            $merged_defaults[] = [
                'key' => '^payment_result\.(redirect_url|message)', // -> checkout request
                'mode' => 'regex_full',
                'kind' => 'allow',
            ];
        }

        if (is_plugin_active('woo-stripe-payment/stripe-payments.php')) {
            $payment_keys_regex_full = [
                '^stripeGeneralData\..*',
                '^elementOptions\..*',
                '^paymentElementOptions\..*',
                '^messageOptions\..*',
                '^confirmParams\..*',
                '^payment_sections\..*',
                '^stripeParams\..*',
                '^(shipping|billing)\_.*\.(autocapitalize|autocomplete|class.*|value)',
                '^merchant_(id|name)$',
                '^button_(\w+)$',
            ];
            $payment_keys_exact = [
                'api_key',
                'button',
                'button_size_mode',
                'country_code',
                'currency',
                'local_payment_type',
                'banner_enabled',
                'rest_nonce',
                'page',
                'saved_method_selector',
                'token_selector',
                'user_id',
                'account',
                'mode',
                'version',
                'environment',
                'processing_country',
            ];
            foreach ($payment_keys_regex_full as $payment_key) {
                $merged_defaults[] = [
                    'key' => $payment_key,
                    'mode' => 'regex_full',
                    'kind' => 'deny',
                ];
            }
            foreach ($payment_keys_exact as $payment_key) {
                $merged_defaults[] = [
                    'key' => $payment_key,
                    'mode' => 'exact',
                    'kind' => 'deny',
                ];
            }
        }

        if (is_plugin_active('pymntpl-paypal-woocommerce/pymntpl-paypal-woocommerce.php')) {
            $merged_defaults[] = [
                'key' => '^paypalQueryParams\..*',
                'mode' => 'regex_full',
                'kind' => 'deny',
            ];

            $path_key_disallowed = [
                'ppcpGeneralData.ajaxRestPath',
                'ppcpGeneralData.blocksVersion',
                'ppcpGeneralData.clientId',
                'ppcpGeneralData.context',
                'ppcpGeneralData.environment',
            ];

            foreach ($path_key_disallowed as $path_key) {
                $merged_defaults[] = [
                    'key' => $path_key,
                    'mode' => 'path',
                    'kind' => 'deny',
                ];
            }

            $merged_defaults[] = [
                'key' => '^ppcpGeneralData\.restRoutes\..*',
                'mode' => 'regex_full',
                'kind' => 'deny',
            ];
        }

        if (self::isCurrentTheme('Woodmart')) {
            $regex_key_disallowed = [
                'add_to_cart_action.*',
                'age_verify.*',
                'ajax_(?!url)(\w+)',
                'carousel_breakpoints\..*',
                'comment_images_upload_mimes\..*',
                'tooltip_\w+_selector',
            ];

            foreach ($regex_key_disallowed as $regex_key) {
                $merged_defaults[] = [
                    'key' => $regex_key,
                    'mode' => 'regex_full',
                    'kind' => 'deny',
                ];
            }

            $exact_key_disallowed = [
                'added_popup',
                'base_hover_mobile_click',
                'cart_redirect_after_add',
                'categories_toggle',
                'collapse_footer_widgets',
                'compare_by_category',
                'compare_save_button_state',
                'countdown_timezone',
                'whb_header_clone',
                'vimeo_library_url',
                'theme_dir',
                'wishlist_page_nonce',
                'photoswipe_template',
            ];

            foreach ($exact_key_disallowed as $exact_key) {
                $merged_defaults[] = [
                    'key' => $exact_key,
                    'mode' => 'path',
                    'kind' => 'deny',
                ];
            }
        }

        // Run through filters, provide our current default filters
        // User can change it by adding a filter and modify the array.
        if (function_exists('apply_filters')) {
            $wp_frag_list = apply_filters('linguise_fragment_filters', $merged_defaults);
        } else {
            $wp_frag_list = $merged_defaults;
        }

        foreach ($wp_frag_list as $frag_item) {
            $allow = $frag_item['kind'] === 'allow';
            $cast_data = isset($frag_item['cast']) ? $frag_item['cast'] : null;
            if ($frag_item['mode'] === 'path') {
                // check if full key is the same
                if ($frag_item['key'] === $full_key) {
                    // Return cast or bool
                    return $cast_data ? $cast_data : $allow;
                }
            } elseif ($frag_item['mode'] === 'exact') {
                // check if key is the same
                if ($frag_item['key'] === $key) {
                    return $cast_data ? $cast_data : $allow;
                }
            } elseif ($frag_item['mode'] === 'regex' || $frag_item['mode'] === 'regex_full') {
                // check if regex matches
                $key_match = $frag_item['mode'] === 'regex_full' ? $full_key : $key;
                $match_re = '/' . $frag_item['key'] . '/';
                if (preg_match($match_re, $key_match)) {
                    return $cast_data ? $cast_data : $allow;
                }
            } elseif ($frag_item['mode'] === 'wildcard') {
                // check if wildcard matches
                $match_re = '/^.*?' . $frag_item['key'] . '.*?$/';
                if (preg_match($match_re, $key)) {
                    return $cast_data ? $cast_data : $allow;
                }
            }
        }

        return null;
    }

    /**
     * Check if the string is a translatable string or not.
     *
     * @param string $value The string to be checked
     *
     * @return boolean - True if it's a translatable string, false if it's not
     */
    private static function isTranslatableString($value)
    {
        $value = trim($value);

        if (empty($value) || !is_string($value)) {
            return false;
        }

        // Check if it's a JSON, if yes, do not translate
        $json_parse = json_decode($value);
        if (!is_null($json_parse)) {
            return false;
        }

        // Has space? Most likely a translateable string
        if (has_space($value)) {
            return true;
        }

        // Check if email
        if (!empty(filter_var($value, FILTER_VALIDATE_EMAIL))) {
            return false;
        }

        // Check if string is UUID
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $value)) {
            return false;
        }

        // Check if first word is lowercase (bad idea?)
        // Or, check if has a number/symbols
        if (ctype_lower($value[0]) || preg_match('/[0-9\W]/', $value)) {
            return false;
        }

        return true;
    }

    /**
     * Check if the string is a link or not.
     *
     * @param string $value The string to be checked
     *
     * @return boolean - True if it's a link, false if it's not
     */
    private static function isStringLink($value)
    {
        // Has http:// or https://
        // Has %%endpoint%%
        // Starts with / and has no space
        if (preg_match('/https?:\/\//', $value)) {
            // Validate the link
            if (filter_var($value, FILTER_VALIDATE_URL)) {
                return true;
            }

            return false;
        }

        if (substr($value, 0, 1) === '/' && !has_space($value)) {
            $as_url = parse_url($value);
            if (empty($as_url)) {
                return false;
            }

            // Check if it only have "path" and not other keys
            $array_keys = array_keys($as_url);
            if (count($array_keys) === 1 && $array_keys[0] === 'path') {
                return true;
            }

            if (preg_match('/%%.*%%/', $value)) {
                // Assume WC-AJAX
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the string is a HTML element or not.
     *
     * @param string $value The string to be checked
     *
     * @return string|false - True if it's a HTML element, false if it's not
     */
    private static function isHTMLElement($value)
    {
        if (empty($value)) {
            return false;
        }

        // use simplexml, suppress the warning
        $doc = @simplexml_load_string($value); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
        if ($doc !== false) {
            return 'html';
        }

        // Use strip_tags method
        if (strip_tags($value) !== $value) {
            return 'html-main';
        }

        return false;
    }

    /**
     * Check and wrap key with ["$key"] if it use symbols
     *
     * @param string $key The key to be checked
     *
     * @return string
     */
    private static function wrapKey($key)
    {
        // only include symbols
        // alphanumeric is not included
        if (preg_match('/[^\w]/', $key)) {
            return "['" . $key . "']";
        }

        return $key;
    }

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
        if (!empty($current_key)) {
            if ($key === $use_key) {
                $use_key = '.' . $use_key;
            }

            $use_key = $current_key . $use_key;
        }
        if ($array_index !== null) {
            $use_key = $use_key . '.' . $array_index;
        }

        if (is_actual_object($value)) {
            $collected_fragments = self::collectFragmentFromJson($value, $strict, $collected_fragments, $use_key);
        } elseif (is_array($value)) {
            for ($i = 0; $i < count($value); $i++) { // phpcs:ignore Generic.CodeAnalysis.ForLoopWithTestFunctionCall.NotAllowed
                $inner_value = $value[$i];
                $collected_fragments = self::collectFragment($key, $inner_value, $collected_fragments, $current_key, $strict, $i);
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
     * Checks if the given theme name is the current theme or the parent of the current theme.
     *
     * @param string         $theme_name   The name of the theme to check.
     * @param \WP_Theme|null $parent_theme Optional. The parent theme to check. Default is null.
     *
     * @return boolean True if the given theme name is the current theme or its parent, false otherwise.
     */
    private static function isCurrentTheme($theme_name, $parent_theme = \null)
    {
        $theme = $parent_theme ?: wp_get_theme();
        if (empty($theme)) {
            return false;
        }

        $is_theme = $theme->name === $theme_name;
        if ($is_theme) {
            return true;
        }

        $parent = $theme->parent();
        if ($parent !== false) {
            return self::isCurrentTheme($theme_name, $parent);
        }
        return false;
    }

    /**
     * Get override JSON fragment matching
     *
     * The way the override works is by matching the script content with the regex, the schema of each item is:
     * - name: The name of the plugin, e.g. 'mailoptin', must be unique
     * - match: The regex to match the script content
     * - replacement: The replacement string, use $$JSON_DATA$$ as the placeholder for the JSON data
     * - position: The position of the JSON data, default to 1 (optional)
     * - encode: If the JSON data is URL encoded or not, default to false (optional)
     * - id: The id of the script, if it's not the same, then it will be skipped (optional)
     * - mode: The mode of the script, default to 'script' (available are: `script` and `app_json`)
     *
     * @return array The array of JSON to match with fragment
     */
    private static function getJSONOverrideMatcher()
    {
        $current_list = [];

        if (is_plugin_active('mailoptin/mailoptin.php')) {
            $current_list[] = [
                'name' => 'mailoptin',
                'match' => '<script type="text\/javascript">var (.*)_lightbox = (.*);<\/script>',
                'replacement' => '<script text="text/javascript">var $1_lightbox = $$JSON_DATA$$;</script>',
                'position' => 2,
            ];
        }

        if (is_plugin_active('ninja-forms/ninja-forms.php')) {
            $current_list[] = [
                'name' => 'ninjaforms_fields',
                'match' => 'form.fields=(.*?);nfForms',
                'replacement' => 'form.fields=$1;nfForms',
            ];
            $current_list[] = [
                'name' => 'ninjaforms_i18n',
                'match' => 'nfi18n = (.*?);',
                'replacement' => 'nfi18n = $$JSON_DATA$$;',
            ];
            $current_list[] = [
                'name' => 'ninjaforms_settings',
                'match' => 'form.settings=(.*?);form',
                'replacement' => 'form.settings=$$JSON_DATA$$;form',
            ];
        }

        if (is_plugin_active('wpforms-lite/wpforms.php')) {
            $current_list[] = [
                'name' => 'wpforms-lite',
                'match' => 'wpforms_settings = (.*?)(\n)(\/\* ]]> \*\/)',
                'replacement' => 'wpforms_settings = $$JSON_DATA$$$2$3',
            ];
        }

        if (is_plugin_active('popup-maker/popup-maker.php')) {
            $current_list[] = [
                'name' => 'popup-maker',
                'match' => 'var pumAdminBarText = (.*?);',
                'replacement' => 'var pumAdminBarText = $$JSON_DATA$$;',
            ];
        }

        if (is_plugin_active('mailpoet/mailpoet.php')) {
            $current_list[] = [
                'name' => 'mailpoet',
                'match' => 'var MailPoetForm = (.*?);',
                'replacement' => 'var MailPoetForm = $$JSON_DATA$$;',
            ];
        }

        if (is_plugin_active('woo-stripe-payment/stripe-payments.php')) {
            $current_list[] = [
                'name' => 'wc-stripe-params-v3',
                'match' => 'var wc_stripe_params_v3 = (.*?);',
                'replacement' => 'var wc_stripe_params_v3 = $$JSON_DATA$$;',
            ];

            $current_list[] = [
                'name' => 'wc-stripe-messages',
                'match' => 'var wc_stripe_messages = (.*?);',
                'replacement' => 'var wc_stripe_messages = $$JSON_DATA$$;',
            ];

            $current_list[] = [
                'name' => 'wc-stripe-checkout-fields',
                'match' => 'var wc_stripe_checkout_fields = (.*?);',
                'replacement' => 'var wc_stripe_checkout_fields = $$JSON_DATA$$;',
            ];
        }

        if (self::isCurrentTheme('Woodmart')) {
            $current_list[] = [
                'name' => 'woodmart-theme',
                'match' => 'var woodmart_settings = (.*?);',
                'replacement' => 'var woodmart_settings = $$JSON_DATA$$;',
            ];
        }

        $current_list[] = [
            'name' => 'wc-settings-encoded',
            'match' => 'var wcSettings = wcSettings \|\| JSON\.parse\( decodeURIComponent\( \'(.*?)\' \) \);',
            'replacement' => 'var wcSettings = wcSettings || JSON.parse( decodeURIComponent( \'$$JSON_DATA$$\' ) );',
            'encode' => true,
        ];

        if (defined('CFCORE_VER')) {
            $current_list[] = [
                'name' => 'calderaforms',
                'match' => 'CF_VALIDATOR_STRINGS = (.*?);',
                'replacement' => 'CF_VALIDATOR_STRINGS = $$JSON_DATA$$;',
            ];
        }

        if (is_plugin_active('ameliabooking/ameliabooking.php')) {
            $current_list[] = [
                'name' => 'amelia-labels',
                'match' => 'var wpAmeliaLabels = (.*?);',
                'replacement' => 'var wpAmeliaLabels = $$JSON_DATA$$;',
            ];
        }

        // Merge with apply_filters
        if (function_exists('apply_filters')) {
            $current_list = apply_filters('linguise_fragment_override', $current_list);
        }

        return $current_list;
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
            $html .= '<' . $tag . ' class="linguise-fragment" data-fragment-name="' . $fragment_name . '" data-fragment-param="' . $fragment_param . '" data-fragment-key="';
            $html .= $fragment['key'] . '" data-fragment-format="' . $fragment['format'] . '" data-fragment-mode="' . $json_fragments['mode'] . '"';
            if ($fragment['format'] === 'link') {
                $html .= ' href="' . $fragment['value'] . '">';
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
        preg_match_all(self::$frag_html_match, $html_fragments, $matches, PREG_SET_ORDER, 0);
        foreach ($matches as $match) {
            $fragment_name = $match[2];
            $fragment_param = $match[3];
            $fragment_key = $match[4];
            $fragment_format = $match[5];
            $fragment_mode = $match[6];

            $fragment_value = $match[8];

            if ($fragment_format === 'link') {
                $fragment_value = $match[7];
            }

            if ($fragment_format === 'html') {
                // parse back the linguise-dev
                $fragment_value = preg_replace('/<linguise-div(.*?)>(.*?)<\/linguise-div>$/si', '<div$1>$2</div>', $fragment_value, 1);
            } elseif ($fragment_format === 'html-main') {
                // parse back the linguise-main
                $fragment_value = preg_replace('/<linguise-main>(.*?)<\/linguise-main>$/si', '$1', $fragment_value, 1);
            }

            if (!isset($fragments[$fragment_name])) {
                $fragments[$fragment_name] = [];
            }
            if (!isset($fragments[$fragment_name][$fragment_param])) {
                $fragments[$fragment_name][$fragment_param] = [
                    'mode' => $fragment_mode,
                    'fragments' => [],
                ];
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
     * @param \DOMNode|\DOMElement $script The script to be matched
     *
     * @return array|null
     */
    private static function tryMatchWithOverride($script)
    {
        $script_content = $script->textContent;
        $script_id = $script->getAttribute('id');

        $override_list = self::getJSONOverrideMatcher();

        foreach ($override_list as $override_item) {
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
    public static function findWPFragments($html_data)
    {
        $html_dom = new \DOMDocument();
        @$html_dom->loadHTML($html_data); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged

        $scripts = $html_dom->getElementsByTagName('script');

        $all_fragments = [];
        foreach ($scripts as $script) {
            $attr_id = $script->getAttribute('id');
            $match_res = preg_match('/^(.*)-js-extra$/', $attr_id, $attr_match);
            if ($match_res === false || $match_res === 0) {
                $overridden_temp = self::tryMatchWithOverride($script);
                if (is_array($overridden_temp)) {
                    $all_fragments[$overridden_temp['name']][$overridden_temp['name']] = [
                        'mode' => 'override',
                        'fragments' => $overridden_temp['fragments'],
                    ];
                }
                continue;
            }

            $frag_id = $attr_match[1];

            $match_res = preg_match('/var ' . str_replace('-', '_', $frag_id) . '_params = (.*);/', $script->textContent, $json_matches);
            if ($match_res === false || $match_res === 0) {
                $unmatched_res = preg_match_all('/var (.+)_params = (.*);/', $script->textContent, $json_multi_matches, PREG_SET_ORDER, 0);
                if ($unmatched_res === false || $unmatched_res === 0) {
                    $overridden_temp = self::tryMatchWithOverride($script);
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
     * Unpack/decode the key name from the JSON data.
     *
     * This is needed because the key name would be encoded sometimes.
     *
     * @param string $key The key to be decoded
     *
     * @return string
     */
    private static function decodeKeyName($key)
    {
        $key = html_entity_decode($key, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        // Would sometimes fails??
        $key = str_replace('&apos;', "'", $key);
        $key = str_replace('&quot;', '"', $key);
        return $key;
    }

    /**
     * Apply the translated fragments for the override.
     *
     * @param string $html_data     The HTML data to be injected
     * @param string $fragment_name The name of the fragment, e.g. 'woocommerce'
     * @param array  $fragment_info The array of fragments to be injected, from intoJSONFragments
     *
     * @return string
     */
    public static function applyTranslatedFragmentsForOverride($html_data, $fragment_name, $fragment_info)
    {
        $fragment_matcher = self::getJSONOverrideMatcher();

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
                try {
                    $json_data->set('$.' . self::decodeKeyName($fragment['key']), $fragment['value']);
                } catch (\Linguise\Vendor\JsonPath\InvalidJsonPathException $e) {
                    Debug::log('Failed to set key in override: ' . self::decodeKeyName($fragment['key']) . ' -> ' . $e->getMessage());
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
                try {
                    $json_data->set('$.' . self::decodeKeyName($fragment['key']), $fragment['value']);
                } catch (\Linguise\Vendor\JsonPath\InvalidJsonPathException $e) {
                    Debug::log('Failed to set key in override: ' . self::decodeKeyName($fragment['key']) . ' -> ' . $e->getMessage());
                }
            }

            $replaced_json = $json_data->getJson();
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
            try {
                $json_path->set('$.' . self::decodeKeyName($fragment['key']), $fragment['value']);
            } catch (\Linguise\Vendor\JsonPath\InvalidJsonPathException $e) {
                Debug::log('Failed to set key in auto: ' . self::decodeKeyName($fragment['key']) . ' -> ' . $e->getMessage());
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
    private static function cleanupFragments($html_data, $fragments)
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

                if ($mode === 'override') {
                    $html_data = self::applyTranslatedFragmentsForOverride($html_data, $fragment_param, $fragment_list);
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
        return $html_data;
    }
}
