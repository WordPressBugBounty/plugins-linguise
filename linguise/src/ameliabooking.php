<?php
use Linguise\WordPress\Helper;
use Linguise\Vendor\Linguise\Script\Core\Request;
use Linguise\Vendor\Linguise\Script\Core\Url;

include_once ABSPATH . 'wp-admin/includes/plugin.php';
if (!is_plugin_active('ameliabooking/ameliabooking.php')) {
    return;
}

/**
 * Let Linguise\Vendor\Linguise\Script\Core\Url to translate the redirect URL
 * We only set the protected property, if we not set the property it would be null
 * Because this method executed via wp-admin/admin-ajax.php
 *
 * @param string $linguise_language Linguise language
 * @param string $url               The string URL
 *
 * @return string
 */
function linguise_ameliabooking_translate_url($linguise_language, $url)
{
    $hostname = $_SERVER['HTTP_HOST'];
    $protocol = (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' || !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] === 443) ? 'https' : 'http';

    $request = Request::getInstance();
    $language_reflection = new \ReflectionProperty(get_class($request), 'language');
    $language_reflection->setAccessible(true);
    $language_reflection->setValue($request, $linguise_language);

    $hostname_reflection = new \ReflectionProperty(get_class($request), 'hostname');
    $hostname_reflection->setAccessible(true);
    $hostname_reflection->setValue($request, $hostname);

    $protocol_reflection = new \ReflectionProperty(get_class($request), 'protocol');
    $protocol_reflection->setAccessible(true);
    $protocol_reflection->setValue($request, $protocol);

    // Let Linguise\Vendor\Linguise\Script\Core\Url translate the url
    return Url::translateUrl($url);
}

/**
 * Translates the given URL based on the language derived from the referer.
 *
 * @param string $url The original URL to be translated.
 *
 * @return string The translated URL or the original URL if no language is found.
 */
function linguise_amelia_get_translated_url($url)
{
    linguiseInitializeConfiguration();

  // Intercept and modify the cart URL as needed
    $language = Helper::getLanguageFromReferer();

    if (!$language) {
        return $url;
    }

    return linguise_ameliabooking_translate_url($language, $url);
}

if (wp_doing_ajax()) {
  /**
   * Executed right after fill booking form
   */
  // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action
    if (!empty($_REQUEST['call']) && $_REQUEST['call'] === '/payment/wc') {
         /**
       * Executed when Ameliabooking setting :
       * - WooCommerce Service: enabled
       * - Default Page : Checkout
       */
        add_filter('woocommerce_get_checkout_url', function ($checkout_url) {
            return linguise_amelia_get_translated_url($checkout_url);
        }, 1);

      /**
       * Executed when Ameliabooking setting :
       * - WooCommerce Service: enabled
       * - Default Page : Cart
       */
        add_filter('woocommerce_get_cart_url', function ($cart_url) {
            return linguise_amelia_get_translated_url($cart_url);
        }, 1);
    }
}
