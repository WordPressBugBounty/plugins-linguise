<?php

namespace Linguise\WordPress\Integrations;

defined('ABSPATH') || die('');

/**
 * Integration for WooCommerce Admin AJAX for emails
 *
 * Code to handle admin ajax request
 */
class WooCommerceAdminEmailsIntegration extends WooCommerceEmailsIntegration
{
    /**
     * Plugin name
     *
     * @var string
     */
    public static $name = 'WooCommerce Admin Emails';

    /**
     * Determines if the integration should be loaded.
     *
     * @return boolean
     */
    public function shouldLoad()
    {
        return parent::shouldLoad() && wp_doing_ajax();
    }

    /**
     * Load the integration
     *
     * @return void
     */
    public function init()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action
        if (!empty($_REQUEST['action']) && $_REQUEST['action'] === 'woocommerce_mark_order_status') {
            add_filter('woocommerce_mail_callback_params', [$this, 'hookWCEmail'], 100, 2);
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
        if (!empty($_REQUEST['action']) && $_REQUEST['action'] === 'woocommerce_mark_order_status') {
            remove_filter('woocommerce_mail_callback_params', [$this, 'hookWCEmail'], 100, 2);
        }
    }
}
