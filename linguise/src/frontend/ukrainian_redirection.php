<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');

/**
 * Class LinguiseUkrainianRedirection
 */
class LinguiseUkrainianRedirection
{
    /**
     * LinguiseUkrainianRedirection constructor.
     *
     * @return void
     */
    public function __construct()
    {
        add_action('init', array($this, 'linguiseInit'), 10);
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

        if (empty($options['ukraine_redirect'])) {
            return;
        }

        if (!empty($_COOKIE['LINGUISE_UKRAINE_REDIRECT'])) {
            return;
        }

        $home_url = home_url();

        if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], $home_url) === 0) {
            // Do not redirect if we call from internally
            return;
        }

        $default_language = $options['default_language'] ?? 'en';

        // Get the language the visitor loaded the page from
        if (isset($_SERVER['HTTP_LINGUISE_ORIGINAL_LANGUAGE'])) {
            $requested_language = $_SERVER['HTTP_LINGUISE_ORIGINAL_LANGUAGE'];
        } else {
            $requested_language = $default_language;
        }

        if ($requested_language === 'uk') {
            // No need to redirect if the visitor already loaded ukrainian language
            return;
        }

        // Get from module parameters the enable languages
        $languages_enabled = isset($options['enabled_languages']) ? $options['enabled_languages'] : array();

        if (!in_array('uk', $languages_enabled, true) && $default_language !== 'uk') {
            // No need to redirect if ukrainian language is not enabled
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
        $url_auto_redirect = $protocol . '://' . $_SERVER['HTTP_HOST'] . $base . ($default_language === 'uk' ? '' : '/uk') . $original_path;
        if (!empty($_SERVER['QUERY_STRING'])) {
            $url_auto_redirect .= '/?' . $_SERVER['QUERY_STRING'];
        }
        setcookie('LINGUISE_UKRAINE_REDIRECT', 1, time() + 20);
        header('Linguise-Translated-Redirect: 1');
        header('Cache-Control: no-cache, must-revalidate, max-age=0');
        header('Location: ' . $url_auto_redirect, true, 302);
        exit();
    }
}

new LinguiseUkrainianRedirection();
