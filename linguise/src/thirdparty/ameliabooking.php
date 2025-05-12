<?php

namespace Linguise\WordPress\Integrations;

defined('ABSPATH') || die('');

use Linguise\WordPress\Helper;

/**
 * Integration with Amelia booking
 *
 * Ensure the URL is redirected to the correct one
 */
class AmeliaBookingIntegration extends LinguiseBaseIntegrations
{
    /**
     * Plugin name
     *
     * @var string
     */
    public static $name = 'Amelia Booking';

    /**
     * A collection of fragment overrides that we want to translate
     *
     * @var array
     */
    protected static $fragment_overrides = [
        [
            'name' => 'amelia-labels',
            'match' => 'var wpAmeliaLabels = (.*?);',
            'replacement' => 'var wpAmeliaLabels = $$JSON_DATA$$;',
        ],
    ];

    /**
     * Determines if the integration should be loaded.
     *
     * @return boolean
     */
    public function shouldLoad()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action
        $is_ajax_active = wp_doing_ajax() && !empty($_REQUEST['action']) && $_REQUEST['action'] === 'wpamelia_api';
        return is_plugin_active('ameliabooking/ameliabooking.php') && $is_ajax_active;
    }

    /**
     * Load the integration
     *
     * @return void
     */
    public function init()
    {
        if (wp_doing_ajax()) {
            // Executed right after fill booking form
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action
            if (!empty($_REQUEST['call']) && $_REQUEST['call'] === '/payment/wc') {
                /**
                 * Executed when Ameliabooking setting:
                 * - WooCommerce Service: enabled
                 * - Default Page: Checkout
                 */
                add_filter('woocommerce_get_checkout_url', [$this, 'translateAmeliaUrls'], 1);

                /**
                 * Executed when Ameliabooking setting:
                 * - WooCommerce Service: enabled
                 * - Default Page: Cart
                 */
                add_filter('woocommerce_get_cart_url', [$this, 'translateAmeliaUrls'], 1);
            }
        }
    }

    /**
     * Unload the integration
     *
     * @return void
     */
    public function destroy()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action
        if (wp_doing_ajax() && !empty($_REQUEST['call']) && $_REQUEST['call'] === '/payment/wc') {
            remove_filter('woocommerce_get_checkout_url', [$this, 'translateAmeliaUrls'], 1);
            remove_filter('woocommerce_get_cart_url', [$this, 'translateAmeliaUrls'], 1);
        }
    }

    /**
     * Translates the given URL based on the language derived from the referer.
     *
     * @param string $url The original URL to be translated.
     *
     * @return string The translated URL or the original URL if no language is found.
     */
    public function translateAmeliaUrls($url)
    {
        // Intercept and modify the cart URL as needed
        $language = Helper::getLanguageFromReferer();

        if (!$language) {
            return $url;
        }

        return $this->translateUrl($language, $url);
    }
}
