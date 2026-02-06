<?php

namespace Linguise\WordPress;

defined('ABSPATH') || die('');

/**
 * Class Helper
 */
class Helper
{
    /**
     * Languages information
     *
     * @var null | object
     */
    protected static $languages_information = null;

    /**
     * Check if a request is an admin one
     * https://florianbrinkmann.com/en/wordpress-backend-request-3815/
     *
     * @return boolean
     */
    public static function isAdminRequest()
    {
        $admin_url = strtolower(admin_url());
        $referrer  = strtolower(wp_get_referer());
        $current_url = home_url(add_query_arg(null, null));

        if (strpos($current_url, $admin_url) === 0) {
            if (0 === strpos($referrer, $admin_url)) {
                return true;
            } else {
                if (function_exists('wp_doing_ajax')) {
                    return !wp_doing_ajax(); // @codeCoverageIgnore
                } else {
                    return !(defined('DOING_AJAX') && DOING_AJAX);
                }
            }
        } else {
            return false;
        }
    }

    /**
     * Return the visitor language from Linguise request
     *
     * @return string|null
     */
    public static function getLanguage()
    {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- View request, no action
        if (!empty($_SERVER['HTTP_LINGUISE_ORIGINAL_LANGUAGE']) && self::isTranslatableLanguage($_SERVER['HTTP_LINGUISE_ORIGINAL_LANGUAGE'])) {
            return $_SERVER['HTTP_LINGUISE_ORIGINAL_LANGUAGE'];
        } elseif (!empty($_GET['linguise_language']) && self::isTranslatableLanguage($_GET['linguise_language'])) {
            return $_GET['linguise_language'];
        }
        // phpcs:enable

        return null;
    }

    /**
     * Retrieve the language from referer
     *
     * Will parse the referer as an URL and check if it matches a translatable language
     * If not, return null
     *
     * @return string|null
     */
    public static function getLanguageFromReferer()
    {
        if (!empty(wp_get_referer())) {
            // Parse as URL
            $language = self::getLanguageFromUrl(wp_get_referer());
            if ($language !== null) {
                return $language;
            }
        }

        return null;
    }

    /**
     * Get the language from an URL
     *
     * @param string $url The URL to parse
     *
     * @return string|null The parsed language (or null if not found)
     */
    public static function getLanguageFromUrl($url)
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (empty($path)) {
            // Fail to parse!
            return null;
        }

        $site_path = parse_url(linguiseGetSite(), PHP_URL_PATH);
        if (!empty($site_path) && strpos($path, $site_path) === 0) {
            // Remove the site path from the URL
            $path = substr($path, strlen($site_path)); // @codeCoverageIgnore
        }

        $parts = explode('/', trim($path, '/'));
        if (!count($parts) || $parts[0] === '') {
            return null;
        }

        $language = $parts[0];
        if (self::isTranslatableLanguage($language)) {
            return $language;
        }

        return null;
    }

    /**
     * Return all languages information
     *
     * @return object
     */
    public static function getLanguagesInfos()
    {
        if (self::$languages_information !== null) {
            return self::$languages_information;
        }

        $languages = file_get_contents(dirname(__FILE__) . '../../assets/languages.json');
        self::$languages_information = json_decode($languages);

        return self::$languages_information;
    }

    /**
     * Check if the passed language is RTL
     *
     * @param string $language_code Language code to check
     *
     * @return boolean
     */
    public static function getLanguageIsRtl($language_code)
    {
        $languages = self::getLanguagesInfos();

        if (isset($languages->$language_code)) {
            return $languages->$language_code->rtl ?? false;
        }

        return false;
    }

    /**
     * Map Linguise language to WordPress locale
     *
     * @param string $language Linguise language
     *
     * @return string|null
     */
    public static function mapLanguageToWordPressLocale($language)
    {
        $languages = self::getLanguagesInfos();

        if (isset($languages->$language)) {
            // Check if wp_code exist
            // If not set, we use the same as $language
            // If set and NULL we should return null
            if (!array_key_exists('wp_code', (array)$languages->$language)) {
                return $language;
            }
            if (!empty($languages->$language->wp_code)) {
                return $languages->$language->wp_code;
            }

            return null;
        }

        return null;
    }

    /**
     * Check if the passed language is translatable
     *
     * @param string $language Language code to check
     *
     * @return boolean
     */
    public static function isTranslatableLanguage($language)
    {
        linguiseSwitchMainSite();
        $linguise_options = get_option('linguise_options');
        linguiseRestoreMultisite();

        if (!$linguise_options) {
            return false; // @codeCoverageIgnore
        }

        return $language !== $linguise_options['default_language'] && in_array($language, $linguise_options['enabled_languages']);
    }

    /**
     * Get the Linguise locale from WordPress locale
     *
     * @param string $locale The WordPress locale to convert
     *
     * @return string|null The Linguise locale or null if not found
     */
    public static function getLinguiseCodeFromWPLocale($locale)
    {
        $languages = self::getLanguagesInfos();
        foreach ($languages as $lang_key => $language) {
            if (isset($language->wp_code) && $language->wp_code === $locale) {
                return $lang_key;
            }

            if (self::localeCompare($lang_key, $locale)) {
                return $lang_key; // @codeCoverageIgnore
            }
        }
        return null;
    }

    /**
     * Safe strpos function
     *
     * @param string $haystack The haystack string
     * @param string $needle   The needle string
     *
     * @return integer The position of the needle in the haystack or the length of the haystack if
     *                 the needle is not found
     */
    public static function ensureStrpos($haystack, $needle)
    {
        $pos = strpos($haystack, $needle);
        return $pos === false ? strlen($haystack) : $pos;
    }

    /**
     * Locale compare check with country code ignore check supported.
     *
     * @param string $locale      The current locale being checked
     * @param string $test_locale The locale to test against
     *
     * @return boolean
     */
    public static function localeCompare($locale, $test_locale)
    {
        if (empty($locale) || empty($test_locale)) {
            return false;
        }

        // Normalize underscore and dash to a dash
        $locale = str_replace('_', '-', $locale);
        $test_locale = str_replace('_', '-', $test_locale);
        if (strcasecmp($locale, $test_locale) === 0) {
            return true;
        }

        // trim until _ or -
        $test_locale = substr($test_locale, 0, self::ensureStrpos($test_locale, '_'));
        $test_locale = substr($test_locale, 0, self::ensureStrpos($test_locale, '-'));

        if (strcasecmp($locale, $test_locale) === 0) {
            return true;
        }

        // trim $locale until _ or -
        $locale = substr($locale, 0, self::ensureStrpos($locale, '_'));
        $locale = substr($locale, 0, self::ensureStrpos($locale, '-'));

        return strcasecmp($locale, $test_locale) === 0;
    }

    /**
     * Create a URL path to our script
     *
     * @param string $path Path to our script
     *
     * @return boolean
     */
    public static function getScriptUrl($path)
    {
        // Check if path starts with /
        if (substr($path, 0, 1) === '/') {
            // Strip / since we want some consistency
            $path = substr($path, 1);
        }

        // Check if LINGUISE_PLUGIN_URL has / at the end
        if (substr(LINGUISE_PLUGIN_URL, -1) === '/') {
            return LINGUISE_PLUGIN_URL . $path; // @codeCoverageIgnore
        }

        return LINGUISE_PLUGIN_URL . '/' . $path;
    }

    /**
     * Check if the request is behind Cloudflare
     *
     * @return boolean true if the request is behind Cloudflare
     */
    public static function isBehindCloudflare()
    {
        if (array_key_exists('HTTP_CF_CONNECTING_IP', $_SERVER)) {
            return true;
        }

        return false;
    }

    /**
     * Parse a HEX color to RGB
     *
     * @param string $color The HEX color to parse
     *
     * @return array|null The parsed color as an array with 'r', 'g', and 'b' keys, or null if invalid
     */
    public static function parseHexColor($color)
    {
        $hex_color = ltrim($color, '#');

        if (strlen($hex_color) === 3) {
            $hex_color = $hex_color[0] . $hex_color[0] . $hex_color[1] . $hex_color[1] . $hex_color[2] . $hex_color[2];
        } elseif (strlen($hex_color) !== 6) {
            return null;
        }

        if (!ctype_xdigit($hex_color)) {
            return null;
        }

        $r = hexdec(substr($hex_color, 0, 2));
        $g = hexdec(substr($hex_color, 2, 2));
        $b = hexdec(substr($hex_color, 4, 2));

        return [
            'r' => $r,
            'g' => $g,
            'b' => $b,
        ];
    }

    /**
     * Mix and blend $color HEX code with $alpha value
     *
     * This will create a RGBA color from a HEX color
     * and a alpha value
     *
     * @param string $color The HEX color to parse
     * @param float  $alpha The alpha value to use (0.0 to 1.0)
     *
     * @return string The parsed color as an RGBA string, if fails returns the original color
     */
    public static function mixColorAlpha($color, $alpha = 1.0)
    {
        $color_p = self::parseHexColor($color);
        if ($color_p === null) {
            return $color;
        }

        return 'rgba(' . $color_p['r'] . ', ' . $color_p['g'] . ', ' . $color_p['b'] . ', ' . $alpha . ')';
    }

    /**
     * Check if the array is an actual object or not.
     *
     * @param array|object $arrOrObject The array or object to be checked
     *
     * @return boolean - True if it's an actual object, false if it's an array
     */
    public static function isActualObject($arrOrObject)
    {
        if (is_object($arrOrObject)) {
            return true;
        }

        if (!is_array($arrOrObject)) {
            // preliminary check
            return false;
        }

        // https://stackoverflow.com/a/72949244 (PHP 7 compatible)
        if (function_exists('array_is_list')) {
            return array_is_list($arrOrObject) === false; // phpcs:ignore PHPCompatibility.FunctionUse.NewFunctions.array_is_listFound
        }

        // @codeCoverageIgnoreStart
        $keys = array_keys($arrOrObject);
        return implode('', $keys) !== implode(range(0, count($keys) - 1));
        // @codeCoverageIgnoreEnd
    }

    /**
     * Check if the string has space or not.
     *
     * @param string $str The string to be checked
     *
     * @return boolean - True if it has space, false if it doesn't
     */
    public static function hasSpace($str)
    {
        return preg_match('/\s/', $str) > 0;
    }


    /**
     * Create a new URL based on the parsed_url output
     *
     * Code taken from Linguise Script Core Helper
     *
     * @param array   $parsed_url The parsed URL
     * @param boolean $encoded    Should we encode the URL or not.
     *
     * @return string
     */
    public static function buildUrl($parsed_url, $encoded = \false)
    {
        $final_url = '';
        if (empty($parsed_url['scheme'])) {
            $final_url .= '//';
        } else {
            $final_url .= $parsed_url['scheme'] . '://';
        }

        if (!empty($parsed_url['user'])) {
            $final_url .= $parsed_url['user'];
            if (!empty($parsed_url['pass'])) {
                $final_url .= ':' . $parsed_url['pass'];
            }
            $final_url .= '@';
        }

        $final_url .= empty($parsed_url['host']) ? '' : $parsed_url['host'];

        if (!empty($parsed_url['port'])) {
            $final_url .= ':' . $parsed_url['port'];
        }

        if (!empty($parsed_url['path'])) {
            if ($encoded) {
                $explode_path = array_map('rawurlencode', explode('/', $parsed_url['path']));
                $final_url .= implode('/', $explode_path);
            } else {
                $final_url .= $parsed_url['path'];
            }
        }

        if (!empty($parsed_url['query'])) {
            $final_url .= '?' . $parsed_url['query'];
        }

        if (!empty($parsed_url['fragment'])) {
            $final_url .= '#' . $parsed_url['fragment'];
        }

        return $final_url;
    }
}
