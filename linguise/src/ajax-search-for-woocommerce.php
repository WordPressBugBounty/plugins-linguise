<?php
/**
 * Ajax Search for WooCommerce a.k.a FiboSearch
 */
use Linguise\Vendor\Linguise\Script\Core\Request;
use Linguise\Vendor\Linguise\Script\Core\Boundary;
use Linguise\Vendor\Linguise\Script\Core\Configuration;
use Linguise\Vendor\Linguise\Script\Core\Database;
use Linguise\Vendor\Linguise\Script\Core\Processor;
use Linguise\Vendor\Linguise\Script\Core\Translation;
use Linguise\WordPress\FragmentHandler;
use Linguise\WordPress\Helper as WPHelper;
use Linguise\Vendor\Linguise\Script\Core\Helper;

include_once ABSPATH . 'wp-admin/includes/plugin.php';
if (!is_plugin_active('ajax-search-for-woocommerce/ajax-search-for-woocommerce.php')) {
    return;
}

/**
 * Translate ajax-search-for-woocommerce labels
 */
add_filter('linguise_fragment_override', function ($fragments) {
    if (!WPHelper::getLanguage()) {
        return $fragments;
    }

    $fragments[] = [
        'name' => 'ajax-search-for-woocommerce',
        'key' => 'jquery-dgwt-wcas-js-extra',
        'match' => 'var dgwt_wcas = (.*?);',
        'replacement' => 'var dgwt_wcas = $$JSON_DATA$$;',
    ];

    return $fragments;
});

/**
 * Exclude some keys
 */
add_filter('linguise_fragment_filters', function ($filters) {
    
    if (!WPHelper::getLanguage()) {
        return $filters;
    }

    // Disallowed keys
    $regex_full_key_disallowed = [
        'img_url',
        'magnifier_icon',
        'custom_params'
    ];

    foreach ($regex_full_key_disallowed as $regex_full_key) {
        $filters[] = [
            'key' => $regex_full_key,
            'mode' => 'regex_full',
            'kind' => 'deny',
        ];
    }

    /**
     * Allow translate labels.no_results key
     */
    $filters[] = [
        'key' => 'labels\.no_results',
        'mode' => 'regex_full',
        'kind' => 'allow',
    ];

    return $filters;
});

/**
 * Append linguise lang to FiboSearch ajax endpoint url
 */
add_filter('dgwt/wcas/endpoint/search', function ($url) {
    $linguise_language = WPHelper::getLanguage();
    
    if (!$linguise_language) {
        return $url;
    }

    $url_with_language = add_query_arg('linguise_language', $linguise_language, $url);
    
    return $url_with_language;
});

add_filter('dgwt/wcas/phrase', function ($keyword) {
    $is_ajax = defined('DGWT_WCAS_AJAX');
    $linguise_language = WPHelper::getLanguage();

    if (!$is_ajax || !$linguise_language) {
        // return original keyword
        return $keyword;
    }

    $options = linguiseGetOptions();

    if (!$options['search_translation']) {
        return $keyword;
    }

    $translation = \Linguise\Vendor\Linguise\Script\Core\Translation::getInstance()->translateJson(
        ['search' => $keyword],
        linguiseGetSite(),
        $linguise_language
    );

    if (empty($translation->search)) {
        return $keyword;
    }

    $keyword = $translation->search;

    return $keyword;
});

/**
 * Translate ajax result from FiboSearch
 */
add_filter('dgwt/wcas/search_results/output', function ($output) {
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
            'key' => 'value',
            'mode' => 'regex_full',
            'kind' => 'allow',
        ];

        $filters[] = [
            'key' => 'url',
            'mode' => 'regex_full',
            'kind' => 'allow',
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
});
