<?php

namespace Linguise\WordPress\Integrations;

use Linguise\WordPress\Helper as WPHelper;

defined('ABSPATH') || die('');

/**
 * Integration for WC PDF Invoices & Packing Slips Professional
 */
class WCPDFIPSProIntegration extends LinguiseBaseIntegrations
{
    /**
     * Plugin name
     *
     * @var string
     */
    public static $name = 'WooCommerce PDF Invoices & Packing Slips Professional';

    /**
     * Decides if the integration should be loaded.
     *
     * @codeCoverageIgnore
     *
     * @return boolean
     */
    public function shouldLoad()
    {
        $options = linguiseGetOptions();
        return is_plugin_active('woocommerce/woocommerce.php') && is_plugin_active('woocommerce-pdf-ips-pro/woocommerce-pdf-ips-pro.php') && (bool)$options['woocommerce_emails_translation'];
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
        add_filter('wpo_wcpdf_pro_multilingual_supported_plugins', [$this, 'hookMultilingualSupportedPlugins'], 999, 1);
        add_filter('wpo_wcpdf_pro_multilingual_html_order_language', [$this, 'hookMultilingualHtmlOrderLanguage'], 999, 2);
        add_filter('wpo_wcpdf_get_html', [$this, 'hookTranslateDocument'], 999, 2);

        // for needs translation always return false, so it would always go to our system for translation
        add_filter('wpo_wcpdf_pro_multilingual_html_needs_translation', [$this, 'hookLinguiseNo'], 999, 1);
    }

    /**
     * Cleans up filters for the integration.
     *
     * @codeCoverageIgnore
     *
     * @return void
     */
    public function destroy()
    {
        remove_filter('wpo_wcpdf_pro_multilingual_supported_plugins', [$this, 'hookMultilingualSupportedPlugins'], 999);
        remove_filter('wpo_wcpdf_pro_multilingual_html_order_language', [$this, 'hookMultilingualHtmlOrderLanguage'], 999);
        remove_filter('wpo_wcpdf_get_html', [$this, 'hookTranslateDocument'], 999);

        remove_filter('wpo_wcpdf_pro_multilingual_html_needs_translation', [$this, 'hookLinguiseNo'], 999);
    }

    /**
     * Hook into the plugin to say that linguise is supported
     *
     * @param array $filters The list of supported plugins
     *
     * @return array
     */
    public function hookMultilingualSupportedPlugins($filters)
    {
        // add linguise
        $filters['linguise'] = array(
            // function is something is called to check if plugin is loaded, since Linguise can enter here that means it's likely loaded
            'function' => '__return_true',
            'name' => 'Linguise',
            'support' => 'html'
        );

        return $filters;
    }

    /**
     * Hook into the plugin to tell the current language
     *
     * @param string             $current_lang The current language
     * @param \WC_Abstract_Order $order        WC Order
     *
     * @return string
     */
    public function hookMultilingualHtmlOrderLanguage($current_lang, $order)
    {
        $ling_meta = $order->get_meta('linguise_language', true);
        if (empty($ling_meta)) {
            return $current_lang;
        }
        return $ling_meta;
    }

    /**
     * Hook into the plugin to tell the current language
     *
     * @param array $args The list of arguments
     *
     * @return boolean
     */
    public function hookLinguiseNo($args)
    {
        return false;
    }

    /**
     * Hook into the plugin to translate the document
     *
     * @param string $original_html The original HTML
     * @param object $document      The WCPDF IPS document
     *
     * @return string
     */
    public function hookTranslateDocument($original_html, $document)
    {
        if (empty($document)) {
            return $original_html;
        }

        $order = $document->order;

        if (empty($order)) {
            return $original_html;
        }

        // similar to how it's done on the main plugin
        if (is_callable(array($order, 'get_type')) && 'shop_order_refund' === $order->get_type()) {
            $order = wc_get_order($order->get_parent_id());

            if (empty($order)) {
                return $original_html;
            }
        }

        $wc_lang = $this->hookMultilingualHtmlOrderLanguage('', $order);
        if (empty($wc_lang)) {
            return $original_html;
        }

        $options = linguiseGetOptions();
        $original_lang = $options['default_language'];
        $original_html = str_replace('html lang="' . $wc_lang . '"', 'html lang="' . $original_lang . '"', $original_html);

        if (!WPHelper::isTranslatableLanguage($wc_lang)) {
            // not a translatable language
            return $original_html;
        }

        $translated_emails = $this->translateFragments($original_html, $wc_lang, '/');
        if (empty($translated_emails)) {
            // Failed to translate
            return $original_html;
        }

        if (isset($translated_emails->redirect)) {
            // Somehow we got this...?
            return $original_html;
        }

        if (!isset($translated_emails->content)) {
            // Failed to translate
            return $original_html;
        }
        if (empty($translated_emails->content)) {
            // Failed to translate
            return $original_html;
        }

        $decoded_email = html_entity_decode($translated_emails->content, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return $decoded_email;
    }
}
