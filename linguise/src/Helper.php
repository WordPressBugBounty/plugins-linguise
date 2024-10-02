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
     * Check if the passed language is translatable
     *
     * @param string $language Language code to check
     *
     * @return boolean
     */
    public static function isTranslatableLanguage($language)
    {
        $linguise_options = get_option('linguise_options');

        return $language !== $linguise_options['default_language'] && in_array($language, $linguise_options['enabled_languages']);
    }
}
