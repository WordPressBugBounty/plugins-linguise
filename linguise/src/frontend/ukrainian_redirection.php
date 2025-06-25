<?php

namespace Linguise\WordPress\Frontend;

defined('ABSPATH') || die('No direct script access allowed!');

/**
 * Ukrainian redirection handler
 *
 * If enabled, this will always redirect to the Ukrainian language
 */
class LinguiseUkrainianRedirection extends LinguiseRedirector
{
    /**
     * The name of the cookie used to store (and check) the redirect
     *
     * For Ukrainian redirection, we use different cookie name
     *
     * @var string
     */
    public static $cookie_name = 'LINGUISE_UKRAINE_REDIRECT';

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
        return !empty($options['ukraine_redirect']);
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
        $default_lang = isset($options['default_language']) ? $options['default_language'] : 'en';
        if ($default_lang === 'uk') {
            // Don't enable redirection if the default language is Ukrainian
            return null;
        }

        $languages_enabled = isset($options['enabled_languages']) ? $options['enabled_languages'] : array();
        return self::normalizeAndCheckLanguage('uk', $languages_enabled);
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
        return $current_lang === 'uk';
    }
}
