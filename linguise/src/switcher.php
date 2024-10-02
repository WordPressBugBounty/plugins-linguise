<?php

use Linguise\Vendor\Linguise\Script\Core\Configuration;
use Linguise\Vendor\Linguise\Script\Core\Request;

defined('ABSPATH') || die('');

if (is_admin()) {
    return;
}

add_action('init', function () use ($languages_names) {
    // load config
    linguiseInitializeConfiguration();

    $linguise_options = linguiseGetOptions();
    // Get from module parameters the enable languages
    $languages_enabled_param = isset($linguise_options['enabled_languages']) ? $linguise_options['enabled_languages'] : array();
    // Get the default language
    $default_language = isset($linguise_options['default_language']) ? $linguise_options['default_language'] : 'en';
    $language_name_display = isset($linguise_options['language_name_display']) ? $linguise_options['language_name_display'] : 'en';

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

    $base = rtrim(linguiseForceRelativeUrl(site_url()), '/');
    $config = array_merge(
        [
            'languages' => $language_list,
            'base' => $base,
            'original_path' => rtrim(substr(rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'), strlen($base)), '/'),
            'trailing_slashes' => $trailing_slashes,
        ],
        $linguise_options
    );
    

    // Remove content we don't want to share
    // fixme: we should remove all config which is not actually used
    unset($config['token']);

    if (!empty($linguise_options['alternate_link'])) {
        $scheme = !empty($_SERVER['HTTPS']) ? 'https' : 'http';
        $host = parse_url(site_url(), PHP_URL_HOST);
        $path = $config['original_path'];
        $query = parse_url(site_url(), PHP_URL_QUERY);
        $alternates = $language_list;
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
                $url = $scheme . '://' . $host . $base . htmlentities($url_translation->translation, ENT_COMPAT) . $trailing_slashes . $query;
            } else {
                $url = $scheme . '://' . $host . $base . (in_array($language_code, array($default_language, 'x-default')) ? '' : '/' . $language_code) . $path . $trailing_slashes . $query;
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

    add_filter('wp_get_nav_menu_items', function ($items) use ($language_list, $config) {
        if (doing_action('customize_register')) { // needed since WP 4.3, doing_action available since WP 3.9
            return $items;
        }

        $found = false;
        $new_items = array();
        $offset = 0;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- View request, no action
        $current_language = (!empty($_GET['language']) && in_array($_GET['language'], array_keys($config['languages']))) ? $_GET['language'] : $config['default_language'];
        foreach ($items as $item) {
            $options = get_post_meta($item->ID, '_linguise_menu_item', true);
            if ($options) {
                // parent item for dropdown
                $item->title = (!empty($config['enable_language_name'])) ? $language_list[$current_language] : '';
                $item->attr_title = '';
                $item->url = '#';
                if ($config['flag_shape'] === 'rounded') {
                    $item->classes = array('linguise_switcher linguise_flag_rounded linguise_parent_menu_item');
                } else {
                    $item->classes = array('linguise_switcher linguise_flag_rectangular linguise_parent_menu_item');
                }

                if ($config['flag_display_type'] === 'side_by_side') {
                    $item->classes[] = 'linguise_parent_menu_item_side_by_side';
                }

                if ($config['flag_display_type'] === 'side_by_side') {
                    $item->classes[] = 'linguise_parent_menu_item_side_by_side';
                }


                $new_items[] = $item;
                $found = true;
            } else {
                $item->menu_order += $offset;
                $new_items[] = $item;
            }
        }

        if ($found) {
            do_action('linguise_load_scripts', $config);

            $custom_css = linguiseRenderCustomCss($config);
            wp_add_inline_style('linguise_switcher', $custom_css);
        }

        return $new_items;
    }, 20);

    global $linguiseScripts;
    $linguiseScripts = false;

    add_action('linguise_load_scripts', function ($config) {
        global $linguise_scripts;
    
        if (!$linguise_scripts) {
            wp_enqueue_script('linguise_switcher', plugin_dir_url(dirname(__FILE__)) . '/assets/js/front.bundle.js', array(), LINGUISE_VERSION);
            wp_enqueue_style('linguise_switcher', plugin_dir_url(dirname(__FILE__)) . '/assets/css/front.bundle.css', array(), LINGUISE_VERSION);

            $tl_host = Configuration::getInstance()->get('host');
            $tl_port = (int)Configuration::getInstance()->get('port');
            $no_port_needed = $tl_port === 80 || $tl_port === 443;
            $tl_addr = 'http' . ($tl_port === 443 ? 's' : '') . '://' . $tl_host . ($no_port_needed ? '' : ':' . $tl_port);
            // Only add the translate host if dynamic translations are enabled.
            if ($config['dynamic_translations']['enabled'] === 1) {
                $config['translate_host'] = $tl_addr;
            }
            // Get base URL from Request instance to avoid issues with WordPress installations in subdirectories
            $request = Request::getInstance();
            $config['base_url'] = $request->getBaseUrl();
            unset($config['expert_mode']);
            wp_localize_script('linguise_switcher', 'linguise_configs', array('vars' => array('configs' => $config)));
            $linguise_scripts = true;
        }
    }, 1);

    /**
     * Create a shortcode to display linguise switcher
     */
    add_shortcode('linguise', function () use ($language_list, $config) {

        do_action('linguise_load_scripts', $config);

        $custom_css = '';
        if ($config['display_position'] !== 'no') {
            if ($config['flag_display_type'] === 'popup') {
                $custom_css .= '.linguise_switcher_popup{padding: 5px 10px}';
            }

            if ($config['flag_display_type'] === 'dropdown') {
                $custom_css .= '.linguise_switcher_dropdown ul{border-radius: 0}';
                $custom_css .= '.linguise_switcher_dropdown ul li{padding: 5px 10px; border-bottom: #eee 1px solid;}';
            }

            if ($config['display_position'] === 'top_left' || $config['display_position'] === 'top_left_no_scroll') {
                $custom_css .= '.linguise_switcher.linguise_switcher_not_menu{position: fixed; top: 20px; left: 20px;z-index: 99999; background: #fff; border-radius: 0;}';
                $custom_css .= '.linguise_switcher.linguise_switcher_not_menu li.linguise_current ul{left: 0; right: auto}';
            }

            if ($config['display_position'] === 'top_right' || $config['display_position'] === 'top_right_no_scroll') {
                $custom_css .= '.linguise_switcher.linguise_switcher_not_menu{position: fixed; top: 20px; right: 20px;z-index: 99999; background: #fff; border-radius: 0;}';
                $custom_css .= '.linguise_switcher.linguise_switcher_not_menu li.linguise_current ul{right: 0; left: auto}';
            }

            if ($config['display_position'] === 'bottom_left' || $config['display_position'] === 'bottom_left_no_scroll') {
                $custom_css .= '.linguise_switcher.linguise_switcher_not_menu{position: fixed; bottom: 0; left: 20px;z-index: 99999; background: #fff; border-radius: 0;}';
                $custom_css .= '.linguise_switcher.linguise_switcher_not_menu li.linguise_current ul{left: 0; right: auto; bottom: 100%; top: auto}';
                $custom_css .= '.linguise_switcher_dropdown.linguise_switcher_not_menu ul{box-shadow: none; border: #eee 1px solid}';
                $custom_css .= '.linguise_switcher_dropdown.linguise_switcher_not_menu .lccaret{transform: rotate(180deg);}';
            }

            if ($config['display_position'] === 'bottom_right' || $config['display_position'] === 'bottom_right_no_scroll') {
                $custom_css .= '.linguise_switcher.linguise_switcher_not_menu{position: fixed; bottom: 0; right: 20px;z-index: 99999; background: #fff; border-radius: 0;}';
                $custom_css .= '.linguise_switcher.linguise_switcher_not_menu li.linguise_current ul{right: 0; left: auto; bottom: 100%; top: auto}';
                $custom_css .= '.linguise_switcher_dropdown.linguise_switcher_not_menu ul{box-shadow: none; border: #eee 1px solid}';
                $custom_css .= '.linguise_switcher_dropdown.linguise_switcher_not_menu .lccaret{transform: rotate(180deg);}';
            }

            $custom_css .= '.linguise_switcher_dropdown.linguise_switcher_not_menu li.linguise_current div.linguise_current_lang{border: #eee 1px solid; padding: 5px 10px}';
            if (in_array($config['display_position'], array('top_left_no_scroll', 'top_right_no_scroll', 'bottom_left_no_scroll', 'bottom_right_no_scroll'))) {
                $custom_css .= '.linguise_switcher.linguise_switcher_not_menu{position: absolute}';
            }
        }

        $custom_css = linguiseRenderCustomCss($config, $custom_css);
        wp_add_inline_style('linguise_switcher', $custom_css);
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- View request, no action
        $current_language = (!empty($_GET['language']) && in_array($_GET['language'], array_keys($config['languages']))) ? $_GET['language'] : $config['default_language'];
        $current_language_flag = 'linguise_flag_';
        if ($current_language === 'en' && $config['flag_en_type'] === 'en-gb') {
            $current_language_flag .= 'en_gb';
        } else {
            $current_language_flag .= $current_language;
        }
        switch ($config['flag_display_type']) {
            case 'popup':
                $display = '<span class="linguise_flags ' . esc_attr($current_language_flag) . ' linguise_language_icon"></span>';
                $display .= '<span class="linguise_lang_name">' . esc_html($language_list[$current_language]) . '</span>';
                $switch = '<a 
            data-config="' . esc_attr(htmlspecialchars(json_encode($config), ENT_QUOTES, 'UTF-8')) . '" 
            class="linguise_switcher linguise_switcher_not_menu linguise_switcher_popup ' . ($config['flag_shape'] === 'rounded' ? 'linguise_flag_rounded' : 'linguise_flag_rectangular') . '"
            href="javascript:openLanguagePopUp();">' . $display . '
          </a>';
                break;
            case 'side_by_side':
                $switch = '<ul class="linguise_switcher linguise_switcher_not_menu linguise_switcher_side_by_side ' . ($config['flag_shape'] === 'rounded' ? 'linguise_flag_rounded' : 'linguise_flag_rectangular') . '" data-config="' . htmlspecialchars(json_encode($config), ENT_QUOTES, 'UTF-8') . '">';
                $switch .= '<li>';
                $switch .= '<a href="javascrip:void()">';
                if ((int) $config['enable_flag'] === 1) {
                    $switch .= '<span class="linguise_flags ' . esc_attr($current_language_flag) . ' linguise_language_icon"></span>';
                }

                if ((int) $config['enable_language_name'] === 1) {
                    $switch .= '<span class="linguise_lang_name">' . esc_html($language_list[$current_language]) . '</span>';
                }
                $switch .= '</a>';
                $switch .= '</li>';
                $switch .= '</ul>';
                break;
            case 'dropdown':
                $switch = '<ul class="linguise_switcher linguise_switcher_not_menu linguise_switcher_dropdown ' . ($config['flag_shape'] === 'rounded' ? 'linguise_flag_rounded' : 'linguise_flag_rectangular') . '" data-config="' . htmlspecialchars(json_encode($config), ENT_QUOTES, 'UTF-8') . '">';

                $switch .= '</ul>';
                break;
            default:
                $display = '<span class="linguise_flags ' . esc_attr($current_language_flag) . ' linguise_language_icon"></span>';
                $display .= '<span class="linguise_lang_name">' . esc_html($language_list[$current_language]) . '</span>';
                $switch = '<a 
            data-config="' . htmlspecialchars(json_encode($config), ENT_QUOTES, 'UTF-8') . '" 
            class="linguise_switcher linguise_switcher_not_menu linguise_switcher_popup"
            href="javascript:openLanguagePopUp();">' . $display . '
          </a>';
        }

        return $switch;
    });


    add_action('wp_footer', function () use ($linguise_options) {
        if (!$linguise_options['token'] || $linguise_options['add_flag_automatically'] !== 1) {
            return;
        }

        echo do_shortcode('[linguise]');
    });
});

/**
 * Render custom CSS
 *
 * @param array  $options    Options
 * @param string $custom_css Custom CSS string
 *
 * @return string
 */
function linguiseRenderCustomCss($options, $custom_css = '')
{
    if ($options['flag_shape'] === 'rounded') {
        $custom_css .= '
                .linguise_switcher span.linguise_language_icon, #linguise_popup li span.linguise_flags {
                        width: ' . (int) $options['flag_width'] . 'px;
                        height: ' . (int) $options['flag_width'] . 'px;
                }';
    } else {
        $custom_css .= '
                .linguise_switcher span.linguise_language_icon, #linguise_popup li span.linguise_flags {
                        width: ' . (int) $options['flag_width'] . 'px;
                        height: ' . ((int) $options['flag_width'] * 2 / 3) . 'px;
                }';
    }
    $custom_css .= '.lccaret svg {fill: '. esc_html($options['language_name_color']) .' !important}';
    $custom_css .= '.linguise_lang_name {color: '. esc_html($options['language_name_color']) .' !important}';
    $custom_css .= '.popup_linguise_lang_name {color: '. esc_html($options['popup_language_name_color'] ?? $options['language_name_color']) .' !important}';
    $custom_css .= '.linguise_current_lang:hover .lccaret svg {fill: '. esc_html($options['language_name_hover_color']) .' !important}';
    $custom_css .= '.linguise_lang_name:hover, .linguise_current_lang:hover .linguise_lang_name, .linguise-lang-item:hover .linguise_lang_name {color: '. esc_html($options['language_name_hover_color']) .' !important}';
    $custom_css .= '.popup_linguise_lang_name:hover, .linguise-lang-item:hover .popup_linguise_lang_name {color: '. esc_html($options['popup_language_name_hover_color'] ?? $options['language_name_hover_color']) .' !important}';
    $custom_css .= '.linguise_switcher span.linguise_language_icon, #linguise_popup li .linguise_flags {box-shadow: '. (int)$options['flag_shadow_h'] .'px '. (int)$options['flag_shadow_v'] .'px '. (int)$options['flag_shadow_blur'] .'px '. (int)$options['flag_shadow_spread'] .'px '. esc_html($options['flag_shadow_color']) .' !important}';
    $custom_css .= '.linguise_switcher span.linguise_language_icon:hover, #linguise_popup li .linguise_flags:hover {box-shadow: '. (int)$options['flag_hover_shadow_h'] .'px '. (int)$options['flag_hover_shadow_v'] .'px '. (int)$options['flag_hover_shadow_blur'] .'px '. (int)$options['flag_hover_shadow_spread'] .'px '. esc_html($options['flag_hover_shadow_color']) .' !important}';
    if ($options['flag_shape'] === 'rectangular') {
        $custom_css .= '#linguise_popup.linguise_flag_rectangular ul li .linguise_flags, .linguise_switcher.linguise_flag_rectangular span.linguise_language_icon {border-radius: ' . (int) $options['flag_border_radius'] . 'px}';
    }
    if (!empty($options['custom_css'])) {
        $custom_css .= esc_html($options['custom_css']);
    }

    return $custom_css;
}

/**
 * Linguise Force Relative Url
 *
 * @param string $url Url
 *
 * @return null|string|string[]
 */
function linguiseForceRelativeUrl($url)
{
    return preg_replace('/^(http)?s?:?\/\/[^\/]*(\/?.*)$/i', '$2', '' . $url);
}
