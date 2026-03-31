<?php

namespace Linguise\WordPress\Integrations;

use Linguise\WordPress\ThirdPartyLoader;

defined('ABSPATH') || die('');

/**
 * Integration for WooCommerce Product Price Based on Countries (WCPBC)
 */
class WCPBCIntegration extends LinguiseBaseIntegrations
{
    /**
     * Plugin name
     *
     * @var string
     */
    public static $name = 'WooCommerce Product Price Based on Countries';

    /**
     * Decides if the WCPBC integration should be loaded.
     *
     * @codeCoverageIgnore
     *
     * @return boolean
     */
    public function shouldLoad()
    {
        return is_plugin_active('woocommerce/woocommerce.php')
            && is_plugin_active('woocommerce-product-price-based-on-countries/woocommerce-product-price-based-on-countries.php');
    }

    /**
     * Registers filters for the integration.
     *
     * @codeCoverageIgnore
     *
     * @return void
     */
    public function init()
    {
        add_filter('wc_price_based_country_ajax_geolocation_widget_content', [$this, 'translateWidgetContent'], 999, 1);
    }

    /**
     * Translates the WCPBC geolocation widget HTML via the WooCommerce fragment translator.
     *
     * @param string $html The widget HTML returned by WCPBC.
     *
     * @return string The translated HTML, or the original HTML if translation is unavailable.
     */
    public function translateWidgetContent($html)
    {
        if (empty($html)) {
            return $html;
        }

        $wc_integration = $this->getWooCommerceIntegration();
        if ($wc_integration === null) {
            return $html;
        }

        $translated = $wc_integration->hookWCFragments($html);
        if (!empty($translated)) {
            return $translated;
        }

        return $html;
    }

    /**
     * Retrieves the active WooCommerceIntegration instance from the ThirdPartyLoader.
     *
     * @return WooCommerceIntegration|null
     */
    private function getWooCommerceIntegration()
    {
        $integrations = ThirdPartyLoader::getInstance()->getLoadedIntegrations();
        foreach ($integrations as $integration) {
            if ($integration instanceof WooCommerceIntegration) {
                return $integration;
            }
        }

        return null;
    }
}
