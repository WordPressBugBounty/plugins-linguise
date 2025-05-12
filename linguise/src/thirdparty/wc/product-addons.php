<?php

namespace Linguise\WordPress\Integrations;

use Linguise\WordPress\Helper as WPHelper;

defined('ABSPATH') || die('');

/**
 * Integration with WC Product Addons by Acowebs
 *
 * Helps translate content for WooCommerce Product Addons
 */
class WCProductAddonsIntegration extends LinguiseBaseIntegrations
{
    /**
     * Plugin name
     *
     * @var string
     */
    public static $name = 'Product Add-Ons for WooCommerce';

    /**
     * A collection of fragment overrides that we want to translate
     *
     * @var array
     */
    protected static $fragment_overrides = [
        'name' => 'wcpa-context',
        'match' => 'var wcpa_front = (.*?);',
        'replacement' => 'var wcpa_front = $$JSON_DATA$$;',
    ];

    /**
     * Initialize the integration.
     * Sets up fragment keys to be translated for Payment Plugin PayPal
     */
    public function __construct()
    {
        // Override so we can add custom keys loop
        $fragment_filters = [];
        $key_exact_ignore = [
            'assets_url',
            'api_nonce',
            'root',
            'date_format',
            'ajax_add_to_cart',
            'time_format',
            'className',
            'tempId',
            'elementId',
        ];
        foreach ($key_exact_ignore as $key) {
            $fragment_filters[] = [
                'key' => $key,
                'mode' => 'exact',
                'kind' => 'deny',
            ];
        }
    
        $regex_full = [
            '^init_triggers\.*',
            '^design\.*',
            'form_rules\.*',
        ];
        foreach ($regex_full as $key) {
            $fragment_filters[] = [
                'key' => $key,
                'mode' => 'regex_full',
                'kind' => 'deny',
            ];
        }
        $regex_fields = [
            'name',
            'type',
            'elementId',
            'cl_rule',
            'values\.\d+\.(?:value|tempId)$',
        ];
        foreach ($regex_fields as $key) {
            $fragment_filters[] = [
                'key' => '^fields\.(?:[\w\d_-]+)\.fields\.\d+\.\d+\.' . $key,
                'mode' => 'regex_full',
                'kind' => 'deny',
            ];
        }

        self::$fragment_keys = $fragment_filters;

        add_filter('linguise_fragment_attributes', function ($fragments, $html_data) {
            // Check if there is data-wcpa attribute in the HTML data
            if (preg_match('/data-wcpa=(["\'])([^"]+)\1/', $html_data, $matches)) {
                // Get the value of the data-wcpa attribute
                $wcpa = $matches[1];

                // Check if the value is not empty
                if (!empty($wcpa)) {
                    // Add the data-wcpa attribute to the fragments array
                    $fragments[] = [
                        'name' => 'wcpa-data-attrs',
                        'key' => 'data-wcpa',
                    ];
                }
            }

            return $fragments;
        }, 100, 2);
    }

    /**
     * Decides if the integration should be loaded.
     *
     * @return boolean
     */
    public function shouldLoad()
    {
        return is_plugin_active('woo-custom-product-addons/start.php');
    }

    /**
     * Initializes the integration.
     *
     * @return void
     */
    public function init()
    {
        // Stub
    }

    /**
     * Unload the integration
     *
     * @return void
     */
    public function destroy()
    {
        // Stub
    }
}
