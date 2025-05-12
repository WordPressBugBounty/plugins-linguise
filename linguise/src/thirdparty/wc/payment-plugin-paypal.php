<?php

namespace Linguise\WordPress\Integrations;

defined('ABSPATH') || die('');

/**
 * Integration for WooCommerce Payment Plugin for PayPal
 *
 * This add a list of fragment keys to be translated.
 */
class WCPaymentPluginPaypalIntegration extends LinguiseBaseIntegrations
{
    /**
     * Plugin name
     *
     * @var string
     */
    public static $name = 'WooCommerce Payment Plugin PayPal';

    /**
     * Initialize the integration.
     * Sets up fragment keys to be translated for Payment Plugin PayPal
     */
    public function __construct()
    {
        // Override so we can add custom keys loop
        $payment_keys_full = [
            [
                'key' => '^paypalQueryParams\..*',
                'mode' => 'regex_full',
                'kind' => 'deny',
            ],
        ];

        $path_key_disallowed = [
            'ppcpGeneralData.ajaxRestPath',
            'ppcpGeneralData.blocksVersion',
            'ppcpGeneralData.clientId',
            'ppcpGeneralData.context',
            'ppcpGeneralData.environment',
        ];

        foreach ($path_key_disallowed as $path_key) {
            $payment_keys_full[] = [
                'key' => $path_key,
                'mode' => 'path',
                'kind' => 'deny',
            ];
        }

        $payment_keys_full[] = [
            'key' => '^ppcpGeneralData\.restRoutes\..*',
            'mode' => 'regex_full',
            'kind' => 'deny',
        ];

        self::$fragment_keys = $payment_keys_full;
    }

    /**
     * Determines if the integration should be loaded.
     *
     * @return boolean
     */
    public function shouldLoad()
    {
        return is_plugin_active('woocommerce/woocommerce.php') && is_plugin_active('pymntpl-paypal-woocommerce/pymntpl-paypal-woocommerce.php');
    }

    /**
     * Load the integration, this will not do anything
     *
     * @return void
     */
    public function init()
    {
        // We don't do anything here
    }

    /**
     * Unload the integration, this will not do anything
     *
     * @return void
     */
    public function destroy()
    {
        // We don't do anything here
    }
}
