<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');

/**
 * Class LinguiseBrowserLanguage
 */
class LinguiseBrowserLanguage
{
    /**
     * LinguiseBrowserLanguage constructor.
     *
     * @return void
     */
    public function __construct()
    {
        add_action('init', array($this, 'linguiseInit'), 11);
    }

    /**
     * Init
     *
     * @return void
     */
    public function linguiseInit()
    {
        if (empty($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'GET' || is_admin() || wp_doing_ajax() || $GLOBALS['pagenow'] === 'wp-login.php') {
            return;
        }

        $options = linguiseGetOptions();

        if (empty($options['browser_redirect'])) {
            return;
        }

        if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && !isset($_SERVER['HTTP_CF_IPCOUNTRY'])) { //phpcs:ignore
            return;
        }

        if (!empty($_COOKIE['LINGUISE_REDIRECT'])) {
            return;
        }

        $home_url = home_url();

        if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], $home_url) === 0) {
            // Do not redirect if we call from internally
            return;
        }

        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) { //phpcs:ignore
            $browser_language = substr(sanitize_text_field($_SERVER['HTTP_ACCEPT_LANGUAGE']), 0, 2); // fixme: won't work for zh-cn
        } elseif (isset($_SERVER['HTTP_CF_IPCOUNTRY'])) { // phpcs:ignore
            // Cloudfare Compatibility
            $browser_language = strtolower($_SERVER['HTTP_CF_IPCOUNTRY']); //phpcs:ignore
        } else {
            // No browser language found
            return;
        }

        if (isset($_SERVER['HTTP_LINGUISE_ORIGINAL_LANGUAGE'])) {
            $url_language = $_SERVER['HTTP_LINGUISE_ORIGINAL_LANGUAGE'];
        } else {
            $url_language = isset($options['default_language']) ? $options['default_language'] : 'en';
        }

        if ($url_language === $browser_language) {
            return;
        }

        // Get from module parameters the enable languages
        $languages_enabled = isset($options['enabled_languages']) ? $options['enabled_languages'] : array();

        if (!in_array($browser_language, $languages_enabled)) {
            // This is not a language we have activated
            return;
        }

        $base = rtrim(linguiseForceRelativeUrl(site_url()), '/');
        $original_path = rtrim(substr(rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'), strlen($base)), '/');
        $protocol = 'http';
        if (strpos($home_url, 'https') === 0) {
            $protocol = 'https';
        }

        if (!$base) {
            $base = '';
        }
        $url_auto_redirect = $protocol . '://' . $_SERVER['HTTP_HOST'] . $base . '/' . $browser_language . $original_path;
        if (!empty($_SERVER['QUERY_STRING'])) {
            $url_auto_redirect .= '/?' . $_SERVER['QUERY_STRING'];
        }
        setcookie('LINGUISE_REDIRECT', 1, time() + 20);
        header('Linguise-Translated-Redirect: 1');
        header('Cache-Control: no-cache, must-revalidate, max-age=0');
        header('Location: ' . $url_auto_redirect, true, 302);
        exit();
    }
}

new LinguiseBrowserLanguage;
