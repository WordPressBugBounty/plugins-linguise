<?php

use Linguise\Vendor\Linguise\Script\Core\Boundary;
use Linguise\Vendor\Linguise\Script\Core\Configuration;
use Linguise\Vendor\Linguise\Script\Core\Request;
use Linguise\Vendor\Linguise\Script\Core\Database;
use Linguise\Vendor\Linguise\Script\Core\Helper;
use Linguise\Vendor\Linguise\Script\Core\Processor;
use Linguise\Vendor\Linguise\Script\Core\Translation;
use Linguise\WordPress\FragmentHandler;
use Linguise\WordPress\Helper as WPHelper;

include_once ABSPATH . 'wp-admin/includes/plugin.php';
if (!is_plugin_active('surecart/surecart.php')) {
    return;
}

/**
 * Translate surecart block
 *
 * @param string    $block_content Block content.
 * @param array     $block         Block data.
 * @param \WP_Block $instance      Block instance.
 *
 * @return string
 */
function linguiseTranslateSurecartBlockContent($block_content, $block, $instance)
{
    // Process each context
    if (!defined('LINGUISE_SCRIPT_TRANSLATION')) {
        define('LINGUISE_SCRIPT_TRANSLATION', 1);
    }

    include_once(LINGUISE_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

    linguiseInitializeConfiguration();

    $options = linguiseGetOptions();

    $language = WPHelper::getLanguage();
    if (!$language) {
        $language = WPHelper::getLanguageFromReferer();
    }

    if (!$language) {
        return $block_content;
    }

    $block_name = 'wp-block_' . $instance->name;

    // Find all data-wp-context data
    preg_match_all('/data-wp-context=\'{(.*)}\'/', $block_content, $matches, PREG_SET_ORDER, 0);

    // We then extract the context
    $contexts = [];
    foreach ($matches as $match) {
        $contexts[] = [
            'context' => $match[0],
            'data' => json_decode('{' . $match[1] . '}'),
        ];
    }

    if (empty($contexts)) {
        return $block_content;
    }

    $content = '<html><head></head><body>';
    $empty_data = true;
    $contexts_length = count($contexts);
    for ($index = 0; $index < $contexts_length; $index++) {
        $context = $contexts[$index];
        if (empty($context['data'])) {
            continue;
        }
        $fragments = FragmentHandler::collectFragmentFromJson($context['data']);

        if (empty($fragments)) {
            continue;
        }

        $prefix = 'index-' . $index;
        $html_fragments = FragmentHandler::intoHTMLFragments($block_name, $prefix, [
            'mode' => 'auto',
            'fragments' => $fragments
        ]);

        $content .= $html_fragments;
        $empty_data = false;
    }
    $content .= '</body></html>';

    if ($empty_data) {
        return $block_content;
    }

    Configuration::getInstance()->set('token', $options['token']);

    $boundary = new Boundary();
    $request = Request::getInstance();

    $boundary->addPostFields('version', Processor::$version);
    $boundary->addPostFields('url', $request->getBaseUrl());
    $boundary->addPostFields('language', $language);
    $boundary->addPostFields('requested_path', '/wp-block/' . $block_name);
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
        return $block_content;
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
        return $block_content;
    }

    $translated_fragments = FragmentHandler::intoJSONFragments($result->content);
    if (empty($translated_fragments)) {
        return $block_content;
    }

    if (!isset($translated_fragments[$block_name])) {
        return $block_content;
    }

    $wp_block_fragments = $translated_fragments[$block_name];
    foreach ($wp_block_fragments as $key_prefix => $tl_json_frag) {
        // We replace data-wp-context according to the key-prefix
        // key-preifx is formatted "index-${index}"
        $index = (int)str_replace('index-', '', $key_prefix);
        // Find the context
        $context = $contexts[$index];
        if (empty($context)) {
            continue;
        }

        $tl_json_frag_list = $tl_json_frag['fragments'];
        if (empty($tl_json_frag_list)) {
            continue;
        }

        $replaced_content = FragmentHandler::applyTranslatedFragmentsForAuto($context['data'], $tl_json_frag_list);
        if ($replaced_content !== false) {
            $whole_content = 'data-wp-context=\'' . json_encode($replaced_content) . '\'';
            $block_content = str_replace($context['context'], $whole_content, $block_content);
        }
    }

    return $block_content;
}

add_filter('render_block_surecart/product-buy-button', 'linguiseTranslateSurecartBlockContent', 1000, 3);
add_filter('render_block_surecart/product-page', 'linguiseTranslateSurecartBlockContent', 1000, 3);

// Init
add_filter('linguise_ajax_intercept_prefixes', function ($prefixes) {
    $prefixes[] = 'surecart/v1/line_items';
    $prefixes[] = 'surecart/v1/checkouts';

    return $prefixes;
});

add_filter('linguise_fragment_override', function ($fragments) {
    $fragments[] = [
        'name' => 'surecart-store-data',
        'key' => 'sc-store-data',
        'mode' => 'app_json',
    ];

    $fragments[] = [
        'name' => '',
        'match' => 'sc-checkout-product-price-variant-selector(.+)component\.product = (.+);(.*)}\)\(\);',
        'replacement' => '$1component.product = $$JSON_DATA$$;$3})();',
        'position' => 2,
        'strict' => true,
    ];

    return $fragments;
});

add_filter('linguise_fragment_filters', function ($filters) {
    // For WP REST API related
    $path_key_match = [
        'name',
        'description',
        'metadata.page_url',
        'human_discount',
        'human_discount_with_duration',
        'discount.redeemable_display_status',
        'purchasable_status_display',
        'permalink',
        'page_title',
        'meta_description',
    ];
    $product_items_regex = [
        '^variant_options\.\d+$',
        '^variant_option_names\.\d+$',
        '^variant\.(option_\d+|option_names\.\d+)$',
        '^variants\.data\.\d+\.(option_\d+|option_names\.\d+)',
        '^variant_options\.data\.(name|values\.\d+)$',
        '^(active_prices|initial_price)\.\d+\.(name|display_amount|interval_text|interval_count_text|trial_text)',
    ];

    $line_items_regex = [
        '^price\.name',
        '^price\.product\.(name|description|permalink|checkout_permalink|page_title|meta_description)',
        '^price\.product\.post\.(post_title|post_excerpt|guid)',
    ];

    foreach ($product_items_regex as $product_key) {
        $line_items_regex[] = '^price\.product\.' . str_replace('^', '', $product_key);
    }

    $checkout_items_regex = [
        '^shipping_choices\.data\.\d+\.shipping_method\.(name|description)',
    ];
    foreach ($line_items_regex as $line_item_key) {
        $checkout_items_regex[] = '^line_items\.data\.\d+\.' . str_replace('^', '', $line_item_key);
    }

    $regex_key_match = array_merge($product_items_regex, $line_items_regex, $checkout_items_regex);
    foreach ($checkout_items_regex as $checkout_key) {
        $regex_key_match[] = '^checkout\.' . str_replace('^', '', $checkout_key);
    }

    foreach ($path_key_match as $path_key) {
        $filters[] = [
            'key' => $path_key,
            'mode' => 'path',
            'kind' => 'allow',
        ];
        $filters[] = [
            // Recursive
            'key' => 'checkout.' . $path_key,
            'mode' => 'path',
            'kind' => 'allow',
        ];
    }
    foreach ($regex_key_match as $regex_key) {
        $filters[] = [
            'key' => $regex_key,
            'mode' => 'regex_full',
            'kind' => 'allow',
        ];
    }

    // Disallowed keys
    $regex_full_key_disallowed = [
        '^checkout\.taxProtocol\.address\..*',
        '^user\.(email|name)',
    ];

    foreach ($regex_full_key_disallowed as $regex_full_key) {
        $filters[] = [
            'key' => $regex_full_key,
            'mode' => 'regex_full',
            'kind' => 'deny',
        ];
    }

    return $filters;
});
