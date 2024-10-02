<?php
defined('ABSPATH') || die('');

/**
 * Class Linguise Configuration
 */
class LinguiseConfiguration
{
    /**
     * Languages names
     *
     * @var array
     */
    public $languages_names = array();

    /**
     * LinguiseConfiguration constructor.
     *
     * @param array $languages_names Languages names
     */
    public function __construct($languages_names)
    {
        if (!empty($languages_names)) {
            $this->languages_names = $languages_names;
        }
        add_action('admin_menu', array($this, 'registerMenuPage'));
        add_action('admin_head', array($this, 'adminHead'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueScripts'));
    }



    /**
     * Function custom menu icon
     *
     * @return void
     */
    public function adminHead()
    {
        echo '<style>
    #toplevel_page_linguise .dashicons-before img {
            width: 25px;
            padding-top: 7px;
        }
  </style>';
    }

    /**
     * Register menu page
     *
     * @return void
     */
    public function registerMenuPage()
    {
        add_menu_page(
            'Linguise',
            'Linguise',
            'manage_options',
            'linguise',
            array($this, 'renderSettings'),
            LINGUISE_PLUGIN_URL . 'assets/images/linguise-logo.svg'
        );
    }

    /**
     *  Render settings
     *
     * @return void
     */
    public function renderSettings()
    {
        // check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }

        $errors = [];

        if (isset($_POST['linguise_options'])) {
            if (empty($_POST['main_settings'])
                || !wp_verify_nonce($_POST['main_settings'], 'linguise-settings')) {
                die();
            }

            $old_options =  linguiseGetOptions();
            $default_language = sanitize_key($_POST['linguise_options']['default_language']);

            $expert_mode_conf = isset($old_options['expert_mode']) ? $old_options['expert_mode'] : [];

            $api_host = isset($expert_mode_conf['api_host']) ? $expert_mode_conf['api_host'] : 'api.linguise.com';
            $api_port = isset($expert_mode_conf['api_port']) ? $expert_mode_conf['api_port'] : '443';
            $api_portless = ['80', '443'];

            $api_base_url = 'http' . ($api_port === '443' ? 's' : '') . '://' . $api_host . (!in_array($api_port, $api_portless) ? ':' . $api_port : '');

            $translate_languages = [];

            $token = sanitize_text_field($_POST['linguise_options']['token']);
            $dynamic_translations = [
                'enabled' => $_POST['linguise_options']['dynamic_translations'] === '1' ? 1 : 0,
                'public_key' => '',
            ];

            $token_changed = false;
            $config_api_url = $api_base_url . '/api/config';
            if ($old_options['token'] !== $token && $token !== '') {
                $args  = array(
                    'method'              => 'GET',
                    'headers'             => array('Referer' => site_url(), 'authorization' => $token)
                );

                $result =  wp_remote_get($config_api_url, $args);
                if (!is_wp_error($result) && isset($result['response']['code'])
                    && ($result['response']['code'] === 200) && !empty($result['body'])) {
                    $apiResponse = json_decode($result['body']);
                    if (!empty($apiResponse) && is_object($apiResponse)) {
                        $default_language = sanitize_key($apiResponse->data->language);
                        $translation_languages = $apiResponse->data->languages;
                        if (!empty($translation_languages)) {
                            foreach ($translation_languages as $translation_language) {
                                $translate_languages[] = sanitize_key($translation_language->code);
                            }
                        }
                        $dynamic_translations['public_key'] = $apiResponse->data->public_key;
                        $token_changed = true;
                    }
                } else {
                    if (!is_wp_error($result) && !empty($result['response']['code']) && $result['response']['code'] === 404) {
                        $errors[] = [
                            'type' => 'error',
                            'message' => sprintf(__('The API Key provided has been rejected, please make sure you use the right key associated with the domain %s', 'linguise'), site_url()),
                        ];
                    } else {
                        $errors[] = [
                            'type' => 'error',
                            'message' => __('Configuration has not been loaded from Linguise website. Please try again later or contact our support team if the problem persist.', 'linguise'),
                        ];
                    }
                    if (!empty($old_options['enabled_languages'])) {
                        $translate_languages = $old_options['enabled_languages'];
                    }
                }
            } else {
                if (!empty($_POST['enabled_languages_sortable'])) {
                    $lang_lists = explode(',', $_POST['enabled_languages_sortable']);
                } else {
                    $lang_lists = (!empty($_POST['linguise_options']['enabled_languages'])) ? $_POST['linguise_options']['enabled_languages'] : array();
                }

                if (!empty($lang_lists)) {
                    foreach ($lang_lists as $language) {
                        $translate_languages[] = sanitize_key($language);
                    }
                }
            }

            if ($dynamic_translations['enabled'] === 1 && empty($dynamic_translations['public_key']) && !$token_changed && $token !== '') {
                $args  = array(
                    'method'              => 'GET',
                    'headers'             => array('Referer' => site_url(), 'authorization' => $token)
                );

                $result =  wp_remote_get($config_api_url, $args);

                if (!is_wp_error($result) && isset($result['response']['code'])
                    && ($result['response']['code'] === 200) && !empty($result['body'])) {
                    $apiResponse = json_decode($result['body']);
                    if (!empty($apiResponse) && is_object($apiResponse)) {
                        $dynamic_translations['public_key'] = $apiResponse->data->public_key;
                    }
                } else {
                    if (!is_wp_error($result) && !empty($result['response']['code']) && $result['response']['code'] === 404) {
                        $errors[] = [
                            'type' => 'error',
                            'message' => sprintf(__('The API Key provided has been rejected, please make sure you use the right key associated with the domain %s', 'linguise'), site_url()),
                        ];
                    } else {
                        $errors[] = [
                            'type' => 'error',
                            'message' => __('Configuration has not been loaded from Linguise website. Please try again later or contact our support team if the problem persist.', 'linguise'),
                        ];
                    }
                }
            };

            $pre_text = preg_replace('#<script(.*?)>(.*?)</script>#is', '', stripslashes($_POST['linguise_options']['pre_text']));
            $post_text = preg_replace('#<script(.*?)>(.*?)</script>#is', '', stripslashes($_POST['linguise_options']['post_text']));
            $add_flag_automatically = $_POST['linguise_options']['add_flag_automatically'] === '1' ? 1 : 0;
            $alternate_link = $_POST['linguise_options']['alternate_link'] === '1' ? 1 : 0;
            $enable_flag = $_POST['linguise_options']['enable_flag'] === '1' ? 1 : 0;
            $enable_language_name = $_POST['linguise_options']['enable_language_name'] === '1' ? 1 : 0;
            $enable_language_short_name = $_POST['linguise_options']['enable_language_short_name'] === '1' ? 1 : 0;
            $browser_redirect = $_POST['linguise_options']['browser_redirect'] === '1' ? 1 : 0;
            $ukraine_redirect = $_POST['linguise_options']['ukraine_redirect'] === '1' ? 1 : 0;
            $cache_enabled = $_POST['linguise_options']['cache_enabled'] === '1' ? 1 : 0;
            $cache_max_size = isset($_POST['linguise_options']['cache_max_size']) ? (int)$_POST['linguise_options']['cache_max_size'] : 200;
            $search_translation = $_POST['linguise_options']['search_translation'] === '1' ? 1 : 0;
            $woocommerce_emails_translation = $_POST['linguise_options']['woocommerce_emails_translation'] === '1' ? 1 : 0;
            $debug = $_POST['linguise_options']['debug'] === '1' ? 1 : 0;

            $linguise_options = array(
                'token' => $token,
                'default_language' => $default_language,
                'enabled_languages' => $translate_languages,
                'pre_text' => $pre_text,
                'post_text' => $post_text,
                'alternate_link' => $alternate_link,
                'enable_flag' => $enable_flag,
                'enable_language_name' => $enable_language_name,
                'enable_language_short_name' => $enable_language_short_name,
                'add_flag_automatically' => $add_flag_automatically,
                'custom_css' => isset($_POST['linguise_options']['custom_css']) ? $_POST['linguise_options']['custom_css'] : '',
                'flag_display_type' => isset($_POST['linguise_options']['flag_display_type']) ? $_POST['linguise_options']['flag_display_type'] : 'popup',
                'display_position' => isset($_POST['linguise_options']['display_position']) ? $_POST['linguise_options']['display_position'] : 'no',
                'language_name_display' => isset($_POST['linguise_options']['language_name_display']) ? $_POST['linguise_options']['language_name_display'] : 'en',
                'flag_shape' => isset($_POST['linguise_options']['flag_shape']) ? $_POST['linguise_options']['flag_shape'] : 'rounded',
                'flag_en_type' => isset($_POST['linguise_options']['flag_en_type']) ? $_POST['linguise_options']['flag_en_type'] : 'en-us',
                'flag_de_type' => isset($_POST['linguise_options']['flag_de_type']) ? $_POST['linguise_options']['flag_de_type'] : 'de',
                'flag_es_type' => isset($_POST['linguise_options']['flag_es_type']) ? $_POST['linguise_options']['flag_es_type'] : 'es',
                'flag_pt_type' => isset($_POST['linguise_options']['flag_pt_type']) ? $_POST['linguise_options']['flag_pt_type'] : 'pt',
                'flag_tw_type' => isset($_POST['linguise_options']['flag_tw_type']) ? $_POST['linguise_options']['flag_tw_type'] : 'zh-tw',
                'flag_border_radius' => isset($_POST['linguise_options']['flag_border_radius']) ? (int)$_POST['linguise_options']['flag_border_radius'] : 0,
                'flag_width' => isset($_POST['linguise_options']['flag_width']) ? (int)$_POST['linguise_options']['flag_width'] : 24,
                'language_name_color' => isset($_POST['linguise_options']['language_name_color']) ? $_POST['linguise_options']['language_name_color'] : '#222',
                'language_name_hover_color' => isset($_POST['linguise_options']['language_name_hover_color']) ? $_POST['linguise_options']['language_name_hover_color'] : '#222',
                'popup_language_name_color' => isset($_POST['linguise_options']['popup_language_name_color']) ? $_POST['linguise_options']['popup_language_name_color'] : '#222',
                'popup_language_name_hover_color' => isset($_POST['linguise_options']['popup_language_name_hover_color']) ? $_POST['linguise_options']['popup_language_name_hover_color'] : '#222',
                'flag_shadow_h' => isset($_POST['linguise_options']['flag_shadow_h']) ? (int)$_POST['linguise_options']['flag_shadow_h'] : 2,
                'flag_shadow_v' => isset($_POST['linguise_options']['flag_shadow_v']) ? (int)$_POST['linguise_options']['flag_shadow_v'] : 2,
                'flag_shadow_blur' => isset($_POST['linguise_options']['flag_shadow_blur']) ? (int)$_POST['linguise_options']['flag_shadow_blur'] : 12,
                'flag_shadow_spread' => isset($_POST['linguise_options']['flag_shadow_spread']) ? (int)$_POST['linguise_options']['flag_shadow_spread'] : 0,
                'flag_shadow_color' => isset($_POST['linguise_options']['flag_shadow_color']) ? $_POST['linguise_options']['flag_shadow_color'] : '#eee',
                'flag_hover_shadow_h' => isset($_POST['linguise_options']['flag_hover_shadow_h']) ? (int)$_POST['linguise_options']['flag_hover_shadow_h'] : 3,
                'flag_hover_shadow_v' => isset($_POST['linguise_options']['flag_hover_shadow_v']) ? (int)$_POST['linguise_options']['flag_hover_shadow_v'] : 3,
                'flag_hover_shadow_blur' => isset($_POST['linguise_options']['flag_hover_shadow_blur']) ? (int)$_POST['linguise_options']['flag_hover_shadow_blur'] : 6,
                'flag_hover_shadow_spread' => isset($_POST['linguise_options']['flag_hover_shadow_spread']) ? (int)$_POST['linguise_options']['flag_hover_shadow_spread'] : 0,
                'flag_hover_shadow_color' => isset($_POST['linguise_options']['flag_hover_shadow_color']) ? $_POST['linguise_options']['flag_hover_shadow_color'] : '#bfbfbf',
                'browser_redirect' => $browser_redirect,
                'ukraine_redirect' => $ukraine_redirect,
                'cache_enabled' => $cache_enabled,
                'cache_max_size' => $cache_max_size,
                'search_translation' => $search_translation,
                'woocommerce_emails_translation' => $woocommerce_emails_translation,
                'debug' => $debug,
                'dynamic_translations' => $dynamic_translations,
                'expert_mode' => $expert_mode_conf,
            );

            update_option('linguise_options', $linguise_options);

            echo '<div class="linguise_saved_wrap"><span class="material-icons"> done </span> '. esc_html__('Linguise settings saved!', 'linguise') .'</div>';
        }

        if (isset($_POST['expert_linguise'])) {
            // create ConfigurationLocal object
            $expert_config = $_POST['expert_linguise'];
            $original_config = linguiseGetConfiguration();
            $patched_options = linguiseGetOptions();

            foreach ($expert_config as $key => $value) {
                // check if $key exists in original config
                if (!isset($original_config[$key])) {
                    // apply directly if not exists
                    $patched_options['expert_mode'][$key] = $value;
                    continue;
                }

                $original = $original_config[$key];

                if (is_bool($original['value'])) {
                    $value = $value === '1' ? true : false;
                }
                if (is_numeric($original['value'])) {
                    $value = (int)$value;
                }

                if ($original['value'] === $value) {
                    // Skip if value is the same as original
                    continue;
                }
                
                if ($original['value'] === null && empty($value)) {
                    // If original is null and value is empty, we don't need to save it
                    continue;
                }

                $patched_options['expert_mode'][$key] = $value;
            }

            update_option('linguise_options', $patched_options);

            echo '<div class="linguise_saved_wrap"><span class="material-icons"> done </span> '. esc_html__('Linguise settings saved!', 'linguise') .'</div>';
        };

        if (isset($_POST['linguise_debug_disable']) && wp_verify_nonce($_POST['debug_banner_nonce'], 'linguise-settings')) {
            $options = linguiseGetOptions();
            $options['debug'] = false;

            update_option('linguise_options', $options);

            echo '<div class="linguise_saved_wrap"><span class="material-icons"> done </span> '. esc_html__('Disabled debug!', 'linguise') .'</div>';
        }

        // get URL query
        $view_mode = isset($_GET['ling_mode']) ? $_GET['ling_mode'] : 'standard';

        // Show expert mode
        if ($view_mode === 'expert') {
            require_once(LINGUISE_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'src/admin/views' . DIRECTORY_SEPARATOR . 'expert-view.php');
        } else {
            require_once(LINGUISE_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'src/admin/views' . DIRECTORY_SEPARATOR . 'view.php');
        }
    }

    /**
     * Patch htaccess file
     *
     * @throws Exception With custom error message
     *
     * @return void
     */
    protected function patchHtaccess()
    {
        global $wp_filesystem;
        if (empty($wp_filesystem)) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            WP_Filesystem();
        }

        // Save htaccess content
        $htaccess_path = ABSPATH . DIRECTORY_SEPARATOR . '.htaccess';
        if (!$wp_filesystem->exists($htaccess_path)) {
            throw new Exception(__('Htaccess file doesn\'t exist. This may be a problem is you are under an Apache server. Please check the documentation to finish the installation manually: <a href="https://www.linguise.com/documentation/linguise-installation/install-linguise-on-wordpress/" target="_blank">how to configure Linguise</a>', 'linguise'), 0);
        }

        $script_path = LINGUISE_PLUGIN_PATH . 'script.php';
        $script_path_parts = explode('/', trim($script_path, '/'));
        $abspath_parts = explode('/', trim(ABSPATH, '/'));

        $script_relative_path = array_slice($script_path_parts, count($abspath_parts));
        $script_relative_path = implode('/', $script_relative_path);

        $htaccess_content = $wp_filesystem->get_contents($htaccess_path);
        $htaccess_content_original = $htaccess_content;
        $htaccess_patched = false;
        if (strpos($htaccess_content, '#### LINGUISE DO NOT EDIT ####') !== false) {
            $htaccess_patched = true;
        }

        $content =
            '#### LINGUISE DO NOT EDIT ####' . PHP_EOL .
            '<IfModule mod_rewrite.c>' . PHP_EOL .
            '   RewriteEngine On' . PHP_EOL .
            '   RewriteRule ^(af|sq|am|ar|hy|az|bn|bs|bg|ca|zh-cn|zh-tw|hr|cs|da|nl|en|eo|et|fi|fr|de|el|gu|ht|ha|iw|hi|hmn|hu|is|ig|id|ga|it|ja|kn|kk|km|ko|ku|lo|lv|lt|lb|mk|mg|ms|ml|mt|mi|mr|mn|ne|no|ps|fa|pl|pt|pa|ro|ru|sm|sr|sd|sk|sl|es|su|sw|sv|tg|ta|te|th|tr|uk|ur|vi|cy|zz-zz)(?:$|/)(.*)$ ' . $script_relative_path . '?linguise_language=$1&original_url=$2 [L,QSA]' . PHP_EOL .
            '</IfModule>' . PHP_EOL .
            '#### LINGUISE DO NOT EDIT END ####' . PHP_EOL;

        if ($htaccess_patched) {
            // Replace previous version
            $htaccess_content = preg_replace('/#### LINGUISE DO NOT EDIT ####.*?#### LINGUISE DO NOT EDIT END ####/', $content, $htaccess_content);
        } else {
            // Add it at the beginning of the file
            $htaccess_content = $content . PHP_EOL . $htaccess_content;
        }

        if ($htaccess_content_original === $htaccess_content) {
            return;
        }

        if (!is_writable($htaccess_path)) {
            throw new Exception(__('Htaccess file is not writable, please make sure to allow the current script to update the .htaccess file to make linguise work as expected. You can also check our online documentation to read <a href="https://www.linguise.com/documentation/linguise-installation/install-linguise-on-wordpress/" target="_blank">how to configure Linguise</a>.', 'linguise'), 1);
        }

        // Only write if necessary
        if (!$wp_filesystem->put_contents($htaccess_path, $htaccess_content)) {
            throw new Exception(__('Failed to write to htaccess file, please make sure to allow the current script to update the .htaccess file to make linguise work as expected. You can also check our online documentation to read <a href="https://www.linguise.com/documentation/linguise-installation/install-linguise-on-wordpress/" target="_blank">how to configure Linguise</a>.', 'linguise'), 2);
        }
    }

    /**
     * Enqueue scripts
     *
     * @return void
     */
    public function enqueueScripts()
    {
        global $current_screen;
        if (!empty($current_screen) && $current_screen->id === 'toplevel_page_linguise') {
            wp_enqueue_script('jquery-ui-sortable');
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
            wp_enqueue_script(
                'linguise_chosen_sortable_script',
                LINGUISE_PLUGIN_URL . 'assets/js/jquery-chosen-sortable.min.js',
                array('jquery'),
                LINGUISE_VERSION,
                true
            );
            wp_enqueue_script(
                'linguise_admin_script',
                LINGUISE_PLUGIN_URL . 'assets/js/admin.bundle.js',
                array('jquery'),
                LINGUISE_VERSION,
                true
            );
            wp_localize_script('linguise_admin_script', 'linguise', array(
                'linguise_nonce' => wp_create_nonce('linguise_nonce'),
                'ajaxurl'               => admin_url('admin-ajax.php')
            ));
            wp_enqueue_style(
                'linguise_admin_script',
                LINGUISE_PLUGIN_URL . 'assets/css/admin.bundle.css',
                array(),
                LINGUISE_VERSION
            );
            wp_enqueue_style('linguise_switcher', LINGUISE_PLUGIN_URL . '/assets/css/front.bundle.css', array(), LINGUISE_VERSION);
        }
    }
}

new LinguiseConfiguration($languages_names);
