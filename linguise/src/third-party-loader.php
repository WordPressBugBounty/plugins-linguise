<?php

namespace Linguise\WordPress;

defined('ABSPATH') || die('');

/**
 * A simple third-party integrations loader
 */
class ThirdPartyLoader
{
    /**
     * Singleton instance
     *
     * @var ThirdPartyLoader|null
     */
    private static $_instance = null; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

    /**
     * Currently loaded 3rd party integrations
     *
     * @var array<string, \Linguise\WordPress\Integrations\LinguiseBaseIntegrations>
     */
    private $loaded_integrations = [];

    /**
     * Currently active 3rd party integrations
     *
     * @var string[]
    */
    private $active_integrations = [];

    /**
     * Current directory of this file
     *
     * @var string
     */
    private $current_dir;

    /**
     * Whether the loader has been initialized
     *
     * @var boolean
     */
    private $initialized;

    /**
     * Native integration classes to be loaded
     *
     * @var array<string, string|array<string>>
     */
    private $native_integrations = [
        'ameliabooking' => 'AmeliaBookingIntegration',
        'bookingpress' => 'BookingPressIntegration',
        'elementor' => 'ElementorIntegration',
        'elementor-pro' => 'ElementorProIntegration',
        'facetwp' => 'FacetWPIntegration',
        'jet-engine' => 'JetEngineIntegration',
        'wc/woocommerce' => 'WooCommerceIntegration',
        'wc/emails' => 'WooCommerceEmailsIntegration',
        'wc/admin-emails' => 'WooCommerceAdminEmailsIntegration',
        'wc/fibosearch' => 'WCFiboSearchIntegration',
        'wc/gateway-stripe' => 'WCGatewayStripeIntegration',
        'wc/payment-plugin-stripe' => 'WCPaymentPluginStripeIntegration',
        'wc/payment-plugin-paypal' => 'WCPaymentPluginPaypalIntegration',
        'wc/klarna-checkout' => 'WCKlarnaCheckoutIntergration',
        'wc/product-addons' => 'WCProductAddonsIntegration',
        'surecart' => 'SurecartIntegration',
        'fluentcrm' => 'FluentCRMIntegration',
        'wp-rocket' => 'WPRocketIntegration',
        'page-scroll-to-id' => 'PageScrollToIdIntegration'
    ];

    /**
     * Initialize loader
     */
    public function __construct()
    {
        $this->current_dir = rtrim(realpath(dirname(__FILE__)), '/');
        $this->initialized = false;

        // Load integrations
        $this->loadInternal();
        // On shutdown -> unload integrations
        add_action('shutdown', [$this, 'unloadInternal']);
    }

    /**
     * Retrieve singleton instance
     *
     * @return ThirdPartyLoader
     */
    public static function getInstance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new ThirdPartyLoader();
        }

        return self::$_instance;
    }

    /**
     * Get a list of loaded integrations instances
     *
     * @return \Linguise\WordPress\Integrations\LinguiseBaseIntegrations[]
     */
    public function getLoadedIntegrations()
    {
        return array_values($this->loaded_integrations);
    }

    /**
     * Get a list of loaded integrations names
     *
     * @return string[]
     */
    public function getLoadedIntegrationsNames()
    {
        // Only return name of loaded integrations
        return array_keys($this->loaded_integrations);
    }

    /**
     * Get a list of active integrations
     *
     * @return string[]
     */
    public function getActiveIntegrations()
    {
        return $this->active_integrations;
    }

    /**
     * Reload all loaded integrations
     *
     * @return void
     */
    public function reload()
    {
        $active_integrations = [];
        foreach ($this->loaded_integrations as $name => $instance) {
            // If it's active then it can't run, we destory only
            if (in_array($name, $this->active_integrations) && !$instance->shouldLoad()) {
                // Remove from active integrations
                $instance->destroy();
            } elseif (in_array($name, $this->active_integrations) && $instance->shouldLoad()) {
                // Reload the process
                $instance->reload();
                // Add to active integrations
                $active_integrations[] = $name;
            } elseif (!in_array($name, $this->active_integrations) && $instance->shouldLoad()) {
                // Initialize only
                $instance->init();
                // Add to active integrations
                $active_integrations[] = $name;
            }
        }

        $this->active_integrations = $active_integrations;
    }

    /**
     * Internal function to load third-party integrations.
     *
     * This function will try to load all the 3rd party integrations.
     *
     * @return void
     */
    public function loadInternal()
    {
        if ($this->initialized) {
            return;
        }

        if (!function_exists('is_plugin_active')) {
            // Load plugin handler so we can use is_plugin_active
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        // Load the base class
        require_once $this->current_dir . DIRECTORY_SEPARATOR . 'thirdparty' . DIRECTORY_SEPARATOR . 'base-class.php';

        // Load our own native integrations
        $merged_fragment_filters = [];
        $merged_fragment_overrides = [];
        $merged_fragment_attributes = [];
        $merged_wp_rest_intercept = [];

        foreach ($this->native_integrations as $class_path => $class_name) {
            require_once $this->current_dir . DIRECTORY_SEPARATOR . 'thirdparty' . DIRECTORY_SEPARATOR . $class_path . '.php';

            // Check if $class_name
            $class_names = is_array($class_name) ? $class_name : [$class_name];
            foreach ($class_names as $class_name) {
                $class = '\\Linguise\\WordPress\\Integrations\\' . $class_name;
                /**
                 * The integration class instance
                 *
                 * @var \Linguise\WordPress\Integrations\LinguiseBaseIntegrations
                 */
                $instance = new $class();

                if ($instance->shouldLoad()) {
                    $instance->init();

                    $merged_fragment_filters = array_merge($merged_fragment_filters, $instance->getFragmentKeys());
                    $merged_fragment_overrides = array_merge($merged_fragment_overrides, $instance->getFragmentOverrides());
                    $merged_fragment_attributes = array_merge($merged_fragment_attributes, $instance->getFragmentAttributes());
                    $merged_wp_rest_intercept = array_merge($merged_wp_rest_intercept, $instance->getWpRestAjaxIntercept());

                    $this->active_integrations[] = $instance::$name;
                }

                $this->loaded_integrations[$instance::$name] = $instance;
            }
        }

        // You will need to return a mapping of
        // File path -> fully qualified class name
        $third_party_integrations = apply_filters('linguise_3rd_party_integrations', []);
        foreach ($third_party_integrations as $file_path => $class_name) {
            include_once $file_path;

            if (!class_exists($class_name)) {
                // Fail to load
                continue;
            }

            /**
             * The integration class instance
             *
             * @var \Linguise\WordPress\Integrations\LinguiseBaseIntegrations
             */
            $instance = new $class_name();
            if (array_key_exists($instance::$name, $this->loaded_integrations)) {
                // Already loaded, skip
                continue;
            }

            if ($instance->shouldLoad()) {
                $instance->init();

                $merged_fragment_filters = array_merge($merged_fragment_filters, $instance->getFragmentKeys());
                $merged_fragment_overrides = array_merge($merged_fragment_overrides, $instance->getFragmentOverrides());
                $merged_fragment_attributes = array_merge($merged_fragment_attributes, $instance->getFragmentAttributes());
                $merged_wp_rest_intercept = array_merge($merged_wp_rest_intercept, $instance->getWpRestAjaxIntercept());

                $this->active_integrations[] = $instance::$name;
            }

            $this->loaded_integrations[$instance::$name] = $instance;
        }

        add_filter('linguise_fragment_filters', function ($filters) use ($merged_fragment_filters) {
            return array_merge($filters, $merged_fragment_filters);
        }, 10, 1);

        add_filter('linguise_fragment_override', function ($overrides) use ($merged_fragment_overrides) {
            return array_merge($overrides, $merged_fragment_overrides);
        }, 10, 1);

        add_filter('linguise_fragment_attributes', function ($attributes) use ($merged_fragment_attributes) {
            return array_merge($attributes, $merged_fragment_attributes);
        }, 10, 1);

        add_filter('linguise_ajax_intercept_prefixes', function ($prefixes) use ($merged_wp_rest_intercept) {
            return array_merge($prefixes, $merged_wp_rest_intercept);
        });

        // Let other plugins know about the loaded integrations
        $this->initialized = true;
        do_action('linguise_3rd_party_integrations_loaded', $this->loaded_integrations);
    }

    /**
     * Destroy all loaded integrations.
     *
     * This method is called when the plugin is being deactivated or
     * uninstalled.
     *
     * @return void
     */
    public function unloadInternal()
    {
        $destroyed = [];
        foreach ($this->loaded_integrations as $name => $instance) {
            $instance->destroy();
            $destroyed[] = $name;
        }
    
        // Remove destroyed integrations
        do_action('linguise_3rd_party_integrations_unloaded', $destroyed);
    }
}

// Load into global
ThirdPartyLoader::getInstance();
