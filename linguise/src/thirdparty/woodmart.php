<?php

namespace Linguise\WordPress\Integrations;

use Linguise\WordPress\Helper as WPHelper;

defined('ABSPATH') || die('');

/**
 * Woodmart Theme integration class
 *
 * Adjustment to some parameters
 */
class WoodmartThemeIntegration extends LinguiseBaseIntegrations
{
    /**
     * Plugin name
     *
     * @var string
     */
    public static $name = 'Woodmart Theme';

    /**
     * A collection of fragment overrides that we want to translate
     *
     * @var array
     */
    protected static $fragment_overrides = [
        [
            'name' => 'woodmart-theme',
            'match' => 'var woodmart_settings = (.*?);',
            'replacement' => 'var woodmart_settings = $$JSON_DATA$$;',
        ],
    ];

    /**
     * Decides if the integration should be loaded.
     *
     * @codeCoverageIgnore
     *
     * @return boolean
     */
    public function shouldLoad()
    {
        return $this->isCurrentTheme('Woodmart');
    }

    /**
     * Load the integration
     *
     * @codeCoverageIgnore
     *
     * @return void
     */
    public function init()
    {
        $merged_defaults = [];

        $regex_key_disallowed = [
            'add_to_cart_action.*',
            'age_verify.*',
            'ajax_(?!url)(\w+)',
            'carousel_breakpoints\..*',
            'comment_images_upload_mimes\..*',
            'tooltip_\w+_selector',
        ];

        foreach ($regex_key_disallowed as $regex_key) {
            $merged_defaults[] = [
                'key' => $regex_key,
                'mode' => 'regex_full',
                'kind' => 'deny',
            ];
        }

        $exact_key_disallowed = [
            'added_popup',
            'base_hover_mobile_click',
            'cart_redirect_after_add',
            'categories_toggle',
            'collapse_footer_widgets',
            'compare_by_category',
            'compare_save_button_state',
            'countdown_timezone',
            'whb_header_clone',
            'vimeo_library_url',
            'theme_dir',
            'wishlist_page_nonce',
            'photoswipe_template',
        ];

        foreach ($exact_key_disallowed as $exact_key) {
            $merged_defaults[] = [
                'key' => $exact_key,
                'mode' => 'path',
                'kind' => 'deny',
            ];
        }

        self::$fragment_keys = $merged_defaults;
    }

    /**
     * Unload the integration
     *
     * @codeCoverageIgnore
     *
     * @return void
     */
    public function destroy()
    {
        /* stub */
    }
}
