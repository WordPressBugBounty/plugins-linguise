<?php
/**
 * Translate checkout payment method
 * Plugin: woocommerce-gateway-stripe
 */
use Linguise\WordPress\Helper as WPHelper;

include_once ABSPATH . 'wp-admin/includes/plugin.php';

if (!is_plugin_active('woocommerce-gateway-stripe/woocommerce-gateway-stripe.php')) {
    return;
}

/**
 * Maps a linguise language to a stripe language.
 *
 * @param string $linguiseLanguage The linguise language
 *
 * @return string The stripe language
 */
function linguise_woocommerce_gateway_stripe_language($linguiseLanguage)
{
  /**
   * Map Linguise Language and Stripe Language
   *
   * @see https://docs.stripe.com/js/appendix/supported_locales
   */
    $mappedLanguages = [
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

    if (!array_key_exists($linguiseLanguage, $mappedLanguages)) {
        return false;
    }

    return $mappedLanguages[$linguiseLanguage];
}

// Classic checkout
add_filter('wc_stripe_upe_params', function ($params) {
    if (!isset($params['locale']) || $params['locale'] === 'auto') {
        return $params;
    }

    $linguiseLanguage = WPHelper::getLanguage();

    if (!$linguiseLanguage) {
        return $params;
    }
    
    $stripeLanguageCode = linguise_woocommerce_gateway_stripe_language($linguiseLanguage);

    if (!$stripeLanguageCode) {
        return $params;
    }

    $params['locale'] = $stripeLanguageCode;

    return $params;
});

// Checkout block
add_filter('wc_stripe_params', function ($params) {
    if (!isset($params['locale']) || $params['locale'] === 'auto') {
        return $params;
    }

    $linguiseLanguage = WPHelper::getLanguage();

    if (!$linguiseLanguage) {
        return $params;
    }
    
    $stripeLanguageCode = linguise_woocommerce_gateway_stripe_language($linguiseLanguage);

    if (!$stripeLanguageCode) {
        return $params;
    }

    $params['locale'] = $stripeLanguageCode;

    return $params;
});
