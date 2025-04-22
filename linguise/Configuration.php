<?php

namespace Linguise\Script;

use Linguise\Vendor\Linguise\Script\Core\Response;
use Linguise\Vendor\Linguise\Script\Core\Request;
use Linguise\WordPress\AttributeHandler;
use Linguise\WordPress\FragmentHandler;

defined('LINGUISE_SCRIPT_TRANSLATION') || die('');

/**
 * Configuration
 */
class Configuration
{
    /**
     * WordPress CMS
     *
     * @var string
     */
    public static $cms = 'wordpress';

    /**
     * List of WordPress strings we found to translate
     *
     * @var array
     */
    protected static $WPOriginalStrings = [];

    /**
     * Extracted json data from WordPress vars
     *
     * @var array
     */
    protected static $WPJsonData = [];

    /**
     * List of nested variables we have json_decoded
     * We'll need to json_encode it back before rendering
     *
     * @var array
     */
    protected static $WPJsonDecoded = [];

    /**
     * List of script and variables to look into
     * We'll add the script to look for depending on the enabled plugins
     *
     * @var array
     * dev can add their own WP vars by hooking into ConfigurationLocal.php and fill this array as their convenience
     */
    protected static $WPKnownVars = [];

    /**
     * Hook onBeforeTranslation
     *
     * @return void
     */
    public static function onBeforeTranslation()
    {
        $request = Request::getInstance();

        if (strpos($request->getPathname(), 'wp-json/oembed/1.0/embed') !== false) {
            return;
        }

        self::excludeAdminBar();

        // Extract WordPress translation from Scripts
        self::wpFragmentsScriptExtraction();
    }

    /**
     * Hook onAfterTranslation
     *
     * @return void
     */
    public static function onAfterTranslation()
    {
        $request = Request::getInstance();

        if (strpos($request->getPathname(), 'wp-json/oembed/1.0/embed') !== false) {
            return;
        }

        // Insert back WordPress translation into Scripts
        self::wpFragmentsScriptInsertion();
    }

    /**
     * Do not translate the adminbar
     *
     * @return void
     */
    protected static function excludeAdminBar()
    {
        $response = Response::getInstance();
        $content  = $response->getContent();
        $content = str_replace('<div id="wpadminbar" ', '<div id="wpadminbar" translate="no" ', $content);
        $response->setContent($content);
    }

    /**
     * Parse the content to extract JavaScript JSON encoded translatable variables
     *
     * @return void
     */
    protected static function wpFragmentsScriptExtraction()
    {
        $response = Response::getInstance();
        $content  = $response->getContent();

        $all_fragments = FragmentHandler::findWPFragments($content);
        $attrs_fragments = AttributeHandler::findWPFragments($content);
        $merged_fragments = array_merge($all_fragments, $attrs_fragments);

        $newContent = $content;
        foreach ($merged_fragments as $frag_key => $frag_json) {
            foreach ($frag_json as $frag_param => $frag_values) {
                $frag_as_html = FragmentHandler::intoHTMLFragments($frag_key, $frag_param, $frag_values);
                $newContent = str_replace('</body>', $frag_as_html . '</body>', $newContent);
            }
        }

        $response->setContent($newContent);
    }

    /**
     * Insert back all translated strings
     *
     * @return void
     */
    protected static function wpFragmentsScriptInsertion()
    {
        $response = Response::getInstance();
        $content = $response->getContent();

        $all_fragments = FragmentHandler::intoJSONFragments($content);
        try {
            $translatedHtml = FragmentHandler::injectTranslatedWPFragments($content, $all_fragments);
        } catch (\LogicException $e) {
            $translatedHtml = $content;
        }
        $attrs_fragments = AttributeHandler::intoJSONFragments($translatedHtml);
        try {
            $translatedHtml = AttributeHandler::injectTranslatedWPFragments($translatedHtml, $attrs_fragments);
        } catch (\LogicException $e) {
            $translatedHtml = $content;
        }

        $response->setContent($translatedHtml);
    }

    /**
     * Remove token after user set new password
     *
     * @return void
     */
    public static function onBeforeRedirect()
    {
        $response = Response::getInstance();
        $cookies = $response->getCookies();
        foreach ($cookies as $index => $cookie) {
            if (strpos($cookie, 'wp-resetpass') !== false) {
                /**
                 * If set cookie into translated page sometimes not working for Cyrillic alphabet, i.e: Russian
                 * Set path into  / to work in translated and non translated URL.
                 */
                $cookies[$index]->setPath('/');
            }
        }
    }

    /**
     * We should translate JSON request to oembed
     *
     * @return boolean
     */
    public static function onJsonShouldTranslate()
    {
        $request = Request::getInstance();

        if (strpos($request->getPathname(), 'wp-json/oembed/1.0/embed') !== false) {
            return true;
        }

        return false;
    }
}
