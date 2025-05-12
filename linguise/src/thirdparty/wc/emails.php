<?php

namespace Linguise\WordPress\Integrations;

use Linguise\WordPress\Helper as WPHelper;

defined('ABSPATH') || die('');

/**
 * Integration for WooCommerce
 *
 * A ton of code handling for WooCommerce related stuff.
 */
class WooCommerceEmailsIntegration extends LinguiseBaseIntegrations
{
    /**
     * Plugin name
     *
     * @var string
     */
    public static $name = 'WooCommerce Emails';

    /**
     * The header name and value to avoid double translation.
     *
     * @var string
     */
    protected static $header_dupes = 'X-Woo-Linguise-Translated: 1';

    /**
     * Determines if the integration should be loaded.
     *
     * @return boolean
     */
    public function shouldLoad()
    {
        $options = linguiseGetOptions();
        return is_plugin_active('woocommerce/woocommerce.php') && (bool)$options['woocommerce_emails_translation'];
    }

    /**
     * Load the integration
     *
     * @return void
     */
    public function init()
    {
        add_action('woocommerce_new_order', [$this, 'hookWCNewOrder'], 100, 2);
        add_filter('woocommerce_mail_callback_params', [$this, 'hookWCEmail'], 100, 2);
    }

    /**
     * Unload the integration
     *
     * @return void
     */
    public function destroy()
    {
        remove_action('woocommerce_new_order', [$this, 'hookWCNewOrder'], 100, 2);
        remove_filter('woocommerce_mail_callback_params', [$this, 'hookWCEmail'], 100, 2);
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
        if (!$language_meta) {
            // Check referer
            $language_meta = WPHelper::getLanguageFromReferer();
        }
        return $language_meta;
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

        $language_meta = WPHelper::getLanguage();
        if (!$language_meta) {
            $language_meta = WPHelper::getLanguageFromReferer();
        }

        if ($language_meta === null) {
            return;
        }

        // We add to both post meta and order meta
        add_post_meta($order_id, 'linguise_language', $language_meta);

        $order->update_meta_data('linguise_language', $language_meta);
        $order->save_meta_data();
        $order->apply_changes();
    }

    /**
     * Translate WooCommerce emails
     *
     * @param array     $args     The email data
     * @param \WC_Email $wc_email The email model
     *
     * @return array
     */
    public function hookWCEmail($args, $wc_email)
    {
        if (!$wc_email->is_customer_email()) {
            return $args;
        }

        // Check if email already translated
        if ($this->isEmailTranslated($args[3])) {
            // Do NOT translate again
            return $args;
        }

        $language_meta = method_exists($wc_email->object, 'get_meta') ? $wc_email->object->get_meta('linguise_language', true) : '';
        if (empty($language_meta)) {
            $language_meta = method_exists($wc_email->object, 'get_id') ? get_post_meta($wc_email->object->get_id(), 'linguise_language', true) : '';
        }
        if (empty($language_meta)) {
            $language_meta = WPHelper::getLanguage();
        }
        if (empty($language_meta)) {
            $language_meta = WPHelper::getLanguageFromReferer();
        }

        if (empty($language_meta) || !WPHelper::isTranslatableLanguage($language_meta)) {
            return $args;
        }

        $content = '<html><head></head><body>';
        $content .= '<divlinguisesubject>' . $args[1] . '</divlinguisesubject>';
        $content .= '<divlinguisecontent>' . $args[2] . '</divlinguisecontent>';
        $content .= '</body>';

        $translated_emails = $this->translateFragments($content, $language_meta, '/');

        if (empty($translated_emails)) {
            // Failed to translate
            return $args;
        }

        if (isset($translated_emails->redirect)) {
            // Somehow we got this...?
            return $args;
        }

        preg_match('/<divlinguisesubject>(.*?)<\/divlinguisesubject>/s', $translated_emails->content, $matches);
        if (!$matches) {
            return $args;
        }

        $args[1] =  html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Mark as translated
        $args[3] .= self::$header_dupes . "\r\n";

        preg_match('/<divlinguisecontent>(.*?)<\/divlinguisecontent>/s', $translated_emails->content, $matches);
        if (!$matches) {
            return $args;
        }

        $args[2] = $matches[1];

        return $args;
    }

    /**
     * Avoid double translating emails by checking if the email is already translated or not.
     *
     * @param string $headers The email headers
     *
     * @return boolean
     */
    private function isEmailTranslated($headers)
    {
        return strpos($headers, self::$header_dupes) !== false;
    }
}
