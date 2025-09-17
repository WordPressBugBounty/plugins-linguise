<?php

namespace Linguise\WordPress;

use Linguise\WordPress\Helper;

defined('ABSPATH') || die('');

/**
 * The main switcher handler for the frontend part.
 */
class LinguiseSwitcher
{
    /**
     * The configuration for the switcher.
     *
     * @var array
     */
    private $config;

    /**
     * The host for translation, defaults to translate.linguise.com
     *
     * @var string
     */
    private $translate_host = 'https://translate.linguise.com';

    /**
     * Indicates if the switcher is loaded.
     *
     * @var boolean
     */
    private $is_loaded = false;

    /**
     * Initialize the switcher
     *
     * @param array       $options        The options to initialize the switcher with.
     * @param string      $base_url       The base URL for the switcher.
     * @param string|null $translate_host The host for translation, defaults to translate.linguise.com
     *
     * @return void
     */
    public function __construct($options, $base_url, $translate_host = \null)
    {
        $languages_names = Helper::getLanguagesInfos();

        // Get from module parameters the enable languages
        $languages_enabled_param = isset($options['enabled_languages']) ? $options['enabled_languages'] : array();
        // Get the default language
        $default_language = isset($options['default_language']) ? $options['default_language'] : 'en';
        $language_name_display = isset($options['language_name_display']) ? $options['language_name_display'] : 'en';

        // Generate language list with default language as first item
        if ($language_name_display === 'en') {
            $language_list = array($default_language => $languages_names->{$default_language}->name);
        } else {
            $language_list = array($default_language => $languages_names->{$default_language}->original_name);
        }

        foreach ($languages_enabled_param as $language) {
            if ($language === $default_language) {
                continue;
            }

            if (!isset($languages_names->{$language})) {
                continue;
            }

            if ($language_name_display === 'en') {
                $language_list[$language] = $languages_names->{$language}->name;
            } else {
                $language_list[$language] = $languages_names->{$language}->original_name;
            }
        }

        if (preg_match('@(\/+)$@', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), $matches) && !empty($matches[1])) {
            $trailing_slashes = $matches[1];
        } else {
            $trailing_slashes = '';
        }

        linguiseSwitchMainSite();
        $site_url = linguiseGetSite();
        linguiseRestoreMultisite();

        $base = rtrim(self::forceRelativeUrl($site_url), '/');
        $config = array_merge(
            [
                'languages' => $language_list,
                'base' => $base,
                'base_url' => $base_url,
                'original_path' => rtrim(substr(rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'), strlen($base)), '/'),
                'trailing_slashes' => $trailing_slashes,
            ],
            $options,
            [
                'default_language' => $default_language,
            ]
        );

        // We do color mixing for shadow
        $flag_shadow_color = $config['flag_shadow_color'] ?? '#000000';
        $flag_hover_shadow_color = $config['flag_hover_shadow_color'] ?? '#000000';
        $flag_shadow_color_alpha = $config['flag_shadow_color_alpha'] ?? 1.0;
        $flag_hover_shadow_color_alpha = $config['flag_hover_shadow_color_alpha'] ?? 1.0;

        $config['flag_shadow_color'] = Helper::mixColorAlpha($flag_shadow_color, $flag_shadow_color_alpha);
        $config['flag_hover_shadow_color'] = Helper::mixColorAlpha($flag_hover_shadow_color, $flag_hover_shadow_color_alpha);

        $this->config = $config;
        if (!empty($translate_host)) {
            // set new host
            $this->translate_host = $translate_host;
        }
    }

    /**
     * Force the URL to be relative.
     *
     * @param string $url The URL to force to be relative.
     *
     * @return string|null The relative URL.
     */
    private static function forceRelativeUrl($url)
    {
        return preg_replace('/^(http)?s?:?\/\/[^\/]*(\/?.*)$/i', '$2', '' . $url);
    }

    /**
     * Check if the switcher assets is loaded.
     *
     * @return boolean True if the switcher is loaded, false otherwise.
     */
    public function isAssetsLoaded()
    {
        return $this->is_loaded;
    }

    /**
     * Load the scripts and styles for the switcher.
     *
     * @return void
     */
    public function loadScripts()
    {
        if ($this->is_loaded) {
            return;
        }

        // do not load translate script for bricks edit page
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- View request, no action
        $is_wp_bricks = array_key_exists('bricks', $_GET) ? $_GET['bricks'] : false;
        if (!$is_wp_bricks) {
            wp_enqueue_script('linguise_switcher', Helper::getScriptUrl('/assets/js/front.bundle.js'), array(), LINGUISE_VERSION);
            wp_enqueue_style('linguise_switcher', Helper::getScriptUrl('/assets/css/front.bundle.css'), array(), LINGUISE_VERSION);

            // make copy of $config
            $config_local = json_decode(json_encode($this->config), true);

            if ($this->config['dynamic_translations']['enabled'] === 1) {
                $config_local['translate_host'] = $this->translate_host;
            }

            // Remove content we don't want to share
            // FIXME: we should remove all config which is not actually used
            unset($config_local['token']);
            unset($config_local['expert_mode']);

            wp_localize_script('linguise_switcher', 'linguise_configs', array('vars' => array('configs' => $config_local)));
            $this->is_loaded = true;
        }
    }

    /**
     * Render the custom CSS for the switcher.
     *
     * @return string
     */
    public function makeCustomCSS()
    {
        $custom_css = '';
        if ($this->config['flag_shape'] === 'rounded') {
            $custom_css .= '
                    .linguise_switcher span.linguise_language_icon, #linguise_popup li span.linguise_flags {
                            width: ' . (int) $this->config['flag_width'] . 'px;
                            height: ' . (int) $this->config['flag_width'] . 'px;
                    }';
        } else {
            $custom_css .= '
                    .linguise_switcher span.linguise_language_icon, #linguise_popup li span.linguise_flags {
                            width: ' . (int) $this->config['flag_width'] . 'px;
                            height: ' . ((int) $this->config['flag_width'] * 2 / 3) . 'px;
                    }';
        }

        $custom_css .= '.lccaret svg {fill: '. esc_html($this->config['language_name_color']) .' !important}';
        $custom_css .= '.linguise_lang_name {color: '. esc_html($this->config['language_name_color']) .' !important}';
        $custom_css .= '.popup_linguise_lang_name {color: '. esc_html($this->config['popup_language_name_color'] ?? $this->config['language_name_color']) .' !important}';
        $custom_css .= '.linguise_current_lang:hover .lccaret svg {fill: '. esc_html($this->config['language_name_hover_color']) .' !important}';
        $custom_css .= '.linguise_lang_name:hover, .linguise_current_lang:hover .linguise_lang_name, .linguise-lang-item:hover .linguise_lang_name {color: '. esc_html($this->config['language_name_hover_color']) .' !important}';
        $custom_css .= '.popup_linguise_lang_name:hover, .linguise-lang-item:hover .popup_linguise_lang_name {color: '. esc_html($this->config['popup_language_name_hover_color'] ?? $this->config['language_name_hover_color']) .' !important}';
        $custom_css .= '.linguise_switcher span.linguise_language_icon, #linguise_popup li .linguise_flags {box-shadow: '. (int)$this->config['flag_shadow_h'] .'px '. (int)$this->config['flag_shadow_v'] .'px '. (int)$this->config['flag_shadow_blur'] .'px '. (int)$this->config['flag_shadow_spread'] .'px '. esc_html($this->config['flag_shadow_color']) .' !important}';
        $custom_css .= '.linguise_switcher span.linguise_language_icon:hover, #linguise_popup li .linguise_flags:hover {box-shadow: '. (int)$this->config['flag_hover_shadow_h'] .'px '. (int)$this->config['flag_hover_shadow_v'] .'px '. (int)$this->config['flag_hover_shadow_blur'] .'px '. (int)$this->config['flag_hover_shadow_spread'] .'px '. esc_html($this->config['flag_hover_shadow_color']) .' !important}';

        if ($this->config['flag_shape'] === 'rectangular') {
            $custom_css .= '#linguise_popup.linguise_flag_rectangular ul li .linguise_flags, .linguise_switcher.linguise_flag_rectangular span.linguise_language_icon {border-radius: ' . (int) $this->config['flag_border_radius'] . 'px}';
        }

        if (!empty($this->config['custom_css'])) {
            $custom_css .= esc_html($this->config['custom_css']);
        }

        return $custom_css;
    }

    /**
     * Render the actual linguise switcher
     *
     * @param boolean $pin Should we pin this in-place or follow the position of the flag that is defined.
     *
     * @return string
     */
    public function renderShortcode($pin = \false)
    {
        $this->loadScripts();
        $custom_css = $this->makeCustomCSS();

        wp_add_inline_style('linguise_switcher', $custom_css);

        if ($pin) {
            return '<div class="linguise_switcher_root linguise_menu_root"></div>';
        }

        return '<div class="linguise_switcher_root"></div>';
    }

    /**
     * Actual starting function for the switcher.
     *
     * @return void
     */
    public function start()
    {
        if (!empty($this->config['alternate_link'])) {
            linguiseSwitchMainSite();
            $site_url = linguiseGetSite();
            linguiseRestoreMultisite();

            $scheme = !empty($_SERVER['HTTPS']) ? 'https' : 'http';

            $host = parse_url($site_url, PHP_URL_HOST);
            $path = $this->config['original_path'];
            $query = parse_url($site_url, PHP_URL_QUERY);
            $alternates = $this->config['languages'];
            $alternates['x-default'] = 'x-default';

            $head_content = [];
            global $wpdb;

            $originalCharset = $wpdb->charset;
            if ($wpdb->charset !== 'utf8mb4') {
                $wpdb->set_charset($wpdb->__get('dbh'), 'utf8mb4');
            }
            foreach ($alternates as $language_code => $language_name) {
                $url_translation = null;
                if ($path) {
                    $db_query = $wpdb->prepare('SELECT * FROM ' . $wpdb->prefix . 'linguise_urls WHERE hash_source=%s AND language=%s', md5($path), $language_code);
                    $url_translation = $wpdb->get_row($db_query);
                }

                if (!is_wp_error($url_translation) && !empty($url_translation)) {
                    /**
                     * Hotfix for some of urls with non latin char become invalid (HTML entity–encoded Unicode characters)
                     * And causing some of SEO tool complaining, Semrush mark hreflang as broken.
                     * TODO: can be removed if core translate the URL properly
                     */
                    $encoded_translation = implode(
                        '/',
                        array_map('rawurlencode', explode('/', trim($url_translation->translation, '/')))
                    );

                    $url = $scheme . '://' . $host . $this->config['base'] . '/' . $encoded_translation . $this->config['trailing_slashes'] . $query;
                } else {
                    $url = $scheme . '://' . $host . $this->config['base'] . (in_array($language_code, array($this->config['default_language'], 'x-default')) ? '' : '/' . $language_code) . $path . $this->config['trailing_slashes'] . $query;
                }

                $head_content[] = '<link rel="alternate" hreflang="' . $language_code . '" href="' . $url . '" />';
            }
            if ($originalCharset !== 'utf8mb4') {
                $wpdb->set_charset($wpdb->__get('dbh'), $originalCharset);
            }

            if (!empty($head_content)) {
                add_action('wp_head', function ($a) use ($head_content) {
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped already
                    echo implode("\n", $head_content);
                });
            }
        }

        add_filter('wp_get_nav_menu_items', [$this, 'hookNavMenuItems'], 20, 1);
        add_action('wp_footer', [$this, 'hookFooter'], 10, 1);
        add_shortcode('linguise', [$this, 'hookShortcode']);
    }

    /**
     * Simple code to render the shortcode with pinned position.
     *
     * @return string
     */
    public function hookShortcode()
    {
        return $this->renderShortcode(true);
    }

    /**
     * Hook for footer to add the linguise switcher.
     *
     * @return void
     */
    public function hookFooter()
    {
        // Footer is an automatic flag switcher that will call the shortcode of linguise
        if (!$this->config['token'] || $this->config['add_flag_automatically'] !== 1) {
            return;
        }

        echo $this->renderShortcode(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this should be secure enough
    }

    /**
     * Hook for nav menu items to add the linguise switcher.
     *
     * @param array $items The menu items.
     *
     * @return array
     */
    public function hookNavMenuItems($items)
    {
        if (doing_action('customize_register')) {
            return $items;
        }

        $is_found = false;
        $new_items = [];
        $offset = 0;

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- View request, no action
        $current_language = (!empty($_GET['language']) && in_array($_GET['language'], array_keys($this->config['languages']))) ? $_GET['language'] : $this->config['default_language'];
        foreach ($items as $item) {
            $options = get_post_meta($item->ID, '_linguise_menu_item', true);
            if ($options) {
                // parent item for dropdown
                $item->title = (!empty($this->config['enable_language_name'])) ? $this->config['languages'][$current_language] : '';
                $item->attr_title = '';
                $item->url = '#';
                $item->classes = array('linguise_switcher_root linguise_menu_root linguise_parent_menu_item');

                if ($this->config['flag_shape'] === 'rounded') {
                    $item->classes[] = 'linguise_flag_rounded';
                } else {
                    $item->classes[] = 'linguise_flag_rectangular';
                }

                if ($this->config['flag_display_type'] === 'side_by_side') {
                    $item->classes[] = 'linguise_parent_menu_item_side_by_side';
                }

                if ($this->config['flag_display_type'] === 'side_by_side') {
                    $item->classes[] = 'linguise_parent_menu_item_side_by_side';
                }

                $new_items[] = $item;
                $is_found = true;
            } else {
                $item->menu_order += $offset;
                $new_items[] = $item;
            }
        }

        if ($is_found) {
            $custom_css = $this->makeCustomCSS();
            $this->loadScripts();
            wp_add_inline_style('linguise_switcher', $custom_css);
        }

        return $new_items;
    }
}
