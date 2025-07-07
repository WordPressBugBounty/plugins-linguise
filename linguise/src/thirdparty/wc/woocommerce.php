<?php

namespace Linguise\WordPress\Integrations;

use Linguise\Vendor\Linguise\Script\Core\Helper;
use Linguise\WordPress\FragmentHandler;
use Linguise\WordPress\Helper as WPHelper;

defined('ABSPATH') || die('');

/**
 * Integration for WooCommerce
 *
 * A ton of code handling for WooCommerce related stuff.
 */
class WooCommerceIntegration extends LinguiseBaseIntegrations
{
    /**
     * Plugin name
     *
     * @var string
     */
    public static $name = 'WooCommerce';

    /**
     * A list of WP-REST ajax methods that we want to be intercepted.
     *
     * @var string[]
     */
    protected static $wp_rest_ajax_intercept = [
        'wc/store/v1/cart',
        'wc/store/v1/batch',
        'wc/store/v1/checkout',
    ];

    /**
     * Is email translation active?
     *
     * @var boolean
     */
    protected $email_active;

    /**
     * A collection of WC-AJAX that we want to hook and translate
     *
     * @var string[]
     */
    private static $wc_ajax_methods = [
        'apply_coupon',
        'remove_coupon',
        'update_shipping_method',
        'get_cart_totals',
        'checkout',
    ];

    /**
     * A collection of WP-AJAX that we want to hook and translate
     *
     * @var string[]
     */
    private static $default_ajax_methods = [
        'woodmart_ajax_add_to_cart',
        'woodmart_update_cart_item',
        'spree_ajax_add_to_cart',
        'uael_ajax_add_to_cart',
        'ga4_ajax_add_to_cart',
        'seocify_ajax_add_to_cart',
        'flatsome_ajax_add_to_cart',
        'bootscore_ajax_add_to_cart',
    ];

    /**
     * Initialize the integration.
     * Sets up fragment keys to be translated for WooCommerce Admin
     */
    public function __construct()
    {
        parent::__construct();

        $fragment_keys = [];
        // Payment methods data
        $payment_keys_regex_full = [
            '^(paymentMethods|paymentMethodData)\..*\.style\..*',
            '^(paymentMethods|paymentMethodData)\..*\.cardOptions.*',
            '^(paymentMethods|paymentMethodData)\..*\.cards\..*',
            '^(paymentMethods|paymentMethodData)\..*\.icons\..*',
            '^(paymentMethods|paymentMethodData)\..*\.customFieldOptions.*',
            '^(paymentMethods|paymentMethodData)\..*\.elementOptions.*',
            '^(paymentMethods|paymentMethodData)\..*\.features.*',
            '^(paymentMethods|paymentMethodData)\..*\.icons.*',
            '^(paymentMethods|paymentMethodData)\..*\.(name|paymentType|countryCode)',
            '^(paymentMethods|paymentMethodData)\..*\.(countries|currencies)\..*',
            '^(paymentMethods|paymentMethodData)\..*\.(requiredParams|paymentSections|specificCountries).*',
            '^paymentMethodSortOrder\..*',
            '^collectableMethodIds\..*',
            '^(paymentMethods|paymentMethodData)\.[\w\d\.\-\_]+\.currency',
        ];
        foreach ($payment_keys_regex_full as $payment_key) {
            $fragment_keys[] = [
                'key' => $payment_key,
                'mode' => 'regex_full',
                'kind' => 'deny',
            ];
        }

        /* For AJAX requests */
        $fragment_keys[] = [
            'key' => '^items\.\d+\.(name|(short_)?description|permalink)', // -> cart request
            'mode' => 'regex_full',
            'kind' => 'allow',
        ];
        $fragment_keys[] = [
            'key' => '^responses\.\d+\.body\.items\.\d+\.(name|(short_)?description|permalink)', // -> batch request
            'mode' => 'regex_full',
            'kind' => 'allow',
        ];
        $fragment_keys[] = [
            'key' => '^payment_result\.(redirect_url|message)', // -> checkout request
            'mode' => 'regex_full',
            'kind' => 'allow',
        ];

        // Used in payment method
        $merged_defaults[] = [
            'key' => 'currency',
            'mode' => 'exact',
            'kind' => 'deny',
        ];

        self::$fragment_keys = $fragment_keys;
    }

    /**
     * Determines if the integration should be loaded.
     *
     * @return boolean
     */
    public function shouldLoad()
    {
        return is_plugin_active('woocommerce/woocommerce.php');
    }

    /**
     * Load the integration
     *
     * @return void
     */
    public function init()
    {
        $this->initCommon();

        $wc_ajax_methods = apply_filters('linguise_woocommerce_fragments_wc_ajax_methods', self::$wc_ajax_methods);
        $wc_ajax_methods = array_unique($wc_ajax_methods);
        $ajax_methods = apply_filters('linguise_woocommerce_fragments_ajax_methods', self::$default_ajax_methods);
        $ajax_methods = array_unique($ajax_methods);

        // Translate wc-ajax methods
        foreach ($wc_ajax_methods as $ajax_method) {
            add_action('wc_ajax_' . $ajax_method, function () use ($ajax_method) {
                ob_start(function ($data) use ($ajax_method) {
                    return $this->hookWCFragments($data, $ajax_method);
                });
            });
        }

        // Translate non-wc-ajax fragments, a.k.a fragments that are fetched into AJAX requests
        foreach ($ajax_methods as $ajax_method) {
            add_action('wp_ajax_' . $ajax_method, function () use ($ajax_method) {
                ob_start(function ($data) use ($ajax_method) {
                    return $this->hookWCFragments($data, $ajax_method);
                });
            }, 1000);

            add_action('wp_ajax_nopriv_' . $ajax_method, function () use ($ajax_method) {
                ob_start(function ($data) use ($ajax_method) {
                    return $this->hookWCFragments($data, $ajax_method);
                });
            }, 1000);
        }
    }

    /**
     * Unload the integration
     *
     * @return void
     */
    public function destroy()
    {
        remove_filter('woocommerce_ajax_get_endpoint', [$this, 'hookWCAjaxEndpoint'], 10, 2);
        remove_action('woocommerce_customer_reset_password', [$this, 'hookWCCustomerResetPassword'], 10);

        // We skip removing the ajax methods overrides
        remove_action('woocommerce_new_order', [$this, 'hookWCNewOrder'], 100, 2);
        remove_filter('woocommerce_after_order_object_save', [$this, 'hookWCNewOrderSave'], 100, 1);
        remove_filter('woocommerce_update_order_review_fragments', [$this, 'hookWCFragments'], 1000, 1);
        remove_filter('woocommerce_add_to_cart_fragments', [$this, 'hookWCFragments'], 1000, 1);

        remove_filter('woocommerce_get_return_url', [$this, 'hookWCReturnUrl'], 10, 2);
        remove_filter('woocommerce_get_endpoint_url', [$this, 'hookWCEndpoint'], 10, 2);
        // remove_filter('woocommerce_get_checkout_order_received_url', [$this, 'hookWCOrderUrl'], 10, 2);
        remove_filter('woocommerce_order_button_html', [$this, 'hookOrderButtonHTML'], 1000, 1);

        remove_action('wp_loaded', [$this, 'hookAddCartFragments']);
        remove_action('wp_redirect', [$this, 'hookWPRedirect']);
    }

    /**
     * Reload the integration
     *
     * @return void
     */
    public function reload()
    {
        $this->destroy();
        // We use initCommon to reload only filter that we remove
        // Since we don't think there is an easy way to destroy the wp_ajax/wc_ajax related hooks
        $this->initCommon();
    }

    /**
     * Initializes the common hooks required for this integration to work
     *
     * @return void
     */
    private function initCommon()
    {
        add_filter('woocommerce_ajax_get_endpoint', [$this, 'hookWCAjaxEndpoint'], 10, 2);
        add_action('woocommerce_customer_reset_password', [$this, 'hookWCCustomerResetPassword'], 10);

        add_action('woocommerce_new_order', [$this, 'hookWCNewOrder'], 100, 2);
        add_action('woocommerce_after_order_object_save', [$this, 'hookWCNewOrderSave'], 100, 1);
        add_filter('woocommerce_update_order_review_fragments', [$this, 'hookWCFragments'], 1000, 1);
        add_filter('woocommerce_add_to_cart_fragments', [$this, 'hookWCFragments'], 1000, 1);

        add_filter('woocommerce_get_return_url', [$this, 'hookWCReturnUrl'], 10, 2);
        add_filter('woocommerce_get_endpoint_url', [$this, 'hookWCEndpoint'], 10, 2);
        // add_filter('woocommerce_get_checkout_order_received_url', [$this, 'hookWCOrderUrl'], 10, 2);
        add_filter('woocommerce_order_button_html', [$this, 'hookOrderButtonHTML'], 1000, 1);

        add_action('wp_loaded', [$this, 'hookAddCartFragments']);
        add_action('wp_redirect', [$this, 'hookWPRedirect']);
    }

    /**
     * Get the language for WooCommerce context.
     *
     * First it checks current language, if not set, then it checks referer.
     *
     * @return string|null
     */
    protected function getWooLanguage()
    {
        $language_meta = WPHelper::getLanguage();
        $invalid_methods = array('GET', 'HEAD', 'OPTIONS');
        if (!$language_meta && !in_array($_SERVER['REQUEST_METHOD'], $invalid_methods)) {
            // Check referer
            $language_meta = WPHelper::getLanguageFromReferer();
        }
        return $language_meta;
    }

    /**
     * Rewrite the WooCommerce URL to include the language code.
     *
     * @param string $url      The URL to rewrite
     * @param string $language The language code to prepend
     *
     * @return string The rewritten URL
     */
    protected function rewriteWooUrl($url, $language)
    {
        $site_url = parse_url(linguiseGetSite());
        $url = parse_url($url);
        $site_path = rtrim(isset($site_url['path']) ? $site_url['path'] : '', '/');

        $url_path = isset($url['path']) ? $url['path'] : '';
        if (!empty($site_path) && $site_path !== '/') {
            // Remove the site path from the URL path
            $url_path = str_replace($site_path, '', $url_path);
        }

        // Check if language already exists in the URL
        $url_path = ltrim($url_path, '/');
        if (strpos($url_path, $language . '/') !== 0) {
            // If not, prepend the language code
            $url_path = $language . '/' . $url_path;
        }

        $url['path'] = '/' . $url_path;
        $result = Helper::buildUrl($url, $site_url, $language);
        return $result;
    }

    /**
     * Save the current language at order creation
     *
     * @param integer   $order_id The order ID
     * @param \WC_Order $order    The actual order
     *
     * @return void
     */
    public function hookWCNewOrder($order_id, $order)
    {
        if (WPHelper::isAdminRequest()) {
            return;
        }

        $language_meta = $this->getWooLanguage();

        if (empty($language_meta)) {
            return;
        }

        // We add to both post meta and order meta
        add_post_meta($order_id, 'linguise_language', $language_meta);

        $order->update_meta_data('linguise_language', $language_meta);
        $order->save_meta_data();
        $order->apply_changes();
    }

    /**
     * Hook WC new order save, this function will save the current language in the order meta
     *
     * Used in some cases where some plugin did not trigger the `woocommerce_new_order` action
     *
     * @param \WC_Order $order The order object
     *
     * @return void
     */
    public function hookWCNewOrderSave($order)
    {
        $woo_language = $this->getWooLanguage();
        if (empty($woo_language)) {
            return;
        }

        $ling_meta = $order->get_meta('linguise_language', true);
        if (!empty($ling_meta)) {
            // If the order already has a language set, we don't need to do anything
            return;
        }
        add_post_meta($order->get_id(), 'linguise_language', $woo_language);
        $order->update_meta_data('linguise_language', $woo_language);

        // Save the order meta data
        $order->save_meta_data();
        $order->apply_changes();
    }

    /**
     * Get the order language from the order meta.
     * If the order meta is not set, it will return the current WooCommerce language.
     *
     * @param \WC_Order|null $order The order object
     *
     * @return string|null The language code or null if not set
     */
    protected function getOrderLanguage($order)
    {
        if (empty($order)) {
            return $this->getWooLanguage();
        }

        $language_meta = null;
        if (method_exists($order, 'get_meta')) {
            $language_meta = $order->get_meta('linguise_language', true);
        }

        if (empty($language_meta) && isset($order->id)) {
            $language_meta = get_post_meta($order->id, 'linguise_language', true);
        }

        if (empty($language_meta)) {
            $language_meta = $this->getWooLanguage();
        }

        return $language_meta;
    }

    /**
     * Hook WC-AJAX endpoint and add linguise_language query arg
     *
     * @param string $endpoint The endpoint URL
     * @param string $request  The request type
     *
     * @return string
     */
    public function hookWCAjaxEndpoint($endpoint, $request)
    {
        $language_meta = $this->getWooLanguage();
        if (empty($language_meta)) {
            return $endpoint;
        }

        if ($request === 'checkout') {
            /**
             * Compability for FunnelKit checkout
             *
             * @see https://wordpress.org/plugins/funnel-builder/
             */
            if (is_plugin_active('funnel-builder/funnel-builder.php')) {
                return add_query_arg('linguise_language', $language_meta, $endpoint);
            }

            return str_replace('checkout', 'checkout&linguise_language=' . $language_meta, $endpoint);
        }

        return str_replace('%%endpoint%%', '%%endpoint%%&linguise_language=' . $language_meta, $endpoint);
    }

    /**
     * Hook WC return url and add current language to the url
     *
     * @param string         $url   The URL itself
     * @param \WC_Order|null $order The order object
     *
     * @return string
     */
    public function hookWCReturnUrl($url, $order)
    {
        $language_meta = $this->getOrderLanguage($order);
        if (empty($language_meta)) {
            return $url;
        }

        return $this->rewriteWooUrl($url, $language_meta);
    }

    /**
     * Hook WC endpoint url and add linguise_language query arg
     *
     * @param string $url      The URL itself
     * @param string $endpoint The endpoint requested
     *
     * @return string
     */
    public function hookWCEndpoint($url, $endpoint)
    {
        $language_meta = $this->getWooLanguage();
        if (empty($language_meta)) {
            return $url;
        }

        $allowed_endpoints = ['order-pay'];

        if (!in_array($endpoint, $allowed_endpoints)) {
            return $url;
        }

        return $this->rewriteWooUrl($url, $language_meta);
    }

    /**
     * Hook WC order URL, this function will override the order URL and add the language metadata in the order meta
     *
     * @param string    $order_url The order URL
     * @param \WC_Order $order     The order object
     *
     * @return string The rewritten order URL with the language code
     */
    public function hookWCOrderUrl($order_url, $order)
    {
        $language_meta = $this->getOrderLanguage($order);

        if (empty($language_meta)) {
            // No language set, return the original URL
            return $order_url;
        }

        // Add the language path to the order URL
        return $this->rewriteWooUrl($order_url, $language_meta);
    }

    /**
     * Hook WC reset password, this function will flush the reset password cookie from user browser
     * when reset password is successful and it's in translated pages
     *
     * @return void
     */
    public function hookWCCustomerResetPassword()
    {
        if (defined('COOKIEHASH') && !empty($this->getWooLanguage())) {
            $reset_pass_cookie = 'wp-resetpass-' . COOKIEHASH;
            setcookie($reset_pass_cookie, ' ', time() - YEAR_IN_SECONDS, '/', COOKIE_DOMAIN, is_ssl(), true);
        }
    }

    /**
     * Translate WooCommerce fragments
     *
     * @param mixed       $data       The data returned from the fragment override
     * @param string|null $ajaxMethod The AJAX method used to fetch the fragments
     *
     * @return mixed
     */
    public function hookWCFragments($data, $ajaxMethod = \null)
    {
        if (empty($data)) {
            return $data;
        }

        $language_meta = $this->getWooLanguage();
        if (!$language_meta) {
            // Not translatable
            return $data;
        }

        $html_content = '<html><head></head><body>';
        if ($ajaxMethod === 'checkout') {
            $json = json_decode($data);
            if (!$json) {
                return $data;
            }
            $html_content = $json->messages;
        } elseif (is_array($data)) {
            foreach ($data as $class => $fragment) {
                $html_content .= '<divlinguise data-wp-linguise-class="' . $class . '">' . $fragment . '</divlinguise>';
            }
        } else {
            $html_content .= $data;
        }

        $html_content .= '</body></html>';

        $translated_content = $this->translateFragments($html_content, $language_meta, '/');
        if (empty($translated_content)) {
            // Failed to translate
            return $data;
        }

        if (isset($translated_content->redirect)) {
            // Somehow we got this...?
            return $data;
        }

        if ($ajaxMethod === 'checkout') {
            preg_match('/<body>(.*)<\/body>/s', $translated_content->content, $matches);
            if (!$matches) {
                return $data;
            }
            $json->messages = $matches[1];
            return json_encode($json);
        } elseif (is_array($data)) {
            foreach ($data as $class => &$fragment) {
                preg_match('/<divlinguise data-wp-linguise-class="' . preg_quote($class) . '">(.*?)<\/divlinguise>/s', $translated_content->content, $matches);
                if (!$matches) {
                    // no match? continue to next fragment
                    continue;
                }
                $fragment = $matches[1];
            }
        } else {
            preg_match('/<body>(.*)<\/body>/s', $translated_content->content, $matches);
            if (!$matches) {
                return $data;
            }
            return $matches[1];
        }

        return $data;
    }

    /**
     * Translates the text on the checkout button
     *
     * @param string $html The HTML of the checkout button
     *
     * @return string
     */
    public function hookOrderButtonHTML($html)
    {
        if (empty($this->getWooLanguage())) {
            return $html;
        }
        return str_replace('<button ', '<button data-linguise-translate-attributes="value data-value" ', $html);
    }

    /**
     * Reset/refresh WC fragments JS scripts
     *
     * @return void
     */
    public function hookAddCartFragments()
    {
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
                if (wc_fragments) {
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
    }

    /**
     * Fix: When removing an item on Classic Cart (Non block Cart) it gets 404 page.
     * There's some weird redirect occurs when removing items and add double language code.
     *
     * Do not translate the redirect URL if the conditions below are met  :
     * Request is for removing cart item or undoing removed item from cart
     * Request is from translated page
     *
     * @param string $location The redirect URL
     *
     * @return string
     */
    public function hookWPRedirect($location)
    {
        // Get Linguise language from HTTP request referer
        $language_meta = WPHelper::getLanguageFromReferer();

        if ($language_meta === null) {
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
    }
}
