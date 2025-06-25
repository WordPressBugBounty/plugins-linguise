<?php

namespace Linguise\WordPress\Frontend;

defined('ABSPATH') || die('No direct script access allowed!');

/**
 * Cookies redirector handler
 *
 * Check for the existence of the `linguise_lang` cookie
 */
class LinguiseCookiesLanguage extends LinguiseRedirector
{
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
        return !empty($options['cookies_redirect']);
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
        $cookie_lang = isset($_COOKIE['linguise_lang']) ? $_COOKIE['linguise_lang'] : null;
        if (empty($cookie_lang)) {
            return null;
        }

        $languages_enabled = isset($options['enabled_languages']) ? $options['enabled_languages'] : array();
        return self::normalizeAndCheckLanguage($cookie_lang, $languages_enabled);
    }
}
