<?php

use Linguise\Vendor\Linguise\Script\Core\Boundary;
use Linguise\Vendor\Linguise\Script\Core\Configuration;
use Linguise\Vendor\Linguise\Script\Core\Database;
use Linguise\Vendor\Linguise\Script\Core\Helper;
use Linguise\Vendor\Linguise\Script\Core\Processor;
use Linguise\Vendor\Linguise\Script\Core\Request;
use Linguise\Vendor\Linguise\Script\Core\Translation;
use Linguise\WordPress\FragmentHandler;
use Linguise\WordPress\Helper as WPHelper;

include_once ABSPATH . 'wp-admin/includes/plugin.php';
if (!is_plugin_active('facetwp/index.php')) {
    return;
}

add_filter('linguise_fragment_filters', function ($filters) {
    $filters[] = [
        'key' => 'facets\..*',
        'mode' => 'regex_full',
        'kind' => 'allow'
    ];

    $filters[] = [
        'key' => 'settings\.labels\..*',
        'mode' => 'regex_full',
        'kind' => 'allow'
    ];

    $filters[] = [
        'key' => 'sort',
        'mode' => 'path',
        'kind' => 'allow',
    ];

    // Disallow this
    $filters[] = [
        'key' => 'nonce',
        'mode' => 'path',
        'kind' => 'deny',
    ];

    $filters[] = [
        'key' => 'preload_data\.settings\.(num_choices|pager)\..*',
        'mode' => 'regex_full',
        'kind' => 'deny',
    ];

    $filters[] = [
        'key' => 'preload_data.template',
        'mode' => 'path',
        'kind' => 'deny',
    ];

    $filters[] = [
        'key' => 'preload_data\.settings\.(\w+)\.show_expanded',
        'mode' => 'regex_full',
        'kind' => 'deny',
    ];

    return $filters;
}, 10, 1);

add_filter('linguise_fragment_override', function ($override) {
    $override[] = [
        'name' => 'facetwp-json',
        'match' => 'window\.FWP_JSON = (.*?);',
        'replacement' => 'window.FWP_JSON = $$JSON_DATA$$;',
    ];

    return $override;
}, 10, 1);

add_filter('facetwp_render_output', function ($output) {
    if (!defined('LINGUISE_SCRIPT_TRANSLATION')) {
        define('LINGUISE_SCRIPT_TRANSLATION', 1);
    }

    include_once(LINGUISE_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

    linguiseInitializeConfiguration();

    $options = linguiseGetOptions();

    $language = WPHelper::getLanguage();

    if ($language === null) {
        $language = WPHelper::getLanguageFromReferer();
    }

    if ($language === null) {
        return $output;
    }

    // Add template to output
    add_filter('linguise_fragment_filters', function ($filters) {
        $filters[] = [
            'key' => 'template',
            'mode' => 'path',
            'kind' => 'allow',
            // Since sometimes this return link
            'cast' => 'html-main',
        ];

        return $filters;
    }, 15, 1);

    // Loop through facets
    $fragments = FragmentHandler::collectFragmentFromJson($output, true);
    if (empty($fragments)) {
        return $output;
    }

    $html_fragments = FragmentHandler::intoHTMLFragments('facetwp-filters', 'render-output', [
        'mode' => 'auto',
        'fragments' => $fragments,
    ]);

    $content = '<html><head></head><body>';
    $content .= $html_fragments;
    $content .= '</body></html>';

    Configuration::getInstance()->set('token', $options['token']);

    $boundary = new Boundary();
    $request = Request::getInstance();

    $boundary->addPostFields('version', Processor::$version);
    $boundary->addPostFields('url', $request->getBaseUrl());
    $boundary->addPostFields('language', $language);
    $boundary->addPostFields('requested_path', '/facetwp-refresh');
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
        return $output;
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
        return $output;
    }

    $translated_fragments = FragmentHandler::intoJSONFragments($result->content);
    if (empty($translated_fragments)) {
        return $output;
    }

    // Get $translated_fragments['facetwp-filters']['render-output']
    if (!isset($translated_fragments['facetwp-filters'])) {
        return $output;
    }

    if (!isset($translated_fragments['facetwp-filters']['render-output'])) {
        return $output;
    }

    $tl_json_frag = $translated_fragments['facetwp-filters']['render-output'];
    if (empty($tl_json_frag)) {
        return $output;
    }

    $tl_json_frag_list = $tl_json_frag['fragments'];
    if (empty($tl_json_frag_list)) {
        return $output;
    }

    $replaced_content = FragmentHandler::applyTranslatedFragmentsForAuto($output, $tl_json_frag_list);
    if ($replaced_content !== false) {
        return $replaced_content;
    }

    return $output;
}, 1000, 1);
