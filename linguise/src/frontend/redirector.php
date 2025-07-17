<?php

namespace Linguise\WordPress\Frontend;

use Linguise\Vendor\Linguise\Script\Core\Database;
use Linguise\Vendor\Linguise\Script\Core\Helper;
use Linguise\WordPress\Helper as WPHelper;

defined('ABSPATH') || die('No direct script access allowed!');

/**
 * Base class for redirector system
 */
class LinguiseRedirector
{
    /**
     * The name of the cookie used to store (and check) the redirect
     *
     * Default value is `LINGUISE_REDIRECT`
     *
     * @var string
     */
    public static $cookie_name = 'LINGUISE_REDIRECT';

    /**
     * Normalization of some languages
     *
     * @var string[]
     */
    protected static $normalization = [
        'nb' => 'no',
        'nn' => 'no',
        'mri' => 'mi',
        'bel' => 'be',
    ];

    /**
     * Allowed methods for the redirector
     *
     * @var string[]
     */
    protected static $allowed_methods = [
        'GET',
        'HEAD',
        'OPTIONS',
    ];

    /**
     * Check if the redirector is enabled
     *
     * This function should be overridden in the child class
     *
     * @param array $options Provided linguise options
     *
     * @return boolean
     */
    public static function isEnabled($options)
    {
        return false;
    }

    /**
     * Get the target redirection language
     *
     * This function should be overridden in the child class,
     * you should always normalize the language before returning it
     *
     * @param array $options Provided linguise options
     *
     * @return string|null Language to redirect to
     */
    public static function getRedirectLanguage($options)
    {
        return null;
    }

    /**
     * Check if the current language is the target language
     *
     * The default behaviour is to just check if the two languages are the same
     *
     * @param string $target_lang  Target language, the one we want to redirect to
     * @param string $current_lang Current language, the one we are currently in
     *
     * @return boolean
     */
    public static function isInTargetLanguage($target_lang, $current_lang)
    {
        return $target_lang === $current_lang;
    }

    /**
     * The main function that check and redirect the user
     *
     * @return void
     */
    public static function startRedirect()
    {
        if (empty($_SERVER['REQUEST_METHOD']) || !in_array($_SERVER['REQUEST_METHOD'], static::$allowed_methods) || is_admin() || wp_doing_ajax() || $GLOBALS['pagenow'] === 'wp-login.php') {
            return;
        }

        $options = linguiseGetOptions();

        if (!empty($_COOKIE[static::$cookie_name])) {
            return;
        }

        if (!static::isEnabled($options)) {
            return;
        }

        $home_url = home_url();

        if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], $home_url) === 0) {
            // Do not redirect if we call from internally
            return;
        }

        // Cleanup double slashes (or more) in the REQUEST_URI
        $request_uri = ltrim($_SERVER['REQUEST_URI'], '/');
        if (empty($request_uri)) {
            $request_uri = '/';
        }

        // Check if starts with a slash
        if (substr($request_uri, 0, 1) !== '/') {
            $request_uri = '/' . $request_uri;
        }

        $languages_enabled = isset($options['enabled_languages']) ? $options['enabled_languages'] : array();
        if (isset($_SERVER['HTTP_LINGUISE_ORIGINAL_LANGUAGE'])) {
            $url_language = $_SERVER['HTTP_LINGUISE_ORIGINAL_LANGUAGE'];
        } else {
            $url_language = WPHelper::getLanguageFromUrl($request_uri);
        }

        $url_language = static::normalizeAndCheckLanguage($url_language, $languages_enabled);
        if (empty($url_language)) {
            $url_language = isset($options['default_language']) ? $options['default_language'] : 'en';
        }

        $redirect_language = static::getRedirectLanguage($options);
        if (empty($redirect_language)) {
            return;
        }

        $default_lang = isset($options['default_language']) ? $options['default_language'] : 'en';
        if ($default_lang === $redirect_language) {
            // Don't enable redirection if the default language is the same as the redirect language
            return;
        }

        if (static::isInTargetLanguage($redirect_language, $url_language)) {
            // No need to redirect if the visitor already loaded the target language
            return;
        }

        require_once LINGUISE_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

        $orig_site = parse_url(linguiseGetSite());

        $url_path_orig = parse_url($request_uri, PHP_URL_PATH);
        if (empty($url_path_orig)) {
            $url_path_orig = '/';
        }
        if (empty($orig_site['path'])) {
            $url_path = rtrim($url_path_orig, '/');
            $base_path = '';
        } else {
            $url_path = rtrim($url_path_orig, '/');
            $site_url = rtrim($orig_site['path'], '/');
            if (empty($site_url)) {
                $site_url = '/';
            }
            $base_path = rtrim($site_url, '/');
        }

        if (!empty($base_path) && $base_path !== '/') {
            // Trim the base URL from the URL path
            $original_path = rtrim(substr($url_path, strlen($base_path)), '/');
        } else {
            $original_path = $url_path;
        }

        if (!empty($original_path)) {
            $translated_url = Database::getInstance()->getTranslatedUrl($original_path);
            if (empty($translated_url)) {
                $translated_url = $original_path;
            }
        } else {
            $translated_url = '/';
        }

        // Check if the URL has ending slashes
        if (substr($url_path_orig, -1) === '/' && $url_path_orig !== '/') {
            // Add ending slashes
            $translated_url = rtrim($translated_url, '/') . '/';
        }
        if (substr($url_path_orig, 0, 1) !== '/') {
            // Add starting slashes
            $translated_url = '/' . ltrim($translated_url, '/');
        }

        $parsed_url = parse_url(linguiseGetSite());
        if (strpos($base_path, '/') === 0) {
            $base_path = substr($base_path, 1);
        }
        $parsed_url['path'] = '/' . rtrim($base_path, '/') . '/' . $redirect_language . $translated_url;
        if (empty($parsed_url['query']) && !empty($_SERVER['QUERY_STRING'])) {
            $parsed_url['query'] = $_SERVER['QUERY_STRING'];
        }

        $final_url = Helper::buildUrl($parsed_url);
        setcookie(static::$cookie_name, '1', time() + 60);
        header('Linguise-Translated-Redirect: 1');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Location: ' . $final_url, true, 302);
        exit();
    }

    /**
     * Normalize and check if a language is enabled.
     *
     * @param string|null $language            Language to check
     * @param array       $supported_languages Languages enabled
     *
     * @return string|null Language if enabled, null otherwise
     */
    protected static function normalizeAndCheckLanguage($language, $supported_languages)
    {
        if (empty($language)) {
            // Sanity check
            return null;
        }

        // Lowercase it
        $language = strtolower($language);
        // Replace underscore with dash
        $language = str_replace('_', '-', $language);

        if (isset(self::$normalization[$language])) {
            $language = self::$normalization[$language];
        }

        // Try to split the language
        $lang_simple = substr($language, 0, 2);

        // Check if the language is enabled
        if (in_array($lang_simple, $supported_languages)) {
            return $lang_simple;
        }

        // Check if the language is enabled
        if (in_array($language, $supported_languages)) {
            return $language;
        }

        return null;
    }
}
