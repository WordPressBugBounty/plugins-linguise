<?php

namespace Linguise\Script;

use Linguise\Vendor\Linguise\Script\Core\Response;

defined('LINGUISE_SCRIPT_TRANSLATION') || die('');

/**
 * Configuration
 */
class Configuration
{
    /**
     * WordPress CMS
     *
     * @var string
     */
    public static $cms = 'wordpress';

    /**
     * List of WordPress strings we found to translate
     *
     * @var array
     */
    protected static $WPOriginalStrings = [];

    /**
     * Extracted json data from WordPress vars
     *
     * @var array
     */
    protected static $WPJsonData = [];

    /**
     * List of nested variables we have json_decoded
     * We'll need to json_encode it back before rendering
     *
     * @var array
     */
    protected static $WPJsonDecoded = [];

    /**
     * List of script and variables to look into
     * We'll add the script to look for depending on the enabled plugins
     *
     * @var array
     * dev can add their own WP vars by hooking into ConfigurationLocal.php and fill this array as their convenience
     */
    protected static $WPKnownVars = [];

    /**
     * Hook onBeforeTranslation
     *
     * @return void
     */
    public static function onBeforeTranslation()
    {
        self::excludeAdminBar();

        // Extract WordPress translation from Scripts
        self::scriptDataExtraction();
    }

    /**
     * Hook onAfterTranslation
     *
     * @return void
     */
    public static function onAfterTranslation()
    {

        // Insert back WordPress translation into Scripts
        self::scriptDataInsertion();
    }

    /**
     * Do not translate the adminbar
     *
     * @return void
     */
    protected static function excludeAdminBar()
    {
        $response = Response::getInstance();
        $content  = $response->getContent();
        $content = str_replace('<div id="wpadminbar" ', '<div id="wpadminbar" translate="no" ', $content);
        $response->setContent($content);
    }

    /**
     * Parse the content to extract JavaScript JSON encoded translatable variables
     *
     * @return void
     */
    protected static function scriptDataExtraction()
    {
        // If WooCommerce is installed, then add the WooCommerce script and vars to the list
        if (defined('LINGUISE_SCRIPT_TRANSLATION_WOOCOMMERCE') && LINGUISE_SCRIPT_TRANSLATION_WOOCOMMERCE) {
            $WooCommerceKnownVars = [
                'wc-geolocation'        => [
                    (object)['type' => 'url', 'label' => 'home_url'],
                ],
                'wc-single-product'     => [
                    (object)['type' => 'string', 'label' => 'i18n_required_rating_text'],
                ],
                'wc-checkout'           => [
                    (object)['type' => 'string', 'label' => 'i18n_checkout_error'],
                ],
                'wc-address-i18n'       => [
                    'locale' => [
                        'AL' => [
                            'state' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ]
                        ],
                        'AO' => [
                            'state' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ]
                        ],
                        'AU' => [
                            'city'     => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ],
                            'postcode' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ],
                            'state'    => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ]
                        ],
                        'BA' => [
                            'state' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ]
                        ],
                        'BD' => [
                            'state' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ]
                        ],
                        'BO' => [
                            'state' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ]
                        ],
                        'CA' => [
                            'postcode' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ],
                            'state'    => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ]
                        ],
                        'CH' => [
                            'state' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ]
                        ],
                        'CL' => [
                            'state' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ]
                        ],
                        'CN' => [
                            'postcode' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ],
                            'state' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ]
                        ],
                        'CO' => [
                            'state' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ]
                        ],
                        'CR' => [
                            'state' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ]
                        ],
                        'DO' => [
                            'state' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ]
                        ],
                        'EC' => [
                            'state' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ]
                        ],
                        'GH' => [
                            'state' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ]
                        ],
                        'GT' => [
                            'state' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ]
                        ],
                        'HK' => [
                            'city'  => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ],
                            'state' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ]
                        ],
                        'HN' => [
                            'state' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ]
                        ],
                        'HU' => [
                            'state' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ]
                        ],
                        'ID' => [
                            'postcode' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ],
                            'state' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ]
                        ],
                        'IE' => [
                            'postcode' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ],
                            'state'    => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ]
                        ],
                        'IN' => [
                            'postcode' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ],
                            'state'    => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ]
                        ],
                        'IT' => [
                            'state' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ]
                        ],
                        'JM' => [
                            'city'     => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ],
                            'postcode' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ],
                            'state'    => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ]
                        ],
                        'JP' => [
                            'postcode' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ],
                            'state' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ],
                        ],
                        'LV' => [
                            'state' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ]
                        ],
                        'MZ' => [
                            'state' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ]
                        ],
                        'NI' => [
                            'state' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ]
                        ],
                        'NG' => [
                            'postcode' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ],
                            'state'    => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ]
                        ],
                        'NZ' => [
                            'postcode' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ],
                        ],
                        'NP' => [
                            'state' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ],
                        ],
                        'PA' => [
                            'state' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ]
                        ],
                        'PR' => [
                            'city' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ],
                        ],
                        'PY' => [
                            'state' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ]
                        ],
                        'RO' => [
                            'state' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ]
                        ],
                        'RS' => [
                            'state' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ]
                        ],
                        'SV' => [
                            'state' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ]
                        ],
                        'ES' => [
                            'state' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ]
                        ],
                        'LI' => [
                            'state' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ]
                        ],
                        'MD' => [
                            'state' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ]
                        ],
                        'TR' => [
                            'state' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ]
                        ],
                        'UG' => [
                            'city'  => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ],
                            'state' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ]
                        ],
                        'US' => [
                            'postcode' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ],
                            'state'    => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ]
                        ],
                        'UY' => [
                            'state' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ]
                        ],
                        'GB' => [
                            'postcode' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ],
                            'state'    => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ]
                        ],
                        'ST' => [
                            'state' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ]
                        ],
                        'ZA' => [
                            'state' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ]
                        ],
                        'default' => [
                            'first_name' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ],
                            'last_name'  => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ],
                            'company'    => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ],
                            'country'   => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ],
                            'address_1' => [
                                (object)['type' => 'string', 'label' => 'label'],
                                (object)['type' => 'string', 'label' => 'placeholder'],
                            ],
                            'address_2' => [
                                (object)['type' => 'string', 'label' => 'label'],
                                (object)['type' => 'string', 'label' => 'placeholder'],
                            ],
                            'city' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ],
                            'state' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ],
                            'postcode' => [
                                (object)['type' => 'string', 'label' => 'label'],
                            ],
                        ],
                    ],
                ],
//            'wc-cart' => [
//
//            ],
//            'wc-cart-fragments' => [
//
//            ],
                'wc-add-to-cart'        => [
                    (object)['type' => 'string', 'label' => 'i18n_view_cart'],
                    (object)['type' => 'url', 'label' => 'cart_url'],
                ],
                'wc-add-to-cart-variation'   => [
                    (object)['type' => 'string', 'label' => 'i18n_no_matching_variations_text'],
                    (object)['type' => 'string', 'label' => 'i18n_make_a_selection_text'],
                    (object)['type' => 'string', 'label' => 'i18n_unavailable_text'],
                ],
                'wc-country-select'     => [
                    (object)['type' => 'string', 'label' => 'i18n_select_state_text'],
                    (object)['type' => 'string', 'label' => 'i18n_no_matches'],
                    (object)['type' => 'string', 'label' => 'i18n_ajax_error'],
                    (object)['type' => 'string', 'label' => 'i18n_input_too_short_1'],
                    (object)['type' => 'string', 'label' => 'i18n_input_too_short_n'],
                    (object)['type' => 'string', 'label' => 'i18n_input_too_long_1'],
                    (object)['type' => 'string', 'label' => 'i18n_input_too_long_n'],
                    (object)['type' => 'string', 'label' => 'i18n_selection_too_long_1'],
                    (object)['type' => 'string', 'label' => 'i18n_selection_too_long_n'],
                    (object)['type' => 'string', 'label' => 'i18n_load_more'],
                    (object)['type' => 'string', 'label' => 'i18n_searching'],
                ],
                'wc-password-strength-meter' => [
                    (object)['type' => 'string', 'label' => 'min_password_strength'],
                    (object)['type' => 'string', 'label' => 'stop_checkout'],
                    (object)['type' => 'string', 'label' => 'i18n_password_error'],
                    (object)['type' => 'string', 'label' => 'i18n_password_hint'],
                ]
            ];
            self::$WPKnownVars = array_merge(self::$WPKnownVars, $WooCommerceKnownVars);
        }

        // We can add here more JS scripts to translate

        if (empty(self::$WPKnownVars)) {
            // No script to handle
            return;
        }

        $response = Response::getInstance();
        $content  = $response->getContent();

        // Loop over all JS vars we know we should translate
        foreach (self::$WPKnownVars as $WPVarName => $WPVarValues) {
            // Extract the data from the
            $extractedData = self::extractWPVars($WPVarName, $content);
            if (!$extractedData) {
                continue;
            }

            self::$WPOriginalStrings[$WPVarName] = [];
            self::$WPJsonData[$WPVarName]        = $extractedData;
            self::extractWordsToTranslate($WPVarName, $WPVarValues, self::$WPJsonData[$WPVarName], [], self::$WPOriginalStrings[$WPVarName]);
        }

        // No WP variables found
        if (empty(self::$WPOriginalStrings)) {
            return;
        }

        $appendContent = '';
        foreach (self::$WPOriginalStrings as $WPVar => $WPTranslations) {
            foreach ($WPTranslations as $WPTranslation) {
                $lastItemId = count($WPTranslation) - 1;

                if ($WPTranslation[$lastItemId]['type'] === 'url') {
                    $tag = 'a';
                } else {
                    $tag = 'div';
                }

                $appendContent .= '<' . $tag . ' data-linguise-js-vars="' . $WPVar . '"';
                for ($ij = 0; $ij < $lastItemId; $ij ++) {
                    $appendContent .= 'data-linguise-js-vars-' . $ij . '="' . $WPTranslation[ $ij ] . '"'; // fixme: replace <>"'
                }

                $appendContent .= 'data-linguise-js-vars-' . $lastItemId . '="' . $WPTranslation[ $lastItemId ]['label'] . '"';

                if ($WPTranslation[$lastItemId]['type'] === 'url') {
                    $appendContent .= 'href="' . $WPTranslation[ $lastItemId ]['value'] . '">';
                } else {
                    $appendContent .= '>' . $WPTranslation[ $lastItemId ]['value'];
                }

                $appendContent .= '</' . $tag . '>';
            }
        }

        $newContent = str_replace('</body>', $appendContent . '</body>', $content);

        $response->setContent($newContent);
    }

    /**
     * Insert back all translated strings
     *
     * @return void
     */
    protected static function scriptDataInsertion()
    {
        if (empty(self::$WPOriginalStrings)) {
            return;
        }

        $response       = Response::getInstance();
        $content        = $response->getContent();

        $WPScriptsUpdated = [];

        preg_match_all('/<(div|a) data-linguise-js-vars="([^"]*)"(?: data-linguise-js-vars-[0-9]+="[^"]*")*?(?: href="([^"]*)")?>(.*?)<\/\1>/s', $content, $tagMatches, PREG_SET_ORDER);
        foreach ($tagMatches as $tagMatch) {
            preg_match_all('/(?:data-linguise-js-vars-([0-9]+)="(.*?)")+?/s', $tagMatch[0], $matches, PREG_SET_ORDER);

            $tag = $tagMatch[1];
            $scriptId = $tagMatch[2];
            $url = $tagMatch[3];
            $translation = $tagMatch[4];

            if (empty(self::$WPOriginalStrings[$scriptId]) || empty(self::$WPJsonData[$scriptId])) {
                // Something is wrong
                continue;
            }

            $WPJsonDataVariable = &self::$WPJsonData[$scriptId];
            foreach ($matches as $match) {
                $variableName = $match[2];

                if (is_object($WPJsonDataVariable)) {
                    $WPJsonDataVariable = &$WPJsonDataVariable->{$variableName};
                } elseif (is_array($WPJsonDataVariable)) {
                    $WPJsonDataVariable = &$WPJsonDataVariable[$variableName];
                }
            }

            if ($tag === 'a') {
                $WPJsonDataVariable = $url;
            } else {
                $WPJsonDataVariable = $translation;
            }

            // Save the fact that this script should be updated on the actual content
            if (!in_array($scriptId, $WPScriptsUpdated)) {
                $WPScriptsUpdated[] = $scriptId;
            }
        }

        // We re json_encode data that were json_decoded previously
        foreach (self::$WPJsonDecoded as $WPJsonDecodedName => &$WPJsonDecodedValue) {
            $dataToEncode = &self::$WPJsonData[$WPJsonDecodedName];
            foreach ($WPJsonDecodedValue as $data) {
                if (is_array($dataToEncode)) {
                    $dataToEncode = &$dataToEncode[$data];
                } elseif (is_object($dataToEncode)) {
                    $dataToEncode = &$dataToEncode->{$data};
                }
            }
            $dataToEncode = json_encode($dataToEncode);
        }

        foreach ($WPScriptsUpdated as $scriptName) {
            $id       = preg_quote($scriptName . '-js-extra', '/');
            $variable = preg_quote(str_replace('-', '_', $scriptName) . '_params', '/');
            $replacement = preg_replace('/(<script id=[\'"]' . $id . '[\'"]>\s*var ' . $variable . ' = )(.*?)(;\s*<\/script>)/s', '$1' . json_encode(self::$WPJsonData[$scriptName]) . '$3', $content, -1, $count);
            if ($count) {
                $content = $replacement;
            }
        }

        // Remove all the Linguise content we added
        $replacement = preg_replace('/<(div|a) data-linguise-js-vars="[^"]*"(?: data-linguise-js-vars-[0-9]+="[^"]*")*?(?: href="[^"]*")?>.*?<\/\1>/s', '', $content, - 1, $count);
        if ($count) {
            $content = $replacement;
        }

        $response->setContent($content);
    }

    /**
     * Extract variables from javascript json variables
     *
     * @param string $WPVarName Variable name to look for
     * @param string $content   Html content to look into
     *
     * @return mixed|false
     */
    protected static function extractWPVars($WPVarName, $content)
    {
        $id       = preg_quote($WPVarName . '-js-extra', '/');
        $variable = preg_quote(str_replace('-', '_', $WPVarName) . '_params', '/');
        preg_match('/<script id=[\'"]' . $id . '[\'"]>\s*var ' . $variable . ' = (.*?);\s*<\/script>/s', $content, $matches);
        if (empty($matches)) {
            return false;
        }

        return json_decode($matches[1]);
    }

    /**
     * Extract recursively variables from a passed variable
     *
     * @param string $baseVariable The name of the WordPress script id
     * @param object $lookupValues The values to look for in the actual data passed, these values need to be translated
     * @param array  $actualData   Actual data to look values
     * @param array  $parents      Name of parents of this var
     * @param array  $extracted    Array of already extracted values
     *
     * @return void
     */
    protected static function extractWordsToTranslate($baseVariable, $lookupValues, $actualData, $parents, &$extracted)
    {
        foreach ($lookupValues as $lookupValuesKey => $lookupValuesValue) {
            if (is_array($lookupValuesValue)) {
                if (is_object($actualData) && !empty($actualData->{$lookupValuesKey})) {
                    $data = $actualData->{$lookupValuesKey};
                } elseif (is_array($actualData) && !empty($actualData[$lookupValuesKey])) {
                    $data = $actualData[$lookupValuesKey];
                } else {
                    continue;
                }

                if (gettype($data) === 'string') {
                    $json                               = json_decode($data);
                    self::$WPJsonDecoded[$baseVariable] = array_merge($parents, [$lookupValuesKey]);
                } else {
                    $json = $data;
                }

                if (is_object($actualData)) {
                    $actualData->{$lookupValuesKey} = $json;
                    $jsonContentPassed = $actualData->{$lookupValuesKey};
                } elseif (is_array($actualData)) {
                    $actualData[$lookupValuesKey] = $json;
                    $jsonContentPassed = $actualData[$lookupValuesKey];
                } else {
                    continue;
                }

                self::extractWordsToTranslate($baseVariable, $lookupValuesValue, $jsonContentPassed, array_merge($parents, [$lookupValuesKey]), $extracted);
            } elseif (is_object($lookupValuesValue)) {
                if (is_array($actualData)) {
                    $value = $actualData[$lookupValuesValue->label];
                } elseif (is_object($actualData) && property_exists($actualData, $lookupValuesValue->label)) {
                    $value = $actualData->{$lookupValuesValue->label};
                } else {
                    continue;
                }
                $extracted[] = array_merge($parents, [
                    [
                        'type' => $lookupValuesValue->type,
                        'label' => $lookupValuesValue->label,
                        'value' => $value,
                    ]
                ]);
            }
        }
    }
}
