<?php

/**
 * Plugin Name: Linguise
 * Plugin URI: https://www.linguise.com/
 * Description: Linguise translation plugin
 * Version:2.0.20
 * Text Domain: linguise
 * Domain Path: /languages
 * Author: Linguise
 * Author URI: https://www.linguise.com/
 * License: GPL2
 */

use Linguise\Vendor\Linguise\Script\Core\Configuration;
use Linguise\Vendor\Linguise\Script\Core\Database;

defined('ABSPATH') || die('');

include_once('src/Helper.php');
include_once plugin_dir_path(__FILE__) . 'src' . DIRECTORY_SEPARATOR .'constants.php';

// Check plugin requirements
$curlInstalled = function_exists('curl_version');
$phpVersionOk = version_compare(PHP_VERSION, '7.0', '>=');
if (!$curlInstalled || !$phpVersionOk) {
    add_action('admin_init', function () {
        if (current_user_can('activate_plugins') && is_plugin_active(plugin_basename(__FILE__))) {
            deactivate_plugins(__FILE__);
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Internal function used
            unset($_GET['activate']);
        }
    });
    add_action('admin_notices', function () use ($curlInstalled, $phpVersionOk) {
        echo '<div class="error">';
        if (!$curlInstalled) {
            echo '<p><strong>Curl php extension is required</strong> to install Linguise, please make sure to install it before installing Linguise again.</p>';
        }
        if (!$phpVersionOk) {
            echo '<p><strong>PHP 7.0 is the minimal version required</strong> to install Linguise, please make sure to update your PHP version before installing Linguise.</p>';
        }
        echo '</div>';
    });
    // Do not load anything more
    return;
}

define('LINGUISE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('LINGUISE_PLUGIN_PATH', plugin_dir_path(__FILE__));

register_activation_hook(__FILE__, function () {
    if (!get_option('linguise_install_time', false)) {
        add_option('linguise_install_time', time());
    }
});

include_once(__DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'install.php');

/**
 * Get options
 *
 * @return array|mixed|void
 */
function linguiseGetOptions()
{
    $defaults = array(
        'token' => '',
        'default_language' => 'en',
        'enabled_languages' => array(),
        'flag_display_type' => 'popup',
        'display_position' => 'bottom_right',
        'enable_flag' => 1,
        'enable_language_name' => 1,
        'enable_language_short_name' => 0,
        'flag_shape' => 'rounded',
        'flag_en_type' => 'en-us',
        'flag_de_type' => 'de',
        'flag_es_type' => 'es',
        'flag_pt_type' => 'pt',
        'flag_tw_type' => 'zh-tw',
        'flag_border_radius' => 0,
        'flag_width' => 24,
        'browser_redirect' => 0,
        'ukraine_redirect' => 0,
        'language_name_display' => 'en',
        'pre_text' => '',
        'post_text' => '',
        'alternate_link' => 1,
        'add_flag_automatically' => 1,
        'custom_css' => '',
        'cache_enabled' => 1,
        'cache_max_size' => 200,
        'language_name_color' => '#222',
        'language_name_hover_color' => '#222',
        'popup_language_name_color' => '#222',
        'popup_language_name_hover_color' => '#222',
        'flag_shadow_h' => 2,
        'flag_shadow_v' => 2,
        'flag_shadow_blur' => 12,
        'flag_shadow_spread' => 0,
        'flag_shadow_color' => '#eee',
        'flag_hover_shadow_h' => 3,
        'flag_hover_shadow_v' => 3,
        'flag_hover_shadow_blur' => 6,
        'flag_hover_shadow_spread' => 0,
        'flag_hover_shadow_color' => '#bfbfbf',
        'search_translation' => 0,
        'debug' => false,
        'woocommerce_emails_translation' => 0,
        'dynamic_translations' => [
            'enabled' => 0,
            'public_key' => '',
        ],
        // empty array that will be filled with the expert mode options
        'expert_mode' => [],
    );
    $options = get_option('linguise_options');
    if (!empty($options) && is_array($options)) {
        $options = array_merge($defaults, $options);
    } else {
        $options = $defaults;
    }
    return $options;
}

/**
 * Load either local config or default config
 *
 * Used anywhere in the plugin that needs to load configuration before doing anything.
 *
 * @return void
 */
function linguiseInitializeConfiguration()
{
    if (!defined('LINGUISE_SCRIPT_TRANSLATION')) {
        define('LINGUISE_SCRIPT_TRANSLATION', true);
    }

    require_once(__DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

    // Explicitely set the CMS to WordPress
    Configuration::getInstance()->set('cms', 'wordpress');
    // Set base directory to Wordpress root
    Configuration::getInstance()->set('base_dir', realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..') . DIRECTORY_SEPARATOR);

    $host = array_key_exists('HTTP_HOST', $_SERVER) ? $_SERVER['HTTP_HOST'] : wp_parse_url(site_url(), PHP_URL_HOST);
    $token = Database::getInstance()->retrieveWordpressOption('token', $host);

    // Data folder in script folder
    $data_folder_in_script_folder = __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'linguise' . DIRECTORY_SEPARATOR . 'script-php' . DIRECTORY_SEPARATOR . md5('data' . $token);
    // Data folder in WP upload folder
    $linguise_upload_dir = wp_upload_dir()['basedir'] . DIRECTORY_SEPARATOR . 'linguise';
    $data_folder_in_upload_folder =  $linguise_upload_dir . DIRECTORY_SEPARATOR . md5('data' . $token);

    // By default, we will use the data folder in upload folder
    $data_folder = $data_folder_in_upload_folder;

    // Check if data has already been saved for this site
    if (!file_exists($data_folder_in_upload_folder) && file_exists($data_folder_in_script_folder)) {
        // Try to move the data to the new location
        $success = true;
        if (!file_exists($data_folder_in_upload_folder)) {
            //First create the folder
            $success = mkdir($linguise_upload_dir, 0755, true);
            file_put_contents($linguise_upload_dir . DIRECTORY_SEPARATOR . 'index.html', '');
        }
        if ($success) {
            // Then move the data
            $success = rename($data_folder_in_script_folder, $data_folder_in_upload_folder);
            file_put_contents($data_folder_in_upload_folder . DIRECTORY_SEPARATOR . 'index.html', '');
        }
        if (!$success) {
            // If the move failed, we will use the old location
            $data_folder = $data_folder_in_script_folder;
        }
    }

    if (!file_exists($data_folder)) {
        mkdir($data_folder, 0755, true);
        mkdir($data_folder . DIRECTORY_SEPARATOR . 'cache');
        file_put_contents($data_folder . DIRECTORY_SEPARATOR . '.htaccess', 'deny from all');
        file_put_contents($data_folder . DIRECTORY_SEPARATOR . 'index.html', '');
    }

    Configuration::getInstance()->set('data_dir', $data_folder);
    if (file_exists($data_folder . DIRECTORY_SEPARATOR . 'ConfigurationLocal.php')) {
        // By default, we load the local configuration from the data folder
        Configuration::getInstance()->loadFile($data_folder . DIRECTORY_SEPARATOR . 'ConfigurationLocal.php', true);
    } elseif (file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'ConfigurationLocal.php')) {
        // If there is a local configuration in the script folder, we load it
        Configuration::getInstance()->loadFile(__DIR__ . DIRECTORY_SEPARATOR . 'ConfigurationLocal.php', true);
    } else {
        // Else we load the default configuration
        Configuration::getInstance()->loadFile(__DIR__ . DIRECTORY_SEPARATOR . 'Configuration.php');
    }

    $cache_enabled = Database::getInstance()->retrieveWordpressOption('cache_enabled');
    $cache_max_size = Database::getInstance()->retrieveWordpressOption('cache_max_size');
    $debug = Database::getInstance()->retrieveWordpressOption('debug') ? 5 : false;

    Configuration::getInstance()->set('token', $token);

    Configuration::getInstance()->set('cache_enabled', $cache_enabled);
    Configuration::getInstance()->set('cache_max_size', $cache_max_size);
    Configuration::getInstance()->set('debug', $debug);

    $options = linguiseGetOptions();
    foreach ($options['expert_mode'] as $key => $value) {
        Configuration::getInstance()->set($key, $value);
    }
}

/**
 * Get configuration attributes
 *
 * @return array
 */
function linguiseGetConfiguration()
{
    linguiseInitializeConfiguration();

    $instance = Configuration::getInstance();
    // get all attributes from the class
    $attributes = $instance->toArray();
    return $attributes;
}

if (wp_doing_ajax()) {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action
    if (empty($_REQUEST['action']) || strpos($_REQUEST['action'], 'wc_emailer') === false) {
        include_once(__DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'debug.php');
        include_once(__DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'cache.php');
        return;
    }
}

// fixme: should not be a global script variable
$languages_names = \Linguise\WordPress\Helper::getLanguagesInfos();

include_once(__DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'install.php');
include_once(__DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'switcher.php');
include_once(__DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'frontend/ukrainian_redirection.php');
include_once(__DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'frontend/browser_language.php');
include_once(__DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'woocommerce.php');
include_once(__DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'elementor.php');

include_once(__DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'configuration.php');
include_once(__DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'admin/menu.php');

register_deactivation_hook(__FILE__, 'linguiseUnInstall');
/**
 * UnInstall plugin
 *
 * @return void
 */
function linguiseUnInstall()
{
    global $wp_filesystem;
    if (empty($wp_filesystem)) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        WP_Filesystem();
    }

    // Save htaccess content
    $htaccess_path = ABSPATH . DIRECTORY_SEPARATOR . '.htaccess';
    $htaccess_content = $wp_filesystem->get_contents($htaccess_path);
    if ($wp_filesystem->exists($htaccess_path) && is_writable($htaccess_path)) {
        if (strpos($htaccess_content, '#### LINGUISE DO NOT EDIT ####') !== false) {
            $htaccess_content = preg_replace('/#### LINGUISE DO NOT EDIT ####.*?#### LINGUISE DO NOT EDIT END ####/s', '', $htaccess_content);
            $wp_filesystem->put_contents($htaccess_path, $htaccess_content);
        }
    }
}

add_action('admin_notices', function () {
    $translate_plugins = array(
        'sitepress-multilingual-cms/sitepress.php' => 'WPML Multilingual CMS',
        'polylang/polylang.php' => 'Polylang',
        'polylang-pro/polylang.php' => 'Polylang Pro',
        'translatepress-multilingual/index.php' => 'TranslatePress',
        'weglot/weglot.php' => 'Weglot',
        'gtranslate/gtranslate.php' => 'GTranslate',
        'conveythis-translate/index.php' => 'ConveyThis',
        'google-language-translator/google-language-translator.php' => 'Google Language Translator',
    );

    foreach ($translate_plugins as $path => $plugin_name) {
        if (is_plugin_active($path)) {
            echo '<div class="error">';
            echo '<p>' . sprintf(esc_html__('We\'ve detected that %s translation plugin is installed. Please disable it before using Linguise to avoid conflict with translated URLs mainly', 'linguise'), '<strong>' . esc_html($plugin_name) . '</strong>') . '</p>';
            echo '</div>';
        }
    }
});

/**
 * Compatibility Checker for GTranslate
 */
add_action('admin_notices', function () {
    $htaccess_file = ABSPATH . '.htaccess';
    if (file_exists($htaccess_file)) {
        if (strpos(file_get_contents($htaccess_file), 'BEGIN GTranslate config') !== false) {
            echo '<div class="error">';
            echo '<p>' . sprintf(esc_html__("It looks like you have %1\$s extension that hasn't been properly uninstalled and prevents Linguise from working properly, please contact our support team or remove %2\$s code from your .htaccess file.", 'linguise'), '<strong>GTranslate</strong>', '<strong>GTranslate</strong>') . '</p>';
            echo '</div>';
        }
    }
});

add_action('admin_notices', function () {
    $options = linguiseGetOptions();
    if ($options['debug']) {
        ?>
        <div id="linguise_admin_notice_debug" class="notice notice-warning" style="display: flex; flex-direction: row;">
            <img src="data:image/webp;base64,UklGRqYDAABXRUJQVlA4TJkDAAAvx8AOEF8wRuM1vgraRkLxS2CGABzhWDwQjMOjUEBQmrZtbd08loxJQeWGVeZWM+GUvCrDWZVBYcYtM2lzTleZUZnbsPnz/fP0SZZ0XFxF9H8C9P/v9qrKX0CHyJ3k1IV1U6d3gagyQZfdKZ5TFt8palDbOF6UW++KUw5vFFUKGrn6Ks7Lh1Y5QqgVhOSN6zkFsLAcJ6BZjB9L6i7OyYcF5QihVkz7sKQTb+ekwRW3HJ6JKsWEn0pqH87Lv0cl9R0VO/uipCDKa2reXChJhQXb1lRS/C2vOQVs3vZIir95jZOh25Lk5zaingjHsKtiCQxEbqLLAskfX3Ekf3zFkeT9CjxpCUYQu2n9hqSggKYkzwAssgwB3k4MWCgF4EoBuJLmSFYTPYDDacOapHB3MSdIxokQq5uTsTwjKcBaTzGupBMvFtODS2OoS+pAbGBxPm3gMkSSfoDYwIspSNL3S4ox7NYMtCQNiZ3A8HY+J+AB/QSO1CN2fMNhWxAnOq1CPGhKQ56RZFgkdYjyuUkkzUBVGvGMNMcV28zhRLdZSACu1OVFSVCXThDntVvyoC4ZmlIItgNvJwb1Etxid1oIeR2WdOGCK0E90+wziZFbgpslOAFV2Se5uTAxrkyFGTjsZJrjiq3blOTHmgoycNjNEBgO2/p1SUE0JeaAyLVZn7ENq5LC3VPCGwJRJUvVZkiN3SkgfwgsyvCW7GR8cRrI/w3itJ1OSqzUA7ungrwhVBN1ZfQztKdAsNKV2tCcSKSFn5boRF5dnpFkaKXMcMVm3JQTL2boEOdnWCR1LH2ekdoTvC1pxELJ0JROgG3QTLm5MMMsNHIbEjuBsXTZLZ0Apd0ikmRoSiOWSD8Q2TovpnRaGUKIVq5c6eTSgdhg+Z4rrvrEGWahoQPQkPrEjmf41BZecWy9RgZvTLKZS4hd0gGIL8PbGdpw5cIYXOkHiEaw2OaZt2yDWgZ1C9Awgzcm2cqgEclI0gzWmk1zvOEkRm6W9riAwMB2i35MxJUsc5ZFkjRMHFb6r9iV+dA4rSH54CTeTsjf8mho80fA/ZLkgyNJvwK7lAwMxG4G7+w4EWWTv3LlSke5p8jfuu1RTbh525qKRcG2ba4mHLzGgxPkv2VbJUvZR6PDKinU58+YWnka0gni+eDzlspqWCx1iOZDcMUpzYDY8Q2fzgc9otJ2IDaweF6UOMRam24aJg5rygcGYnfayd/6gaP/BQMA" width="200" height="60" />
            <div class="notice-content">
                <p style="font-size: 16px; font-weight: 700;">
                    <?php echo esc_html_e('Linguise debug mode is currently enabled.', 'linguise'); ?>
                </p>
                <p>
                    <?php echo esc_html_e('This mode is intended for debugging purposes only and will generate a ton of logs that will consume a lot of space.', 'linguise'); ?>
                    <br />
                    <?php echo esc_html_e('Please disable it when you are done.', 'linguise'); ?>
                </p>
                <p>
                    <?php $disable_url = wp_nonce_url(admin_url('admin-ajax.php') . '?action=linguise_disable_debug', '_linguise_nonce_'); ?>
                    <a href="<?php echo esc_url($disable_url); ?>" class="button" id="linguise_admin_notice_debug_disable">
                        <?php esc_html_e('Disable debug!', 'linguise') ?>
                    </a>
                </p>
            </div>
        </div>
        <script type="text/javascript">
            jQuery(function($) {
                $(document).ready(function() {
                    $(document).on('click', '#linguise_admin_notice_debug_disable', function (e) {
                        e.preventDefault();
                        $.ajax({
                            url: $(this).prop('href'),
                            method: 'POST',
                            success: function(data) {
                                if (data.success) {
                                    $('#linguise_admin_notice_debug').fadeOut('fast', function () {
                                        $(this).remove();
                                    })
                                } else {
                                    alert('Failed to disable debug mode for Linguise');
                                }
                            },
                            error: function (data) {
                                alert('Failed to disable debug mode for Linguise');
                            }
                        })
                    })
                });
            });
        </script>
        <?php
    }
});

add_action('parse_query', function ($query_object) {
    $linguise_original_language = \Linguise\WordPress\Helper::getLanguage();

    if (!$linguise_original_language) {
        return;
    }

    $options = linguiseGetOptions();

    if (!$options['search_translation']) {
        return;
    }

    if ($query_object->is_search()) {
        $raw_search = $query_object->query['s'];

        if (!defined('LINGUISE_SCRIPT_TRANSLATION')) {
            define('LINGUISE_SCRIPT_TRANSLATION', 1);
        }

        include_once('vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

        Configuration::getInstance()->set('cms', 'wordpress');
        Configuration::getInstance()->set('token', $options['token']);

        $translation = \Linguise\Vendor\Linguise\Script\Core\Translation::getInstance()->translateJson(['search' => $raw_search], site_url(), $linguise_original_language, '/');

        if (empty($translation->search)) {
            return;
        }

        $query_object->set('s', $translation->search);
    }
});

/**
 * First hook available to check if we should translate this request
 *
 * @return void
 */
function linguiseFirstHook()
{
    static $run = null;

    // Check if it has been already called or not
    if ($run) {
        return;
    }
    $run = true;

    $linguise_original_language = \Linguise\WordPress\Helper::getLanguage();
    $languages_names = \Linguise\WordPress\Helper::getLanguagesInfos();
    if (!empty($linguise_original_language) && !empty($languages_names->$linguise_original_language)) {
        // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- This is a WP Global variable override
        $GLOBALS['text_direction'] = $languages_names->$linguise_original_language->rtl ? 'rtl' : 'ltr';
        return;
    }

    if (is_admin()) {
        return;
    }

    $linguise_options = linguiseGetOptions();

    if (!$linguise_options['token']) {
        return;
    }

    include_once('vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

    $base_dir = site_url('', 'relative');
    $path = substr($_SERVER['REQUEST_URI'], strlen($base_dir));

    $path = parse_url('https://localhost/' . ltrim($path, '/'), PHP_URL_PATH);

    $parts = explode('/', trim($path, '/'));

    if (!count($parts) || $parts[0] === '') {
        return;
    }

    $language = $parts[0];

    if (!in_array($language, array_merge($linguise_options['enabled_languages'], array('zz-zz')))) {
        return;
    }

    $_GET['linguise_language'] = $language;

    if (is_plugin_active('woocommerce/woocommerce.php')) {
        define('LINGUISE_SCRIPT_TRANSLATION_WOOCOMMERCE', true);
    }

    add_filter('ecwid_lang', $language);

    if (!defined('WP_ROCKET_WHITE_LABEL_FOOTPRINT')) {
        define('WP_ROCKET_WHITE_LABEL_FOOTPRINT', true);
    }

    include_once('script.php');
}
add_action('muplugins_loaded', 'linguiseFirstHook', 1);
add_action('plugins_loaded', 'linguiseFirstHook', 1);
add_action('init', function () {
    load_plugin_textdomain('linguise', false, dirname(plugin_basename(__FILE__)) . '/languages');
});
