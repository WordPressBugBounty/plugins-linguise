<?php

/**
 * Translate woocommerce order
 */

use Linguise\Vendor\Linguise\Script\Core\Boundary;
use Linguise\Vendor\Linguise\Script\Core\Configuration;
use Linguise\Vendor\Linguise\Script\Core\Helper;
use Linguise\Vendor\Linguise\Script\Core\Processor;
use Linguise\Vendor\Linguise\Script\Core\Request;
use Linguise\Vendor\Linguise\Script\Core\Translation;
use Linguise\WordPress\FragmentHandler;
use Linguise\WordPress\Helper as WPHelper;

include_once ABSPATH . 'wp-admin/includes/plugin.php';
if (!is_plugin_active('woocommerce/woocommerce.php')) {
    return;
}

$linguise_options = linguiseGetOptions();

if ($linguise_options['woocommerce_emails_translation']) {
    /**
     * Save the current language at order creation
     */
    add_action('woocommerce_new_order', function ($order_id, $order) {
        if (WPHelper::isAdminRequest()) {
            return;
        }

        $language = WPHelper::getLanguage();
        if (!$language) {
            $language = WPHelper::getLanguageFromReferer();
        }

        if ($language === null) {
            return;
        }

        add_post_meta($order_id, 'linguise_language', $language);

        $order->update_meta_data('linguise_language', $language);
        $order->save_meta_data();
        $order->apply_changes();
    }, 100, 2);

    add_filter('woocommerce_mail_callback_params', function ($args, $wc_email) {
        if (!$wc_email->is_customer_email()) {
            return $args;
        }

        $language_meta = method_exists($wc_email->object, 'get_meta') ? $wc_email->object->get_meta('linguise_language', true) : '';
        if (empty($language_meta)) {
            $language_meta = get_post_meta($wc_email->object->get_id(), 'linguise_language', true);
        }
        $language_fallback = WPHelper::getLanguage();

        $language = $language_meta ? $language_meta : $language_fallback;

        if (!$language || !WPHelper::isTranslatableLanguage($language)) {
            return $args;
        }

        if (!defined('LINGUISE_SCRIPT_TRANSLATION')) {
            define('LINGUISE_SCRIPT_TRANSLATION', 1);
        }

        include_once(LINGUISE_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

        linguiseInitializeConfiguration();

        $options = linguiseGetOptions();
        Configuration::getInstance()->set('token', $options['token']);

        $content = '<html><head></head><body>';
        $content .= '<divlinguisesubject>' . $args[1] . '</divlinguisesubject>';
        $content .= '<divlinguisecontent>' . $args[2] . '</divlinguisecontent>';
        $content .= '</body>';

        $boundary = new Boundary();
        $request = Request::getInstance();

        $boundary->addPostFields('version', Processor::$version);
        $boundary->addPostFields('url', $request->getBaseUrl());
        $boundary->addPostFields('language', $language);
        $boundary->addPostFields('requested_path', '/');
        $boundary->addPostFields('content', $content);
        $boundary->addPostFields('token', Configuration::getInstance()->get('token'));
        $boundary->addPostFields('ip', Helper::getIpAddress());
        $boundary->addPostFields('response_code', 200);
        $boundary->addPostFields('user_agent', !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');

        $ch = curl_init();

        list($translated_content, $response_code) = Translation::getInstance()->_translate($ch, $boundary);

        if (!$translated_content || $response_code !== 200) {
            // We failed to translate
            return $args;
        }

        curl_close($ch);

        $result = json_decode($translated_content);

        preg_match('/<divlinguisesubject>(.*?)<\/divlinguisesubject>/s', $result->content, $matches);
        if (!$matches) {
            return $args;
        }
        $args[1] =  html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');

        preg_match('/<divlinguisecontent>(.*?)<\/divlinguisecontent>/s', $result->content, $matches);
        if (!$matches) {
            return $args;
        }
        $args[2] = $matches[1];

        return $args;
    }, 100, 2);
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- View request, no action
if (!empty($_SERVER['HTTP_LINGUISE_ORIGINAL_LANGUAGE']) && $_SERVER['HTTP_LINGUISE_ORIGINAL_LANGUAGE'] !== $linguise_options['default_language'] && in_array($_SERVER['HTTP_LINGUISE_ORIGINAL_LANGUAGE'], $linguise_options['enabled_languages'])) {
    add_filter('woocommerce_ajax_get_endpoint', function ($endpoint, $request) {
        if ($request === 'checkout') {
            /**
             * Compability for FunnelKit checkout
             *
             * @see https://wordpress.org/plugins/funnel-builder/
             */
            if (is_plugin_active('funnel-builder/funnel-builder.php')) {
                return add_query_arg('linguise_language', $_SERVER['HTTP_LINGUISE_ORIGINAL_LANGUAGE'], $endpoint);
            }

            return str_replace('checkout', 'checkout&linguise_language=' . $_SERVER['HTTP_LINGUISE_ORIGINAL_LANGUAGE'], $endpoint);
        }
        return str_replace('%%endpoint%%', '%%endpoint%%&linguise_language=' . $_SERVER['HTTP_LINGUISE_ORIGINAL_LANGUAGE'], $endpoint);
    }, 10, 2);

    add_action('woocommerce_customer_reset_password', function () {
        $reset_pass_cookie = 'wp-resetpass-' . COOKIEHASH;
        setcookie($reset_pass_cookie, ' ', time() - YEAR_IN_SECONDS, '/', COOKIE_DOMAIN, is_ssl(), true);
    });
}

/**
 * Get WooCommerce language
 *
 * @return string|null
 */
function linguiseWooGetLanguage()
{
    $language = WPHelper::getLanguage();
    if (!$language) {
        // Check referer
        $language = WPHelper::getLanguageFromReferer();
    }
    return $language;
}

/**
 * Translate WooCommerce fragments
 *
 * @param array       $data       WooCommerce fragments
 * @param string|null $ajaxMethod WooCommerce method called from
 *
 * @return mixed
 */
function linguiseUpdateWooCommerceFragments($data, $ajaxMethod = null)
{
    if (empty($data)) {
        return $data;
    }

    $language = linguiseWooGetLanguage();
    if (!$language) {
        // Fails
        return $data;
    }

    $content = '<html><head></head><body>';
    if ($ajaxMethod === 'checkout') {
        $json = json_decode($data);
        if (!$json) {
            return $data;
        }
        $content = $json->messages;
    } elseif (is_array($data)) {
        foreach ($data as $class => $fragment) {
            $allowed = FragmentHandler::isKeyAllowed($class, $class);

            if (!is_null($allowed) && !$allowed) {
                continue;
            }

            $content .= '<divlinguise data-wp-linguise-class="' . $class . '">' . $fragment . '</divlinguise>';
        }
    } else {
        $content .= $data;
    }

    $content .= '</body></html>';

    if (!defined('LINGUISE_SCRIPT_TRANSLATION')) {
        define('LINGUISE_SCRIPT_TRANSLATION', 1);
    }

    include_once(LINGUISE_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

    linguiseInitializeConfiguration();

    $options = linguiseGetOptions();
    Configuration::getInstance()->set('token', $options['token']);

    $boundary = new Boundary();
    $request = Request::getInstance();

    $boundary->addPostFields('version', Processor::$version);
    $boundary->addPostFields('url', $request->getBaseUrl());
    $boundary->addPostFields('language', $language); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- View request, no action
    $boundary->addPostFields('requested_path', '/');
    $boundary->addPostFields('content', $content);
    $boundary->addPostFields('token', Configuration::getInstance()->get('token'));
    $boundary->addPostFields('ip', Helper::getIpAddress());
    $boundary->addPostFields('response_code', 200);
    $boundary->addPostFields('user_agent', !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');

    $ch = curl_init();

    list($translated_content, $response_code) = Translation::getInstance()->_translate($ch, $boundary);

    if (!$translated_content || $response_code !== 200) {
        // We failed to translate
        return $data;
    }

    curl_close($ch);

    $result = json_decode($translated_content);

    if ($ajaxMethod === 'checkout') {
        preg_match('/<body>(.*)<\/body>/s', $result->content, $matches);
        if (!$matches) {
            return $data;
        }
        $json->messages = $matches[1];
        return json_encode($json);
    } elseif (is_array($data)) {
        foreach ($data as $class => &$fragment) {
            preg_match('/<divlinguise data-wp-linguise-class="' . preg_quote($class) . '">(.*?)<\/divlinguise>/s', $result->content, $matches);
            if (!$matches) {
                // no match? continue to next fragment
                continue;
            }
            $fragment = $matches[1];
        }
    } else {
        preg_match('/<body>(.*)<\/body>/s', $result->content, $matches);
        if (!$matches) {
            return $data;
        }
        return $matches[1];
    }

    return $data;
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- View request, no action
$wcAjaxMethods = [
    'apply_coupon',
    'remove_coupon',
    'update_shipping_method',
    'get_cart_totals',
    'checkout'
];
foreach ($wcAjaxMethods as $ajaxMethod) {
    add_action('wc_ajax_' . $ajaxMethod, function () use ($ajaxMethod) {
        ob_start(function ($data) use ($ajaxMethod) {
            return linguiseUpdateWooCommerceFragments($data, $ajaxMethod);
        });
    });
}

// Translate non-wc-ajax fragments, a.k.a fragments that are fetched into AJAX requests
$defaultAjaxMethods = [
    'woodmart_ajax_add_to_cart',
    'woodmart_update_cart_item',
    'spree_ajax_add_to_cart',
    'uael_ajax_add_to_cart',
    'ga4_ajax_add_to_cart',
    'seocify_ajax_add_to_cart',
    'flatsome_ajax_add_to_cart',
    'bootscore_ajax_add_to_cart',
];
$ajaxMethods = apply_filters('linguise_woocommerce_ajax_methods', $defaultAjaxMethods);
// Remove duplicates
$ajaxMethods = array_unique($ajaxMethods);
foreach ($ajaxMethods as $ajaxMethod) {
    add_action('wp_ajax_' . $ajaxMethod, function () use ($ajaxMethod) {
        ob_start(function ($data) use ($ajaxMethod) {
            return linguiseUpdateWooCommerceFragments($data, $ajaxMethod);
        });
    }, 1000);
    add_action('wp_ajax_nopriv_' . $ajaxMethod, function () use ($ajaxMethod) {
        ob_start(function ($data) use ($ajaxMethod) {
            return linguiseUpdateWooCommerceFragments($data, $ajaxMethod);
        });
    }, 1000);
}

add_filter('woocommerce_update_order_review_fragments', 'linguiseUpdateWooCommerceFragments', 1000, 1);
add_filter('woocommerce_add_to_cart_fragments', 'linguiseUpdateWooCommerceFragments', 1000, 1);
add_filter('woocommerce_get_return_url', function ($url, $order) {
    $language = linguiseWooGetLanguage();
    if (!$language) {
        return $url;
    }
    $siteUrl = linguiseGetSite();
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- View request, no action, also $_GET['linguise_language'] is verified previously
    return preg_replace('/^' . preg_quote($siteUrl, '/') . '/', $siteUrl . '/' . $language, $url);
}, 10, 2);

add_filter('woocommerce_get_endpoint_url', function ($url, $endpoint, $value, $permalink) {
    $allowed_endpoints = ['order-pay'];

    $language = linguiseWooGetLanguage();
    if (!$language) {
        return $url;
    }

    if (!in_array($endpoint, $allowed_endpoints)) {
        return $url;
    }

    $siteUrl = linguiseGetSite();
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- View request, no action, also $_GET['linguise_language'] is verified previously
    return preg_replace('/^' . preg_quote($siteUrl, '/') . '/', $siteUrl . '/' . $language, $url);
}, 10, 4);

// Translate the WooCommerce order button value attributes
add_filter('woocommerce_order_button_html', function ($html) {
    if (!linguiseWooGetLanguage()) {
        return $html;
    }
    return str_replace('<button ', '<button data-linguise-translate-attributes="value data-value" ', $html);
});

/**
 * Reset wc fragment
 */
add_action('wp_loaded', function () {

    $script = 'try {
            jQuery(document).ready(function($) {

                if (typeof wc_cart_fragments_params === "undefined") {
                    return false;
                }

                if (typeof linguise_configs !== "undefined" && typeof linguise_configs.vars !== "undefined" && typeof linguise_configs.vars.configs !== "undefined" && typeof linguise_configs.vars.configs.current_language === "undefined") {
                    return;
                }

                // Fix the blinking cart icon when the network is slow
                var wc_fragments = sessionStorage.getItem( wc_cart_fragments_params.fragment_name );
                if(wc_fragments){
                    wc_fragments = JSON.parse( wc_fragments );
                    delete wc_fragments["a.cart-contents"];
                    sessionStorage.setItem( wc_cart_fragments_params.fragment_name, JSON.stringify(wc_fragments) );
                }

                // Get mini cart based on current language without timeout
                var $fragment_refresh = {
                    url: wc_cart_fragments_params.wc_ajax_url.toString().replace( "%%endpoint%%", "get_refreshed_fragments" ),
                    type: "GET",
                    data: {
                        time: new Date().getTime()
                    },
                    success: function( data ) {
                        if ( data && data.fragments ) {
            
                            $.each( data.fragments, function( key, value ) {
                                $( key ).replaceWith( value );
                            });

                            sessionStorage.setItem( wc_cart_fragments_params.fragment_name, JSON.stringify( data.fragments ) );
                        }
                    },
                    error: function() {
                        $( document.body ).trigger( "wc_fragments_ajax_error" );
                    }
                };
            
                $.ajax( $fragment_refresh );
            });
        } catch (e) {
            console.warn(e);
        }';

    wp_register_script('linguise_woocommerce_cart_fragments', '', array('jquery'), '', true);
    wp_enqueue_script('linguise_woocommerce_cart_fragments');
    wp_add_inline_script('linguise_woocommerce_cart_fragments', $script);
});

/**
 * Check if klarna checkout was activate
 *
 * @return boolean
 */
function linguise_woo_is_klarna_active()
{
    if (!is_plugin_active('klarna-checkout-for-woocommerce/klarna-checkout-for-woocommerce.php')) {
        return false;
    }

    return true;
}

/**
 * Map linguise language with Klarna Checkout lang.
 * https://docs.klarna.com/klarna-checkout/additional-resources/available-languages/
 *
 * @param string $linguiseLang String language code
 *
 * @return string
 */
function linguise_woo_get_klarna_language($linguiseLang)
{
    // linguise X Klarna lang
    $mapLanguages = [
        'da' => 'da', // Danish
        'nl' => 'nl', // Dutch
        'en' => 'en', // English
        'fi' => 'fi', // Finish
        'fr' => 'fr', // French
        'de' => 'de', // German
        'it' => 'it', // Italian
        'no' => 'nb', // Norwegian (Bokmål)
        'pl' => 'pl', // Polish
        'pt' => 'pt', // Portuguese
        'es' => 'es', // Spanish
        'sv' => 'sv', // Swedish
    ];

    // fallback, use klarna language
    if (!array_key_exists($linguiseLang, $mapLanguages)) {
        return false;
    }

    return $mapLanguages[$linguiseLang];
}

add_filter('kco_locale', function ($locale) {
    if (!linguise_woo_is_klarna_active()) {
        return $locale;
    }
    
    $language = WPHelper::getLanguage();
    
    // fallback, use Klarna language
    if (!$language) {
        return $locale;
    }

    $klarnaLanguage = linguise_woo_get_klarna_language($language);

    if (!$klarnaLanguage) {
        return $locale;
    }

    return $klarnaLanguage;
}, 1);

add_filter('kco_additional_checkboxes', function ($additional_checkboxes) {
    if (!linguise_woo_is_klarna_active()) {
        return $additional_checkboxes;
    }

    foreach ($additional_checkboxes as $index => $checkbox) {
        if ($checkbox['id'] === 'terms_and_conditions') {
            $content = '<html><head></head><body>';
            $content .= $additional_checkboxes[$index]['text'];
            $content .= '</body></html>';

            $language = WPHelper::getLanguage();

            if ($language === null) {
                return $additional_checkboxes;
            }
            
            $klarnaLanguage = linguise_woo_get_klarna_language($language);
            // language is not supported by klarna, do not tranlate,let it as is
            if (!$klarnaLanguage) {
                return $additional_checkboxes;
            }

            include_once(LINGUISE_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

            linguiseInitializeConfiguration();

            $options = linguiseGetOptions();
            Configuration::getInstance()->set('token', $options['token']);

            $boundary = new Boundary();
            $request = Request::getInstance();

            $boundary->addPostFields('version', Processor::$version);
            $boundary->addPostFields('url', $request->getBaseUrl());
            $boundary->addPostFields('language', $language); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- View request, no action
            $boundary->addPostFields('requested_path', '/');
            $boundary->addPostFields('content', $content);
            $boundary->addPostFields('token', Configuration::getInstance()->get('token'));
            $boundary->addPostFields('ip', Helper::getIpAddress());
            $boundary->addPostFields('response_code', 200);
            $boundary->addPostFields('user_agent', !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');

            $ch = curl_init();

            list($translated_content, $response_code) = Translation::getInstance()->_translate($ch, $boundary);

            if (!$translated_content || $response_code !== 200) {
                // We failed to translate
                return $additional_checkboxes;
            }

            curl_close($ch);

            $result = json_decode($translated_content);
            
            preg_match('/<body>(.*)<\/body>/s', $result->content, $matches);
            
            if (!$matches) {
                return $additional_checkboxes;
            }

            $additional_checkboxes[$index]['text'] = $matches[1];

            break;
        }
    }

    return $additional_checkboxes;
}, 1);

add_filter('linguise_ajax_intercept_prefixes', function ($prefixes) {
    $prefixes[] = 'wc/store/v1/cart';
    $prefixes[] = 'wc/store/v1/batch';
    $prefixes[] = 'wc/store/v1/checkout';

    return $prefixes;
});

/**
 * Fix: When removing an item on Classic Cart (Non block Cart) it gets 404 page.
 * There's some weird redirect occurs when removing items and add double language code.
 *
 * Do not translate the redirect URL if the conditions below are met  :
 * Request is for removing cart item or undoing removed item from cart
 * Request is from translated page
 */
add_action('wp_redirect', function ($location) {
    // Get Linguise language from HTTP request referer
    $language = WPHelper::getLanguageFromReferer();

    if ($language === null) {
        // original page, skip
        return $location;
    }

    // Check: is request for remove cart item or undo removed item from cart
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action
    if (!isset($_REQUEST['remove_item']) && !isset($_REQUEST['undo_item'])) {
        /**
         * Request is not remove cart item or is not undo removing cart item
         */
        return $location;
    }

    /**
     * Set header to prevent Script-PHP translate the translated url.
     * If the header is not exist, Script-PHP would re-translated the URL that has been translated before.
     * So we need to add this header to make sure the below code is not re-translate the translated URL.
     *
     * @see Linguise\Vendor\Linguise\Script\Core\CurlRequest
     * the above class has code to check if the header has Linguise-Translated-Redirect or not
     */

    header('Linguise-Translated-Redirect: true');

    return $location;
});
