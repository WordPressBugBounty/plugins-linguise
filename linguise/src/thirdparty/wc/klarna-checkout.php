<?php

namespace Linguise\WordPress\Integrations;

use Linguise\WordPress\Helper as WPHelper;

defined('ABSPATH') || die('');

/**
 * Integration with Klarna Checkout for WooCommerce
 *
 * The following fix some issue with locale usages
 * and ensuring some stuff getting translated without dynamic content.
 */
class WCKlarnaCheckoutIntergration extends LinguiseBaseIntegrations
{
    /**
     * Plugin name
     *
     * @var string
     */
    public static $name = 'Klarna Checkout for WooCommerce';

    /**
     * Determines if the integration should be loaded.
     *
     * @return boolean
     */
    public function shouldLoad()
    {
        return is_plugin_active('woocommerce/woocommerce.php') && is_plugin_active('klarna-checkout-for-woocommerce/klarna-checkout-for-woocommerce.php');
    }

    /**
     * Load the integration
     *
     * @return void
     */
    public function init()
    {
        add_filter('kco_locale', [$this, 'hookLocale'], 1, 1);
        add_filter('kco_additional_checkboxes', [$this, 'hookAdditionalCheckbox'], 1, 1);
    }

    /**
     * Unload the integration
     *
     * @return void
     */
    public function destroy()
    {
        remove_filter('kco_locale', [$this, 'hookLocale'], 1, 1);
        remove_filter('kco_additional_checkboxes', [$this, 'hookAdditionalCheckbox'], 1, 1);
    }

    /**
     * Maps a linguise language to a Klarna supported language.
     *
     * @param string $linguise_language The linguise language
     *
     * @return string|false The klarna language or false if not mapped
     */
    protected function mapLanguage($linguise_language)
    {
        // Linguise -> Klarna lang
        $mapLanguages = [
            'da' => 'da', // Danish
            'nl' => 'nl', // Dutch
            'en' => 'en', // English
            'fi' => 'fi', // Finish
            'fr' => 'fr', // French
            'de' => 'de', // German
            'it' => 'it', // Italian
            'no' => 'nb', // Norwegian (Bokmål)
            'pl' => 'pl', // Polish
            'pt' => 'pt', // Portuguese
            'es' => 'es', // Spanish
            'sv' => 'sv', // Swedish
        ];

        // fallback, use klarna language
        if (!array_key_exists($linguise_language, $mapLanguages)) {
            return false;
        }

        return $mapLanguages[$linguise_language];
    }

    /**
     * Hook into kco_locale to modify the Klarna locale that we want to use.
     *
     * @param string $locale The current locale
     *
     * @return string The new locale, either original or modified
     */
    public function hookLocale($locale)
    {
        $language_meta = WPHelper::getLanguage();
        if (!$language_meta) {
            // not found, use current Klarna language
            return $locale;
        }

        $klarna_lang = $this->mapLanguage($language_meta);
        if (!$klarna_lang) {
            // No match
            return $locale;
        }

        return $klarna_lang;
    }

    /**
     * Hook into kco_additional_checkboxes to translate the terms and conditions checkbox text.
     *
     * @param array $additional_checkboxes The additional checkboxes
     *
     * @return array The modified checkboxes
     */
    public function hookAdditionalCheckbox($additional_checkboxes)
    {
        // Check for language first
        $language_meta = WPHelper::getLanguage();
        if (!$language_meta) {
            return $additional_checkboxes;
        }

        // Map language
        $klarna_lang = $this->mapLanguage($language_meta);
        if (!$klarna_lang) {
            return $additional_checkboxes;
        }

        foreach ($additional_checkboxes as $index => $checkbox) {
            if ($checkbox['id'] === 'terms_and_conditions') {
                $html_content = '<html><head></head><body>';
                $html_content .= $additional_checkboxes[$index]['text'];
                $html_content .= '</body></html>';

                $translated_content = $this->translateFragments($html_content, $language_meta, '/');

                if (empty($translated_content)) {
                    // Failed to translate
                    continue;
                }

                if (isset($translated_content->redirect)) {
                    // We got redirect URL for some reason?
                    continue;
                }

                preg_match('/<body>(.*)<\/body>/s', $translated_content->content, $matches);

                if (!$matches) {
                    // No body match
                    continue;
                }

                $additional_checkboxes[$index]['text'] = $matches[1];
                break;
            }
        }

        return $additional_checkboxes;
    }
}
