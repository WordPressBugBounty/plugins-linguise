<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');

/**
 * Class LinguiseBrowserLanguage
 */
class LinguiseBrowserLanguage
{
    /**
     * Normalization of some languages
     *
     * @var string[]
     */
    private static $normalization = [
        'nb' => 'no',
        'nn' => 'no',
        'mri' => 'mi',
        'bel' => 'be',
    ];

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

        $browser_language = $this->getNormalizedBrowserLanguage($options);
        if (empty($browser_language)) {
            // No matching browser language found
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

        $base = rtrim(linguiseForceRelativeUrl(linguiseGetSite()), '/');
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

    /**
     * Gets the browser language from the header and try to normalize it.
     *
     * @param array $options Linguise options
     *
     * @return string|null
     */
    private function getNormalizedBrowserLanguage($options)
    {
        // Get from module parameters for the enabled languages
        $languages_enabled = isset($options['enabled_languages']) ? $options['enabled_languages'] : array();

        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $accept_language = sanitize_text_field($_SERVER['HTTP_ACCEPT_LANGUAGE']);
            $accept_languages = $this->splitAcceptLanguage($accept_language);
            if (empty($accept_languages)) {
                return null;
            }

            // If one preferred lang and it's `*`, return null
            if (count($accept_languages) === 1 && $accept_languages[0]['lang'] === '*') {
                return null;
            }

            // Loop until we found a match
            foreach ($accept_languages as $accept_language) {
                if ($accept_language['lang'] === '*') {
                    // Accept anything? skip
                    continue;
                }

                $language = $this->normalizeAndCheckLanguage($accept_language['lang'], $languages_enabled);
                if (!empty($language)) {
                    return $language;
                }
            }

            // Didn't match anything
            return null;
        } elseif (isset($_SERVER['HTTP_CF_IPCOUNTRY'])) {
            // Cloudfare Compatibility
            $cf_ipcountry = sanitize_text_field($_SERVER['HTTP_CF_IPCOUNTRY']);
            // XX -> Unknown
            // T1 -> Tor network
            if ($cf_ipcountry === 'XX' || $cf_ipcountry === 'T1') {
                return null;
            }

            return $this->normalizeAndCheckLanguage($cf_ipcountry, $languages_enabled);
        } else {
            // Nothing
            return null;
        }
    }

    /**
     * Normalize and check if a language is enabled.
     *
     * @param string $language            Language to check
     * @param array  $supported_languages Languages enabled
     *
     * @return string|null Language if enabled, null otherwise
     */
    private function normalizeAndCheckLanguage($language, $supported_languages)
    {
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

    /**
     * Split the Accept-Language header information into an array
     * of languages with their weights.
     *
     * Weights will be automatically set to 1.0 if not specified and will
     * be sorted in descending order.
     *
     * @param string $accept_language Accept-Language header
     *
     * @return array Array of languages with their weights
     *               e.g. [['lang' => 'en', 'weight' => 1.0], ['lang' => 'fr', 'weight' => 0.8]]
     */
    private function splitAcceptLanguage($accept_language)
    {
        $accept_languages = array_map('trim', explode(',', $accept_language));

        $languages_weighted = [];
        foreach ($accept_languages as $language) {
            // Split again to get the quality
            $language_with_weight = explode(';q=', $language);
            $weight = 1.0;
            if (count($language_with_weight) > 1) {
                $weight = floatval($language_with_weight[1]);
            }
            $languages_weighted[] = [
                'lang' => $language_with_weight[0],
                'weight' => $weight,
            ];
        }
        
        // Sort by weight
        usort($languages_weighted, function ($a, $b) {
            if ($a['weight'] === $b['weight']) {
                return 0;
            }
            return ($a['weight'] > $b['weight']) ? -1 : 1;
        });

        return $languages_weighted;
    }
}

new LinguiseBrowserLanguage;
