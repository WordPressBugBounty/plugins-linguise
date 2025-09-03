<?php

namespace Linguise\WordPress\Integrations;

use Linguise\WordPress\Helper as WPHelper;

defined('ABSPATH') || die('');

/**
 * Integration for WooCommerce Gateway Stripe
 *
 * This helps translate the checkout payment method for Stripe.
 */
class WCGatewayStripeIntegration extends LinguiseBaseIntegrations
{
    /**
     * Plugin name
     *
     * @var string
     */
    public static $name = 'WooCommerce Gateway Stripe';

    /**
     * A collection of fragment keys that will be translated
     *
     * @var array{key:string, mode:'exact'|'path'|'wildcard'|'regex'|'regex_full',kind:'allow'|'deny'}
     */
    protected static $fragment_keys = [
        [
            'key' => 'allowed_shipping_countries\..*',
            'mode' => 'regex_full',
            'kind' => 'deny',
        ],
        [
            'key' => 'checkout\.((?:country|currency)_code|needs_shipping)$',
            'mode' => 'regex_full',
            'kind' => 'deny',
        ],
        [
            'key' => 'baseLocation\.(country|state)$',
            'mode' => 'regex_full',
            'kind' => 'deny',
        ],
        [
            'key' => 'blocksAppearance\.rules\..*',
            'mode' => 'regex_full',
            'kind' => 'deny',
        ]
    ];

    /**
     * Decides if the integration should be loaded.
     *
     * @return boolean
     */
    public function shouldLoad()
    {
        return is_plugin_active('woocommerce-gateway-stripe/woocommerce-gateway-stripe.php');
    }

    /**
     * Initializes the integration.
     *
     * @return void
     */
    public function init()
    {
        // Classic checkout
        add_filter('wc_stripe_upe_params', [$this, 'hookTranslateParams'], 10, 1);
        // Block checkout
        add_filter('wc_stripe_params', [$this, 'hookTranslateParams'], 10, 1);

        add_filter('linguise_after_apply_translated_fragments_auto', [$this, 'restoreOriginalConfigClassicCheckout'], 10, 2);
        add_filter('linguise_after_apply_translated_fragments_override', [$this, 'restoreOriginalConfigBlockCheckout'], 10, 2);
    }

    /**
     * Unload the integration
     *
     * @return void
     */
    public function destroy()
    {
        // Classic checkout
        remove_filter('wc_stripe_upe_params', [$this, 'hookTranslateParams'], 10);
        // Block checkout
        remove_filter('wc_stripe_params', [$this, 'hookTranslateParams'], 10);

        remove_filter('linguise_after_apply_translated_fragments_auto', [$this, 'restoreOriginalConfigClassicCheckout'], 10, 2);
        remove_filter('linguise_after_apply_translated_fragments_override', [$this, 'restoreOriginalConfigBlockCheckout'], 10, 2);
    }

    /**
     * Map Linguise Language to Stripe Language
     *
     * @param string $language_code The language code to map.
     *
     * @return string|false The mapped language code or false if not found.
     */
    protected function mapStripeLanguage($language_code)
    {
        /**
         * Map Linguise Language and Stripe Language
         *
         * @see https://docs.stripe.com/js/appendix/supported_locales
         */
        $mapped_languages = [
            'ar' => 'ar',  //Arabic
            'bg' => 'bg',  //Bulgarian (Bulgaria)
            'cs' => 'cs',  //Czech (Czech Republic)
            'da' => 'da',  //Danish (Denmark)
            'de' => 'de',  //German (Germany)
            'el' => 'el',  //Greek (Greece)
            'en' => 'en',  //English
            'en' => 'en-GB', //English (United Kingdom)
            'es' => 'es',  //Spanish (Spain)
            'es' => 'es-419',  //Spanish (Latin America)
            'et' => 'et',  //Estonian (Estonia)
            'fi' => 'fi',  //Finnish (Finland)
            'fr' => 'fr',  //French (France)
            'fr' => 'fr-CA', //French (Canada)
            'he' => 'he',  //Hebrew (Israel)
            'hr' => 'hr',  //Croatian (Croatia)
            'hu' => 'hu',  //Hungarian (Hungary)
            'id' => 'id',  //Indonesian (Indonesia)
            'it' => 'it',  //Italian (Italy)
            'ja' => 'ja',  //Japanese (Japan)
            'ko' => 'ko',  //Korean (Korea)
            'lt' => 'lt',  //Lithuanian (Lithuania)
            'lv' => 'lv',  //Latvian (Latvia)
            'ms' => 'ms',  //Malay (Malaysia)
            'mt' => 'mt',  //Maltese (Malta)
            'nl' => 'nl',  //Dutch (Netherlands)
            'pl' => 'pl',  //Polish (Poland)
            'pt' => 'pt-BR', //Portuguese (Brazil)
            'pt' => 'pt',  //Portuguese (Brazil)
            'ro' => 'ro',  //Romanian (Romania)
            'ru' => 'ru',  //Russian (Russia)
            'sk' => 'sk',  //Slovak (Slovakia)
            'sl' => 'sl',  //Slovenian (Slovenia)
            'sv' => 'sv',  //Swedish (Sweden)
            'th' => 'th',  //Thai (Thailand)
            'tr' => 'tr',  //Turkish (Turkey)
            'vi' => 'vi',  //Vietnamese (Vietnam)
            'zh-cn' => 'zh',  //Chinese Simplified (China)
            'zh-tw' => 'zh-TW' //Chinese Traditional (Taiwan)
        ];

        if (!array_key_exists($language_code, $mapped_languages)) {
            return false;
        }

        return $mapped_languages[$language_code];
    }

    /**
     * Translate the Stripe locale in the JSON params to the correct language
     *
     * @param array $params The Stripe params
     *
     * @return array The translated params
     */
    public function hookTranslateParams($params)
    {
        if (!isset($params['locale']) || $params['locale'] === 'auto') {
            return $params;
        }

        $linguise_lang = WPHelper::getLanguage();

        if (!$linguise_lang) {
            return $params;
        }

        $stripe_code = $this->mapStripeLanguage($linguise_lang);
    
        if (!$stripe_code) {
            return $params;
        }

        $params['locale'] = $stripe_code;

        return $params;
    }

    /**
     * Restore the original block appearance structure that was accidentally
     * changed into an array
     *
     * @param string $fragment_name The name of the fragment being translated.
     * @param array  $replaced_json The fragment being translated.
     *
     * @return array The original block appearance structure.
     */
    public function restoreOriginalConfigClassicCheckout($fragment_name, $replaced_json)
    {
        if ($fragment_name === 'wc-stripe-upe-classic') {
            if (array_key_exists('blocksAppearance', $replaced_json)) {
                $replaced_json['blocksAppearance'] = $this->arrayToObject($replaced_json['blocksAppearance']);
            }
        }

        return $replaced_json;
    }

    /**
     * Restore the original block appearance structure that was accidentally
     * changed into an array
     *
     * @param string $fragment_name The name of the fragment being translated.
     * @param string $replaced_json The fragment being translated.
     *
     * @return array The original block appearance structure.
     */
    public function restoreOriginalConfigBlockCheckout($fragment_name, $replaced_json)
    {
        $replaced_json = json_decode($replaced_json);
        if ($fragment_name === 'wc-settings-encoded') {
            if (isset($replaced_json->paymentMethodData->stripe->blocksAppearance)) {
                $rules = $replaced_json->paymentMethodData->stripe->blocksAppearance->rules;
                $replaced_json->paymentMethodData->stripe->blocksAppearance->rules = $this->arrayToObject($rules);
            }
        }

        return json_encode($replaced_json);
    }

    /**
     * Recursive convert an array into an object.
     *
     * @param array $data The data to convert.
     *
     * @return object The converted object.
     */
    public function arrayToObject($data)
    {
        if (is_array($data)) {
            if (empty($data)) {
                return new \stdClass(); // convert empty array to object
            }
            foreach ($data as $key => $value) {
                $data[$key] = $this->arrayToObject($value);
            }
            return (object) $data;
        } elseif ($data instanceof \stdClass) {
            foreach ($data as $key => $value) {
                $data->$key = $this->arrayToObject($value);
            }
            return $data;
        }

        return $data;
    }
}
