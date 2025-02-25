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

        if (strpos(home_url(add_query_arg(null, null)), $admin_url) === 0) {
            if (0 === strpos(strtolower(wp_get_referer()), $admin_url)) {
                return true;
            } else {
                if (function_exists('wp_doing_ajax')) {
                    return !wp_doing_ajax();
                } else {
                    return !(defined('DOING_AJAX') && DOING_AJAX );
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
            if (isset($languages->$language->wp_code) && !empty($languages->$language->wp_code)) {
                return $languages->$language->wp_code;
            } elseif (!isset($languages->$language->wp_code)) {
                return $language;
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
            return false;
        }

        return $language !== $linguise_options['default_language'] && in_array($language, $linguise_options['enabled_languages']);
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
            return LINGUISE_PLUGIN_URL . $path;
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
}
