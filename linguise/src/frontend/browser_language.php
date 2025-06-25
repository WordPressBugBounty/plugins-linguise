<?php

namespace Linguise\WordPress\Frontend;

defined('ABSPATH') || die('No direct script access allowed!');

/**
 * Browser language redirector handler
 *
 * Compare the Accept-Language header with the available languages
 */
class LinguiseBrowserLanguage extends LinguiseRedirector
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
        return !empty($options['browser_redirect']);
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
        if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && !isset($_SERVER['HTTP_CF_IPCOUNTRY'])) { //phpcs:ignore
            return null;
        }

        // Get from module parameters for the enabled languages
        $languages_enabled = isset($options['enabled_languages']) ? $options['enabled_languages'] : array();
        $current_language = isset($options['default_language']) ? $options['default_language'] : null;

        if (!empty($current_language) && !in_array($current_language, $languages_enabled)) {
            // If the default language is not in the enabled languages, add it
            $languages_enabled[] = $current_language;
        }

        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $accept_language = sanitize_text_field($_SERVER['HTTP_ACCEPT_LANGUAGE']);
            $accept_languages = self::splitAcceptLanguage($accept_language);
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

                $language = self::normalizeAndCheckLanguage($accept_language['lang'], $languages_enabled);
                if (!empty($language)) {
                    // Found a match
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

            return self::normalizeAndCheckLanguage($cf_ipcountry, $languages_enabled);
        } else {
            // Nothing
            return null;
        }
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
    private static function splitAcceptLanguage($accept_language)
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
