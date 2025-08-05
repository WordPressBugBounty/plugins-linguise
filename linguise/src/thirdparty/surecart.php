<?php

namespace Linguise\WordPress\Integrations;

use Linguise\WordPress\FragmentHandler;
use Linguise\WordPress\Helper as WPHelper;

defined('ABSPATH') || die('');

/**
 * Integration for Surecart
 */
class SurecartIntegration extends LinguiseBaseIntegrations
{
    /**
     * Plugin name
     *
     * @var string
     */
    public static $name = 'SureCart';

    /**
     * A collection of fragment overrides that we want to translate
     *
     * @var array
     */
    protected static $fragment_overrides = [
        [
            'name' => 'surecart-store-data',
            'key' => 'sc-store-data',
            'mode' => 'app_json',
        ],
        [
            'name' => 'surecart-checkout-variants',
            'match' => 'sc-checkout-product-price-variant-selector(.+)component\.product = (.+);(.*)}\)\(\);',
            'replacement' => '$1component.product = $$JSON_DATA$$;$3})();',
            'position' => 2,
            'strict' => true,
        ],
    ];

    /**
     * A collection of HTML attributes that you want to translate
     *
     * @var array
     */
    protected static $fragment_attributes = [
        [
            'name' => 'surecart-customer-email-track',
            'key' => 'tracking-confirmation-message',
            'mode' => 'string',
            'matchers' => [
                [
                    'type' => 'tag',
                    'key' => 'sc-customer-email'
                ]
            ]
        ],
        [
            'name' => 'surecart-customer-email-label',
            'key' => 'label',
            'mode' => 'string',
            'matchers' => [
                [
                    'type' => 'tag',
                    'key' => 'sc-customer-email'
                ]
            ]
        ],
        [
            'name' => 'surecart-customer-name-label',
            'key' => 'label',
            'mode' => 'string',
            'matchers' => [
                [
                    'type' => 'tag',
                    'key' => 'sc-customer-name'
                ]
            ]
        ],
        [
            'name' => 'surecart-customer-name-placeholder',
            'key' => 'placeholder',
            'mode' => 'string',
            'matchers' => [
                [
                    'type' => 'tag',
                    'key' => 'sc-customer-name'
                ]
            ]
        ],
        [
            'name' => 'surecart-order-submit-secure-label',
            'key' => 'secure-notice-text',
            'mode' => 'string',
            'matchers' => [
                [
                    'type' => 'tag',
                    'key' => 'sc-order-submit'
                ]
            ]
        ],
        [
            'name' => 'surecart-order-coupon-form-placeholder',
            'key' => 'placeholder',
            'mode' => 'string',
            'matchers' => [
                [
                    'type' => 'tag',
                    'key' => 'sc-order-coupon-form'
                ]
            ]
        ],
        [
            'name' => 'surecart-order-summary-sum-text',
            'key' => 'order-summary-text',
            'mode' => 'string',
            'matchers' => [
                [
                    'type' => 'tag',
                    'key' => 'sc-order-summary'
                ]
            ]
        ],
        [
            'name' => 'surecart-order-summary-inv-sum-text',
            'key' => 'invoice-summary-text',
            'mode' => 'string',
            'matchers' => [
                [
                    'type' => 'tag',
                    'key' => 'sc-order-summary'
                ]
            ]
        ],
        [
            'name' => 'surecart-line-trial-label',
            'key' => 'label',
            'mode' => 'string',
            'matchers' => [
                [
                    'type' => 'tag',
                    'key' => 'sc-line-item-trial'
                ]
            ]
        ],
        [
            'name' => 'surecart-buy-button-context',
            'key' => 'data-wp-context',
            'matchers' => [
                [
                    'key' => 'data-sc-block-id',
                    'value' => 'product-buy-button',
                    'type' => 'attribute',
                ]
            ]
        ]
    ];

    /**
     * A list of WP-REST ajax methods that we want to be intercepted.
     *
     * @var string[]
     */
    protected static $wp_rest_ajax_intercept = [
        'surecart/v1/line_items',
        'surecart/v1/checkouts',
    ];

    /**
     * Initialize the integration.
     * Sets up fragment keys to be translated for Surecart
     */
    public function __construct()
    {
        // For WP REST API related
        $path_key_match = [
            'name',
            'description',
            'metadata.page_url',
            'human_discount',
            'human_discount_with_duration',
            'discount.redeemable_display_status',
            'purchasable_status_display',
            'permalink',
            'page_title',
            'meta_description',
        ];
        $product_items_regex = [
            '^variant_options\.\d+$',
            '^variant_option_names\.\d+$',
            '^variant\.(option_\d+|option_names\.\d+)$',
            '^variants\.data\.\d+\.(option_\d+|option_names\.\d+)',
            '^variant_options\.data\.(name|values\.\d+)$',
            '^(active_prices|initial_price)\.\d+\.(name|display_amount|interval_text|interval_count_text|trial_text)',
        ];

        $line_items_regex = [
            '^price\.name',
            '^price\.product\.(name|description|permalink|checkout_permalink|page_title|meta_description)',
            '^price\.product\.post\.(post_title|post_excerpt|guid)',
        ];

        foreach ($product_items_regex as $product_key) {
            $line_items_regex[] = '^price\.product\.' . str_replace('^', '', $product_key);
        }

        $checkout_items_regex = [
            '^shipping_choices\.data\.\d+\.shipping_method\.(name|description)',
        ];
        foreach ($product_items_regex as $product_item_key) {
            $checkout_items_regex[] = '^line_items\.data\.\d+\.' . str_replace('^', '', $product_item_key);
        }
        foreach ($line_items_regex as $line_item_key) {
            $checkout_items_regex[] = '^line_items\.data\.\d+\.' . str_replace('^', '', $line_item_key);
        }

        $regex_key_match = array_merge($product_items_regex, $line_items_regex, $checkout_items_regex);
        foreach ($checkout_items_regex as $checkout_key) {
            $regex_key_match[] = '^checkout\.' . str_replace('^', '', $checkout_key);
        }

        $fragment_keys = [];

        foreach ($path_key_match as $path_key) {
            $fragment_keys[] = [
                'key' => $path_key,
                'mode' => 'path',
                'kind' => 'allow',
            ];
            $fragment_keys[] = [
                // Recursive
                'key' => 'checkout.' . $path_key,
                'mode' => 'path',
                'kind' => 'allow',
            ];
        }
        foreach ($regex_key_match as $regex_key) {
            $fragment_keys[] = [
                'key' => $regex_key,
                'mode' => 'regex_full',
                'kind' => 'allow',
            ];
        }

        // Disallowed keys
        $regex_full_key_disallowed = [
            '^checkout\.taxProtocol\.address\..*',
            '^user\.(email|name)',
        ];

        foreach ($regex_full_key_disallowed as $regex_full_key) {
            $fragment_keys[] = [
                'key' => $regex_full_key,
                'mode' => 'regex_full',
                'kind' => 'deny',
            ];
        }

        $img_path = [
            '^image\.src$',
            '^line_items\.data\.\d+\.image\.src$',
            '^checkout\.line_items\.data\.\d+\.image\.src$',
        ];
        foreach ($img_path as $path) {
            $fragment_keys[] = [
                'key' => $path,
                'mode' => 'regex_full',
                'kind' => 'allow',
                'cast' => 'media-img'
            ];
        }
        $imgset_path = [
            '^image\.srcset$',
            '^line_items\.data\.\d+\.image\.srcset$',
            '^checkout\.line_items\.data\.\d+\.image\.srcset$',
        ];
        foreach ($imgset_path as $path) {
            $fragment_keys[] = [
                'key' => $path,
                'mode' => 'regex_full',
                'kind' => 'allow',
                'cast' => 'media-imgset'
            ];
        }

        self::$fragment_keys = $fragment_keys;
    }

    /**
     * Determines if the integration should be loaded.
     *
     * @return boolean
     */
    public function shouldLoad()
    {
        return is_plugin_active('surecart/surecart.php');
    }

    /**
     * Load the integration
     *
     * @return void
     */
    public function init()
    {
        // add_filter('render_block_surecart/product-buy-button', [$this, 'translateBlockContent'], 1000, 3);
        // add_filter('render_block_surecart/product-page', [$this, 'translateBlockContent'], 1000, 3);
        add_filter('render_block_surecart/slide-out-cart-items', [$this, 'markClassBlockContent'], 1000, 3);
        add_filter('linguise_after_attribute_translation', [$this, 'hookAfterAttributeTranslation'], 100, 1);
    }

    /**
     * Unload the integration
     *
     * @return void
     */
    public function destroy()
    {
        // remove_filter('render_block_surecart/product-buy-button', [$this, 'translateBlockContent'], 1000, 3);
        // remove_filter('render_block_surecart/product-page', [$this, 'translateBlockContent'], 1000, 3);
        remove_filter('render_block_surecart/slide-out-cart-items', [$this, 'markClassBlockContent'], 1000, 3);
        remove_filter('linguise_after_attribute_translation', [$this, 'hookAfterAttributeTranslation'], 100);
    }

    /**
     * Translate surecart block
     *
     * @param string    $block_content Block content.
     * @param array     $block         Block data.
     * @param \WP_Block $instance      Block instance.
     *
     * @return string
     */
    public function translateBlockContent($block_content, $block, $instance)
    {
        $language_meta = WPHelper::getLanguage();
        if (!$language_meta) {
            $language_meta = WPHelper::getLanguageFromReferer();
        }

        if (!$language_meta) {
            return $block_content;
        }

        $block_name = 'wp-block_' . $instance->name;

        // Find all data-wp-context data
        preg_match_all('/data-wp-context=\'{(.*)}\'/', $block_content, $matches, PREG_SET_ORDER, 0);

        // We then extract the context
        $contexts = [];
        foreach ($matches as $match) {
            $contexts[] = [
                'context' => $match[0],
                'data' => json_decode('{' . $match[1] . '}'),
            ];
        }

        if (empty($contexts)) {
            return $block_content;
        }

        $html_content = '<html><head></head><body>';
        $empty_data = true;
        $contexts_length = count($contexts);
        for ($index = 0; $index < $contexts_length; $index++) {
            $context = $contexts[$index];
            if (empty($context['data'])) {
                continue;
            }
            $fragments = FragmentHandler::collectFragmentFromJson($context['data']);

            if (empty($fragments)) {
                continue;
            }

            $prefix = 'index-' . $index;
            $html_fragments = FragmentHandler::intoHTMLFragments($block_name, $prefix, [
                'mode' => 'auto',
                'fragments' => $fragments
            ]);

            $html_content .= $html_fragments;
            $empty_data = false;
        }
        $html_content .= '</body></html>';

        if ($empty_data) {
            return $block_content;
        }

        $translated_content = $this->translateFragments($html_content, $language_meta, '/wp-block/' . $block_name);

        if (empty($translated_content)) {
            // We failed to translate
            return $block_content;
        }

        if (isset($translated_content->redirect)) {
            // Somehow we got this...?
            return $block_content;
        }

        $translated_fragments = FragmentHandler::intoJSONFragments($translated_content->content);
        if (empty($translated_fragments)) {
            return $block_content;
        }

        if (!isset($translated_fragments[$block_name])) {
            return $block_content;
        }

        $wp_block_fragments = $translated_fragments[$block_name];
        foreach ($wp_block_fragments as $key_prefix => $tl_json_frag) {
            // We replace data-wp-context according to the key-prefix
            // key-preifx is formatted "index-${index}"
            $index = (int)str_replace('index-', '', $key_prefix);
            // Find the context
            $context = $contexts[$index];
            if (empty($context)) {
                continue;
            }

            $tl_json_frag_list = $tl_json_frag['fragments'];
            if (empty($tl_json_frag_list)) {
                continue;
            }

            $replaced_content = FragmentHandler::applyTranslatedFragmentsForAuto($context['data'], $tl_json_frag_list);
            if ($replaced_content !== false) {
                $whole_content = 'data-wp-context=\'' . json_encode($replaced_content) . '\'';
                $block_content = str_replace($context['context'], $whole_content, $block_content);
            }
        }

        return $block_content;
    }

    /**
     * Mark the content of a block with a specific tag
     *
     * @param string    $block_content The content of the block
     * @param array     $block         The block data
     * @param \WP_Block $instance      The block instance
     *
     * @return string
     */
    public function markClassBlockContent($block_content, $block, $instance)
    {
        $repl_block_content = preg_replace_callback(
            '/class=(["\'])([^"\']+)\1/',
            function ($matches) {
                $classes_merge = $matches[2] . ' linguise-parent-ignore';

                return 'class=' . $matches[1] . $classes_merge . $matches[1];
            },
            $block_content
        );

        if (is_string($repl_block_content) && !empty($repl_block_content)) {
            return $repl_block_content;
        }
        return $block_content;
    }

    /**
     * Hook after attribute translation to avoid double translation later with dynamic content
     *
     * @param string $html_data The HTML data after attribute translation
     *
     * @return string
     */
    public function hookAfterAttributeTranslation($html_data)
    {
        $html_data = $this->markByTag($html_data, 'sc-customer-email');
        $html_data = $this->markByTag($html_data, 'sc-customer-name');
        $html_data = $this->markByTag($html_data, 'sc-order-submit');
        $html_data = $this->markByTag($html_data, 'sc-order-coupon-form');
        $html_data = $this->markByTag($html_data, 'sc-order-summary');
        $html_data = $this->markByTag($html_data, 'sc-line-item-trial');
        $html_data = $this->markByTag($html_data, 'sc-line-item-total');
        $html_data = $this->markByTag($html_data, 'sc-line-items');
        $html_data = $this->markByTag($html_data, 'sc-order-shipping-address');
        $html_data = $this->markByTag($html_data, 'sc-order-billing-address');
        return $html_data;
    }
}
