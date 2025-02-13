<?php

use Linguise\Vendor\Linguise\Script\Core\Boundary;
use Linguise\Vendor\Linguise\Script\Core\Configuration;
use Linguise\Vendor\Linguise\Script\Core\Database;
use Linguise\Vendor\Linguise\Script\Core\Debug;
use Linguise\Vendor\Linguise\Script\Core\Helper;
use Linguise\Vendor\Linguise\Script\Core\Processor;
use Linguise\Vendor\Linguise\Script\Core\Request;
use Linguise\Vendor\Linguise\Script\Core\Translation;
use Linguise\WordPress\FragmentHandler;
use Linguise\WordPress\Helper as WPHelper;

/**
 * Intercept WP-AJAX request
 *
 * Example: https://domain.com/wp-json/wc/store/v1/cart
 *
 * @param WP_REST_Response|WP_HTTP_Response|WP_Error|mixed $response The response object for the WP-AJAX request
 * @param array                                            $handler  The handler for the WP-AJAX request
 * @param WP_REST_Request                                  $request  The request object for the WP-AJAX request
 *
 * @return WP_REST_Response
 */
function linguise_intercept_ajax_request($response, $handler, $request)
{
    if (!defined('LINGUISE_SCRIPT_TRANSLATION')) {
        define('LINGUISE_SCRIPT_TRANSLATION', 1);
    }

    include_once(LINGUISE_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

    linguiseInitializeConfiguration();

    $options = linguiseGetOptions();

    if (!empty(Configuration::getInstance()->get('debug')) && Configuration::getInstance()->get('debug')) {
        if (is_int(Configuration::getInstance()->get('debug'))) {
            $verbosity = Configuration::getInstance()->get('debug');
        } else {
            $verbosity = 0;
        }
        Debug::enable($verbosity, Configuration::getInstance()->get('debug_ip'));
    }

    $route = $request->get_route();

    // Default AJAX to intercept
    $supported_prefix = [];
    $supported_prefix = apply_filters('linguise_ajax_intercept_prefixes', $supported_prefix);
    Debug::log('Intercepting AJAX request: ' . $route);
    Debug::log('Supported prefixes: ' . print_r($supported_prefix, true)); // phpcs:ignore WordPress.PHP.DevelopmentFunctions

    if (empty($supported_prefix)) {
        return $response;
    }

    $selected_prefix = null;
    foreach ($supported_prefix as $prefix) {
        if (strpos($route, $prefix) !== false) {
            $selected_prefix = $prefix;
            break;
        }
    }

    if ($selected_prefix === null) {
        // Unrecognized prefix
        return $response;
    }

    $language = WPHelper::getLanguageFromReferer();

    if ($language === null) {
        return $response;
    }

    // Check if error
    if (is_wp_error($response)) {
        return $response;
    }

    $raw_data = $response->get_data();
    if (!is_array($raw_data)) {
        return $response;
    }

    // Do stricter collection (only do "allow"-ed keys)
    $fragments = FragmentHandler::collectFragmentFromJson($raw_data, true);
    if (empty($fragments)) {
        return $response;
    }

    $woo_prefix = str_replace('/', '-', $selected_prefix);
    $html_fragments = FragmentHandler::intoHTMLFragments('wp-ajax-json', $woo_prefix, [
        'mode' => 'auto',
        'fragments' => $fragments
    ]);

    $content = '<html><head></head><body>';
    $content .= $html_fragments;
    $content .= '</body></html>';

    $options = linguiseGetOptions();
    Configuration::getInstance()->set('token', $options['token']);

    $boundary = new Boundary();
    $request = Request::getInstance();

    $boundary->addPostFields('version', Processor::$version);
    $boundary->addPostFields('url', $request->getBaseUrl());
    $boundary->addPostFields('language', $language);
    $boundary->addPostFields('requested_path', '/wp-json' . $route);
    $boundary->addPostFields('content', $content);
    $boundary->addPostFields('token', Configuration::getInstance()->get('token'));
    $boundary->addPostFields('ip', Helper::getIpAddress());
    $boundary->addPostFields('response_code', 200);
    $boundary->addPostFields('user_agent', !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');

    $ch = curl_init();
    list($translated_content, $response_code) = Translation::getInstance()->_translate($ch, $boundary);
    curl_close($ch);

    if (!$translated_content || $response_code !== 200) {
        // We failed to translate
        return $response;
    }

    $result = json_decode($translated_content);

    // Get the request object
    $req_object = Request::getInstance();

    // Use reflection to access private property language.
    // We do this since the Database use Request::getInstance()->getLanguage()
    // to get the current language which should be "null" or missing in this case
    // if we don't replace it.
    $req_reflect = new \ReflectionClass($req_object);
    $reflect_lang = $req_reflect->getProperty('language');
    $reflect_lang->setAccessible(true);
    // Get the old value first
    $req_language = $req_object->getLanguage();
    // Set the new value from the referer data
    $reflect_lang->setValue($req_object, $language);

    $db_updated = false;
    if (isset($result->url_translations)) {
        $new_urls = get_object_vars($result->url_translations);
        Database::getInstance()->saveUrls((array)$new_urls);
        $db_updated = true;
    }

    if (isset($result->urls_untranslated)) {
        Database::getInstance()->removeUrls((array)$result->urls_untranslated);
        $db_updated = true;
    }

    register_shutdown_function(function () use ($db_updated) {
        if ($db_updated) {
            Database::getInstance()->close();
        }
    });

    // Revert the language back
    $reflect_lang->setValue($req_object, $req_language);

    if (isset($result->redirect)) {
        // Somehow we got this...?
        return $response;
    }

    $translated_fragments = FragmentHandler::intoJSONFragments($result->content);
    if (empty($translated_fragments)) {
        return $response;
    }

    // Get $translated_fragments['wp-ajax-json'][$woo_prefix]
    if (!isset($translated_fragments['wp-ajax-json'])) {
        return $response;
    }

    if (!isset($translated_fragments['wp-ajax-json'][$woo_prefix])) {
        return $response;
    }

    $tl_json_frag = $translated_fragments['wp-ajax-json'][$woo_prefix];
    if (empty($tl_json_frag)) {
        return $response;
    }

    $tl_json_frag_list = $tl_json_frag['fragments'];
    if (empty($tl_json_frag_list)) {
        return $response;
    }

    $replaced_content = FragmentHandler::applyTranslatedFragmentsForAuto($raw_data, $tl_json_frag_list);
    if ($replaced_content !== false) {
        $response->set_data($replaced_content);
    }

    return $response;
}

add_filter('rest_request_after_callbacks', 'linguise_intercept_ajax_request', 1000, 3);
