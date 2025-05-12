<?php

namespace Linguise\WordPress\Integrations;

use Linguise\WordPress\Helper as WPHelper;

defined('ABSPATH') || die('');

/**
 * Integration for WooCommerce Payment Plugin for Stripe
 *
 * This add a list of fragment keys to be translated and adjust
 * locale based on the current active language.
 */
class WCPaymentPluginStripeIntegration extends LinguiseBaseIntegrations
{
    /**
     * Plugin name
     *
     * @var string
     */
    public static $name = 'WooCommerce Payment Plugin Stripe';

    /**
     * A collection of fragment overrides that we want to translate
     *
     * @var array
     */
    protected static $fragment_overrides = [
        [
            'name' => 'wc-stripe-params-v3',
            'match' => 'var wc_stripe_params_v3 = (.*?);',
            'replacement' => 'var wc_stripe_params_v3 = $$JSON_DATA$$;',
        ],
        [
            'name' => 'wc-stripe-messages',
            'match' => 'var wc_stripe_messages = (.*?);',
            'replacement' => 'var wc_stripe_messages = $$JSON_DATA$$;',
        ],
        [
            'name' => 'wc-stripe-checkout-fields',
            'match' => 'var wc_stripe_checkout_fields = (.*?);',
            'replacement' => 'var wc_stripe_checkout_fields = $$JSON_DATA$$;',
        ],
    ];

    /**
     * Initialize the integration.
     * Sets up fragment keys to be translated for Payment Plugin Stripe
     */
    public function __construct()
    {
        // Override so we can add custom keys loop
        $payment_keys_regex_full = [
            '^stripeGeneralData\..*',
            '^elementOptions\..*',
            '^paymentElementOptions\..*',
            '^messageOptions\..*',
            '^confirmParams\..*',
            '^payment_sections\..*',
            '^stripeParams\..*',
            '^(shipping|billing)\_.*\.(autocapitalize|autocomplete|class.*|value)',
            '^merchant_(id|name)$',
            '^button_(\w+)$',
        ];
        
        $payment_keys_exact = [
            'api_key',
            'button',
            'button_size_mode',
            'country_code',
            'currency',
            'local_payment_type',
            'banner_enabled',
            'rest_nonce',
            'page',
            'saved_method_selector',
            'token_selector',
            'user_id',
            'account',
            'mode',
            'version',
            'environment',
            'processing_country',
        ];
        
        $payment_keys_full = [];
        
        foreach ($payment_keys_regex_full as $payment_key) {
            $payment_keys_full[] = [
                'key' => $payment_key,
                'mode' => 'regex_full',
                'kind' => 'deny',
            ];
        }
        foreach ($payment_keys_exact as $payment_key) {
            $payment_keys_full[] = [
                'key' => $payment_key,
                'mode' => 'exact',
                'kind' => 'deny',
            ];
        }

        self::$fragment_keys = $payment_keys_full;
    }

    /**
     * Determines if the integration should be loaded.
     *
     * @return boolean
     */
    public function shouldLoad()
    {
        return is_plugin_active('woocommerce/woocommerce.php') && is_plugin_active('woo-stripe-payment/stripe-payments.php');
    }

    /**
     * Load the integration
     *
     * @return void
     */
    public function init()
    {
        add_filter('wc_stripe_get_site_locale', [$this, 'hookGetLocale'], 1, 1);
    }

    /**
     * Unload the integration
     *
     * @return void
     */
    public function destroy()
    {
        remove_filter('wc_stripe_get_site_locale', [$this, 'hookGetLocale'], 1, 1);
    }

    /**
     * Hooks into wc_stripe_get_site_locale to modify the locale that we want to use
     *
     * @param string $locale The current locale
     *
     * @return string The new locale, either original or modified
     */
    public function hookGetLocale($locale)
    {
        if (!$locale) {
            return $locale;
        }

        $language = WPHelper::getLanguage();

        if (!$language) {
            return $locale;
        }

        $stripeCode = $this->mapLanguage($language);
        if (!empty($stripeCode)) {
            return $stripeCode;
        }

        return $locale;
    }

    /**
     * Maps a linguise language to a stripe language.
     *
     * @param string $linguise_language The linguise language
     *
     * @return string|false The stripe language or false if not mapped
     */
    protected function mapLanguage($linguise_language)
    {
        /**
         * Supported list:
         *
         * @see https://docs.stripe.com/js/appendix/supported_locales
         */
        $mappedLanguages = [
            'ar' => 'ar', // Arabic
            'bg' => 'bg', // Bulgarian (Bulgaria)
            'cs' => 'cs', // Czech (Czech Republic)
            'da' => 'da', // Danish (Denmark)
            'de' => 'de', // German (Germany)
            'el' => 'el', // Greek (Greece)
            'en' => 'en', // English
            'en' => 'en-GB', // English (United Kingdom)
            'es' => 'es', // Spanish (Spain)
            'es' => 'es-419', // Spanish (Latin America)
            'et' => 'et', // Estonian (Estonia)
            'fi' => 'fi', // Finnish (Finland)
            'fr' => 'fr', // French (France)
            'fr' => 'fr-CA', // French (Canada)
            'he' => 'he', // Hebrew (Israel)
            'hr' => 'hr', // Croatian (Croatia)
            'hu' => 'hu', // Hungarian (Hungary)
            'id' => 'id', // Indonesian (Indonesia)
            'it' => 'it', // Italian (Italy)
            'ja' => 'ja', // Japanese (Japan)
            'ko' => 'ko', // Korean (Korea)
            'lt' => 'lt', // Lithuanian (Lithuania)
            'lv' => 'lv', // Latvian (Latvia)
            'ms' => 'ms', // Malay (Malaysia)
            'mt' => 'mt', // Maltese (Malta)
            'nl' => 'nl', // Dutch (Netherlands)
            'pl' => 'pl', // Polish (Poland)
            'pt' => 'pt-BR', // Portuguese (Brazil)
            'pt' => 'pt', // Portuguese (Brazil)
            'ro' => 'ro', // Romanian (Romania)
            'ru' => 'ru', // Russian (Russia)
            'sk' => 'sk', // Slovak (Slovakia)
            'sl' => 'sl', // Slovenian (Slovenia)
            'sv' => 'sv', // Swedish (Sweden)
            'th' => 'th', // Thai (Thailand)
            'tr' => 'tr', // Turkish (Turkey)
            'vi' => 'vi', // Vietnamese (Vietnam)
            'zh-cn' => 'zh', // Chinese Simplified (China)
            'zh-tw' => 'zh-TW', // Chinese Traditional (Taiwan)
        ];

        if (!array_key_exists($linguise_language, $mappedLanguages)) {
            return false;
        }

        return $mappedLanguages[$linguise_language];
    }
}
