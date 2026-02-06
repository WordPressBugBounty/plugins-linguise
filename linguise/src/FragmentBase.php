<?php

namespace Linguise\WordPress;

defined('ABSPATH') || die('');

/**
 * A collection of helper function for fragment handler
 */
class FragmentBase
{
    /**
     * Default filters for the fragments
     *
     * @var array
     */
    protected static $default_filters = [
        [
            'key' => '(state|country)\.label$',
            'mode' => 'regex_full',
            'kind' => 'allow'
        ],
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
        [
            'key' => 'wc_ajax_url',
            'mode' => 'exact',
            'kind' => 'deny',
        ],
        /**
         * Plugin : woocommerce-gateway-stripe
         */
        [
            'key' => 'paymentMethodsConfig.*?(card|us_bank_account|alipay|klarna|afterpay_clearpay|link|wechat_pay|cashapp)\.countries',
            'mode' => 'regex_full',
            'kind' => 'deny',
        ],
        [
            'key' => 'accountCountry',
            'mode' => 'exact',
            'kind' => 'deny',
        ],
        [
            'key' => 'appearance\..*',
            'mode' => 'regex_full',
            'kind' => 'deny',
        ],
        [
            'key' => 'blocksAppearance\..*',
            'mode' => 'regex_full',
            'kind' => 'deny',
        ],
        [
            'key' => 'paymentMethodData.*?stripe\.plugin_url',
            'mode' => 'regex_full',
            'kind' => 'deny',
        ],
        [
            'key' => 'currency',
            'mode' => 'exact',
            'kind' => 'deny',
        ],
    ];

    /**
     * Cached key filters, should be initialized on first use
     *
     * @var array|null
     */
    protected static $key_filters = null;

    /**
     * Get the key filters, merged with the default filters and the theme/plugin specific filters
     *
     * @return array The array of key filters
     */
    protected static function getKeyFilters()
    {
        // the allow/deny list are formatted array like:
        // [
        //    [
        //        'key' => 'woocommerce',
        //        'mode' => 'regex' | 'exact | 'path' | 'wildcard',
        //        'kind' => 'allow' | 'deny',
        //    ]
        // ]

        if (!empty(self::$key_filters)) {
            return self::$key_filters;
        }

        $merged_defaults = self::$default_filters;
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
        $wp_frag_list = apply_filters('linguise_fragment_filters', $merged_defaults);

        // cache the list
        self::$key_filters = $wp_frag_list;

        return self::$key_filters;
    }

    /**
     * Check if the string is a translatable string or not.
     *
     * @param string $value The string to be checked
     *
     * @return boolean - True if it's a translatable string, false if it's not
     */
    protected static function isTranslatableString($value)
    {
        $value = trim($value);

        if (empty($value) || !is_string($value)) {
            return false;
        }

        if (is_string($value) && empty($value)) {
            return false;
        }

        // Check if it's a JSON, if yes, do not translate
        $json_parse = json_decode($value);
        if (!is_null($json_parse)) {
            return false;
        }

        // Has space? Most likely a translateable string
        if (Helper::hasSpace($value)) {
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
    protected static function isStringLink($value)
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

        if (substr($value, 0, 1) === '/' && !Helper::hasSpace($value)) {
            $as_url = parse_url($value);
            if (empty($as_url)) {
                return false; // @codeCoverageIgnore
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
     * Check if the string is a image link or not.
     *
     * @param string $value The string to be checked
     *
     * @return boolean - True if it's a image link, false if it's not
     */
    protected static function isImageLink($value)
    {
        // There is so much more image extension
        $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'tiff', 'tif', 'ico', 'avif', 'heic', 'heif'];
        $regex_matcher = '/\.(?:' . implode('|', $extensions) . ')$/i';
        if (preg_match($regex_matcher, $value)) {
            // We don't need to validate the link since this is called inside isStringLink
            return true;
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
    protected static function isHTMLElement($value)
    {
        if (empty($value)) {
            return false;
        }

        // use simplexml, suppress the warning
        if (extension_loaded('xml') && function_exists('simplexml_load_string')) {
            $doc = @simplexml_load_string($value); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
            if ($doc !== false) {
                return 'html';
            }
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
    protected static function wrapKey($key)
    {
        // only include symbols
        // alphanumeric is not included
        if (preg_match('/[^\w]/', $key)) {
            return "['" . $key . "']";
        }

        return $key;
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
    protected static function unwrapKey($key)
    {
        $key = html_entity_decode($key, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        // Would sometimes fails??
        $key = str_replace('&apos;', "'", $key);
        $key = str_replace('&quot;', '"', $key);
        return $key;
    }

    /**
     * Checks if the given theme name is the current theme or the parent of the current theme.
     *
     * @param string         $theme_name   The name of the theme to check.
     * @param \WP_Theme|null $parent_theme Optional. The parent theme to check. Default is null.
     *
     * @return boolean True if the given theme name is the current theme or its parent, false otherwise.
     */
    protected static function isCurrentTheme($theme_name, $parent_theme = \null)
    {
        // @codeCoverageIgnoreStart
        if (!function_exists('wp_get_theme')) {
            return false;
        }
        // @codeCoverageIgnoreEnd

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
     * Check with the Configuration for the allow list and deny list.
     *
     * @param string $key      The key to be checked
     * @param string $full_key The full JSON path key to be checked
     *
     * @return boolean|null - True if it's allowed, false if it's not
     */
    public static function isKeyAllowed($key, $full_key)
    {
        $wp_frag_list = self::getKeyFilters();

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
     * @param string $html_data The HTML data input
     *
     * @return array The array of JSON to match with fragment
     */
    protected static function getJSONOverrideMatcher($html_data)
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

        $current_list[] = [
            'name' => 'wc-settings-encoded-alt',
            'match' => 'var wcSettings = JSON\.parse\( decodeURIComponent\( \'(.*?)\' \) \);',
            'replacement' => 'var wcSettings = JSON.parse( decodeURIComponent( \'$$JSON_DATA$$\' ) );',
            'encode' => true,
        ];

        $current_list[] = [
            'name' => 'wc-settings-api-inject',
            'match' => 'wp\.apiFetch\.createPreloadingMiddleware\(\s*JSON\.parse\(\s*decodeURIComponent\(\s*\'(.*?)\'\s*\)\s*\)\s*\)',
            'replacement' => 'wp.apiFetch.createPreloadingMiddleware( JSON.parse( decodeURIComponent( \'$$JSON_DATA$$\' ) ) )',
            'encode' => true,
        ];

        if (defined('CFCORE_VER')) {
            $current_list[] = [
                'name' => 'calderaforms',
                'match' => 'CF_VALIDATOR_STRINGS = (.*?);',
                'replacement' => 'CF_VALIDATOR_STRINGS = $$JSON_DATA$$;',
            ];
        }

        $current_list[] = [
            'name' => 'surecart-store-data',
            'key' => 'sc-store-data',
            'mode' => 'app_json',
        ];

        // Merge with apply_filters
        $current_list = apply_filters('linguise_fragment_override', $current_list, $html_data);

        return $current_list;
    }
}
