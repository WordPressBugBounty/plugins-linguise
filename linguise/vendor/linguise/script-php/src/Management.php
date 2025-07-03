<?php

namespace Linguise\Vendor\Linguise\Script\Core;

defined('LINGUISE_SCRIPT_TRANSLATION') or die();

class Management {
    /**
     * @var null|Management
     */
    private static $_instance = null;

    /**
     * @var string
     */
    private static $token_key = 'linguise_token';
    /**
     * Mapping data from the API to the local config
     *
     * @var array
     */
    private static $flag_options_map = [
        'display' => 'flag_display_type',
        'position' => 'display_position',
        'enabled_flag' => 'enable_flag',
        'enabled_lang_name' => 'enable_language_name',
        'enabled_lang_name_popup' => 'enable_language_name_popup',
        'enabled_lang_short_name' => 'enable_language_short_name',
        'lang_name_display' => 'language_name_display',
    ];

    private function __construct()
    {
        // Private constructor to prevent instantiation
    }

    /**
     * Get the singleton instance of the Management class
     *
     * @return Management
     */
    public static function getInstance()
    {
        if (is_null(self::$_instance)) {
            // Create a new instance if it doesn't exist
            self::$_instance = new Management();
        }
        return self::$_instance;
    }

    public function run($html_message = \null, $api_web_errors = []) {
        // Start session
        $sess = Session::getInstance()->start();
        // Set our CSRF token, always overrides
        $sess->generateCsrfToken();

        // We define this constant so user can't do direct access to the template
        $this->defineConstants(false);

        if (isset($_GET['linguise_action'])) {
            switch ($_GET['linguise_action']) {
                case 'download-debug':
                    $this->downloadDebug();
                    break;
                case 'update-config':
                    break;
                default:
                    if (empty($_SESSION[self::$token_key])) {
                        $this->rejectGET();
                    } else {
                        $this->unknownGETAction();
                    }
                    break;
            }
        }

        require_once LINGUISE_BASE_DIR . 'src' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'Helper.php';
        require_once LINGUISE_BASE_DIR . 'src' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'stubs.php';

        if (!$sess->hasSession()) {
            // Render login page
            require_once LINGUISE_BASE_DIR . 'src' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
            if (!empty($html_message)) {
                echo $html_message;
            }
            require_once LINGUISE_BASE_DIR . 'src' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'oobe.php';
        } else {
            // Logged in? we send it!
            $this->defineConstants(true);
            require_once LINGUISE_BASE_DIR . 'src' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
            if (!empty($html_message)) {
                echo $html_message;
            }
            $view_mode = isset($_GET['ling_mode']) ? $_GET['ling_mode'] : 'default';
            if ($view_mode === 'expert') {
                require_once LINGUISE_BASE_DIR . 'src' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'management-expert.php';
            } else {
                require_once LINGUISE_BASE_DIR . 'src' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'management.php';
            }
        }
        require_once LINGUISE_BASE_DIR . 'src' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';
        die();
    }

    public function oobeRun($html_message = \null) {
        // Start session
        $sess = Session::getInstance()->start();
        // Set our CSRF token, always overrides
        $sess->generateCsrfToken();

        $this->defineConstants(false);

        require_once LINGUISE_BASE_DIR . 'src' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'stubs.php';
        require_once LINGUISE_BASE_DIR . 'src' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
        if (!empty($html_message)) {
            echo $html_message;
        }
        require_once LINGUISE_BASE_DIR . 'src' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'oobe.php';
        require_once LINGUISE_BASE_DIR . 'src' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';
    }

    public function editorRun() {
        $this->defineConstants(false);

        require_once LINGUISE_BASE_DIR . 'src' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'stubs.php';
        require_once LINGUISE_BASE_DIR . 'src' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'editor.php';
    }

    // This is from Website -> API (and also local)
    public function updateConfig()
    {
        // Start session
        $sess = Session::getInstance()->start();

        // XXX: Errors data
        $api_web_errors = [];

        // Verify session
        if (!$sess->hasSession()) {
            $message = '<div class="linguise-notification-popup"><span class="material-icons fail">check</span>Not authorized</div>';
            $this->run($message, $api_web_errors);
        }
        if (!isset($_POST['_token'])) {
            $this->errorJSON('Missing CSRF token', 400);
            $message = '<div class="linguise-notification-popup"><span class="material-icons fail">check</span>Missing CSRF token</div>';
            $this->run($message, $api_web_errors);
        }
        if (!$sess->verifyCsrfToken('linguise_config', $_POST['_token'])) {
            $message = '<div class="linguise-notification-popup"><span class="material-icons fail">check</span>Invalid CSRF token</div>';
            $this->run($message, $api_web_errors);
        }

        $db = Database::getInstance()->ensureConnection();

        $notification_popup_msg = null;
        if (isset($_POST['linguise_options'])) {
            $linguise_options = $_POST['linguise_options'];

            $old_options = $db->retrieveOtherParam('linguise_options');
            $expert_mode_conf = isset($old_options['expert_mode']) ? $old_options['expert_mode'] : [];

            $token = $linguise_options['token'];
            $dynamic_translations = [
                'enabled' => isset($linguise_options['dynamic_translations']) && $linguise_options['dynamic_translations'] === '1' ? 1 : 0,
                'public_key' => '',
            ];

            $default_language = Helper::sanitizeKey($linguise_options['default_language']);
            $translate_languages = [];

            $queried_api = false;
            if ($old_options['token'] !== $token && $token !== '') {
                $api_result = $this->getRemoteData($token);
                $queried_api = true;
                if ($api_result !== false && isset($api_result['data'])) {
                    $default_language = Helper::sanitizeKey($api_result['data']['language']);
                    $dynamic_translations['public_key'] = $api_result['data']['public_key'];

                    $translation_languages = $api_result['data']['languages'];
                    if (!empty($translation_languages)) {
                        foreach ($translation_languages as $translation_language) {
                            $translate_languages[] = Helper::sanitizeKey($translation_language['code']);
                        }
                    }

                    $dynamic_translations['public_key'] = $api_result['data']['public_key'];
                } else if ($api_result !== false && isset($api_result['status_code'])) {
                    $api_web_errors[] = [
                        'type' => 'error',
                        'message' => 'The API key provided has been rejected, make sure you use the right key with the associated domain ' . Request::getInstance(true)->getBaseUrl(),
                    ];
                } else {
                    $api_web_errors[] = [
                        'type' => 'error',
                        'message' => 'Unable to load configuration from Linguise, please try again later or contact our support team if the problem persist.',
                    ];
                }

                // Has error and we don't have any languages? We just use the old ones
                if (!empty($api_web_errors) && !empty($old_options['enabled_languages'])) {
                    $translate_languages = $old_options['enabled_languages'];
                }
            } else {
                if (!empty($_POST['enabled_languages_sortable'])) {
                    $lang_lists = explode(',', $_POST['enabled_languages_sortable']);
                } else {
                    $lang_lists = !empty($linguise_options['enabled_languages']) ? $linguise_options['enabled_languages'] : array();
                }

                if (!empty($lang_lists)) {
                    foreach ($lang_lists as $lang) {
                        $lang = Helper::sanitizeKey($lang);
                        if (!empty($lang)) {
                            $translate_languages[] = $lang;
                        }
                    }
                }
            }

            if (empty($dynamic_translations['public_key']) && !empty($old_options['dynamic_translations']['public_key'])) {
                $dynamic_translations['public_key'] = $old_options['dynamic_translations']['public_key'];
            }

            $pre_text = preg_replace('#<script(.*?)>(.*?)</script>#is', '', stripslashes($linguise_options['pre_text']));
            $post_text = preg_replace('#<script(.*?)>(.*?)</script>#is', '', stripslashes($linguise_options['post_text']));
            $enable_flag = isset($linguise_options['enable_flag']) && $linguise_options['enable_flag'] === '1' ? 1 : 0;
            $enable_language_name = isset($linguise_options['enable_language_name']) && $linguise_options['enable_language_name'] === '1' ? 1 : 0;
            $enable_language_name_popup = isset($_POST['linguise_options']['enable_language_name_popup']) && $_POST['linguise_options']['enable_language_name_popup'] === '1' ? 1 : 0;
            $short_name_input = $linguise_options['enable_language_short_name'];
            $enable_language_short_name = ($short_name_input === '1' || $short_name_input === 'short') ? 1 : 0;
            // $browser_redirect = isset($linguise_options['browser_redirect']) && $linguise_options['browser_redirect'] === '1' ? 1 : 0;
            $cache_enabled = isset($linguise_options['cache_enabled']) && $linguise_options['cache_enabled'] === '1' ? 1 : 0;
            $cache_max_size = isset($linguise_options['cache_max_size']) ? (int)$linguise_options['cache_max_size'] : 200;
            $search_translation = isset($linguise_options['search_translation']) && $linguise_options['search_translation'] === '1' ? 1 : 0;
            $debug = isset($linguise_options['debug']) && $linguise_options['debug'] === '1' ? 1 : 0;

            $linguise_options = [
                'token' => $token,
                'cache_enabled' => $cache_enabled,
                'cache_max_size' => $cache_max_size,
                'search_translations' => $search_translation,
                'debug' => $debug,
                'dynamic_translations' => $dynamic_translations,

                // Languages selected
                'default_language' => $default_language,
                'enabled_languages' => $translate_languages,

                // Flag related
                'display_position' => isset($linguise_options['display_position']) ? $linguise_options['display_position'] : 'no',
                'flag_display_type' => isset($linguise_options['flag_display_type']) ? $linguise_options['flag_display_type'] : 'popup',
                'enable_flag' => $this->boolInt($enable_flag),
                'enable_language_name' => $this->boolInt($enable_language_name),
                'enable_language_name_popup' => $this->boolInt($enable_language_name_popup),
                'enable_language_short_name' => $this->boolInt($enable_language_short_name),
                'language_name_display' => isset($linguise_options['language_name_display']) ? $linguise_options['language_name_display'] : 'en',
                'flag_shape' => isset($linguise_options['flag_shape']) ? $linguise_options['flag_shape'] : 'rounded',
                'flag_en_type' => isset($linguise_options['flag_en_type']) ? $linguise_options['flag_en_type'] : 'en-us',
                'flag_de_type' => isset($linguise_options['flag_de_type']) ? $linguise_options['flag_de_type'] : 'de',
                'flag_es_type' => isset($linguise_options['flag_es_type']) ? $linguise_options['flag_es_type'] : 'es',
                'flag_pt_type' => isset($linguise_options['flag_pt_type']) ? $linguise_options['flag_pt_type'] : 'pt',
                'flag_tw_type' => isset($linguise_options['flag_tw_type']) ? $linguise_options['flag_tw_type'] : 'zh-tw',
                'flag_border_radius' => isset($linguise_options['flag_border_radius']) ? (int)$linguise_options['flag_border_radius'] : 0,
                'flag_width' => isset($linguise_options['flag_width']) ? (int)$linguise_options['flag_width'] : 24,
                'pre_text' => $pre_text,
                'post_text' => $post_text,
                'custom_css' => isset($linguise_options['custom_css']) ? $linguise_options['custom_css'] : '',
                'language_name_color' => isset($linguise_options['language_name_color']) ? $linguise_options['language_name_color'] : '#222',
                'language_name_hover_color' => isset($linguise_options['language_name_hover_color']) ? $linguise_options['language_name_hover_color'] : '#222',
                'popup_language_name_color' => isset($linguise_options['popup_language_name_color']) ? $linguise_options['popup_language_name_color'] : '#222',
                'popup_language_name_hover_color' => isset($linguise_options['popup_language_name_hover_color']) ? $linguise_options['popup_language_name_hover_color'] : '#222',
                'flag_shadow_h' => isset($linguise_options['flag_shadow_h']) ? (int)$linguise_options['flag_shadow_h'] : 2,
                'flag_shadow_v' => isset($linguise_options['flag_shadow_v']) ? (int)$linguise_options['flag_shadow_v'] : 2,
                'flag_shadow_blur' => isset($linguise_options['flag_shadow_blur']) ? (int)$linguise_options['flag_shadow_blur'] : 12,
                'flag_shadow_spread' => isset($linguise_options['flag_shadow_spread']) ? (int)$linguise_options['flag_shadow_spread'] : 0,
                'flag_shadow_color' => isset($linguise_options['flag_shadow_color']) ? $linguise_options['flag_shadow_color'] : '#eee',
                'flag_shadow_color_alpha' => isset($linguise_options['flag_shadow_color_alpha']) ? (float)$linguise_options['flag_shadow_color_alpha'] : 1.0,
                'flag_hover_shadow_h' => isset($linguise_options['flag_hover_shadow_h']) ? (int)$linguise_options['flag_hover_shadow_h'] : 3,
                'flag_hover_shadow_v' => isset($linguise_options['flag_hover_shadow_v']) ? (int)$linguise_options['flag_hover_shadow_v'] : 3,
                'flag_hover_shadow_blur' => isset($linguise_options['flag_hover_shadow_blur']) ? (int)$linguise_options['flag_hover_shadow_blur'] : 6,
                'flag_hover_shadow_spread' => isset($linguise_options['flag_hover_shadow_spread']) ? (int)$linguise_options['flag_hover_shadow_spread'] : 0,
                'flag_hover_shadow_color' => isset($linguise_options['flag_hover_shadow_color']) ? $linguise_options['flag_hover_shadow_color'] : '#bfbfbf',
                'flag_hover_shadow_color_alpha' => isset($linguise_options['flag_hover_shadow_color_alpha']) ? (float)$linguise_options['flag_hover_shadow_color_alpha'] : 1.0,
                'language_flag_order' => $translate_languages,

                'expert_mode' => $expert_mode_conf,
            ];

            $db->saveOtherParam('linguise_options', $linguise_options);

            if (!$queried_api && !empty($linguise_options['token'])) {
                // Update to the API
                $payload = [
                    'language' => $default_language,
                    'allowed_languages' => $translate_languages,
                    'dynamic_translations' => $this->intBool($dynamic_translations['enabled']),
                    'language_settings' => [
                        // Flag related
                        'display' => $linguise_options['flag_display_type'],
                        'position' => $linguise_options['display_position'],
                        'enabled_flag' => $this->intBool($enable_flag),
                        'enabled_lang_name' => $this->intBool($enable_language_name),
                        'enabled_lang_name_popup' => $this->intBool($enable_language_name_popup),
                        'enabled_lang_short_name' => $this->intBool($enable_language_short_name),
                        'lang_name_display' => $linguise_options['language_name_display'],
                        'flag_shape' => $linguise_options['flag_shape'],
                        'flag_en_type' => $linguise_options['flag_en_type'],
                        'flag_de_type' => $linguise_options['flag_de_type'],
                        'flag_es_type' => $linguise_options['flag_es_type'],
                        'flag_pt_type' => $linguise_options['flag_pt_type'],
                        'flag_tw_type' => $linguise_options['flag_tw_type'],
                        'flag_border_radius' => $linguise_options['flag_border_radius'],
                        'flag_width' => $linguise_options['flag_width'],
                        'pre_text' => $pre_text,
                        'post_text' => $post_text,
                        'custom_css' => $linguise_options['custom_css'],
                        'language_name_color' => $linguise_options['language_name_color'],
                        'language_name_hover_color' => $linguise_options['language_name_hover_color'],
                        'popup_language_name_color' => $linguise_options['popup_language_name_color'],
                        'popup_language_name_hover_color' => $linguise_options['popup_language_name_hover_color'],
                        'flag_shadow_h' => $linguise_options['flag_shadow_h'],
                        'flag_shadow_v' => $linguise_options['flag_shadow_v'],
                        'flag_shadow_blur' => $linguise_options['flag_shadow_blur'],
                        'flag_shadow_spread' => $linguise_options['flag_shadow_spread'],
                        'flag_shadow_color' => $linguise_options['flag_shadow_color'],
                        'flag_shadow_color_alpha' => $linguise_options['flag_shadow_color_alpha'],
                        'flag_hover_shadow_h' => $linguise_options['flag_hover_shadow_h'],
                        'flag_hover_shadow_v' => $linguise_options['flag_hover_shadow_v'],
                        'flag_hover_shadow_blur' => $linguise_options['flag_hover_shadow_blur'],
                        'flag_hover_shadow_spread' => $linguise_options['flag_hover_shadow_spread'],
                        'flag_hover_shadow_color' => $linguise_options['flag_hover_shadow_color'],
                        'flag_hover_shadow_color_alpha' => $linguise_options['flag_hover_shadow_color_alpha'],
                        'language_flag_order' => $translate_languages,
                    ],
                ];

                $this->pushRemoteSync($payload, $token);
            }

            $notification_popup_msg = '<div class="linguise-notification-popup"><span class="material-icons">done</span> Linguise settings saved!</div>';
        }

        // expert config
        if (isset($_POST['expert_linguise'])) {
            $expert_config = $_POST['expert_linguise'];
            $original_config = Configuration::getInstance()->toArray();

            $patched_options = $db->retrieveOtherParam('linguise_options');

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

            $db->saveOtherParam('linguise_options', $patched_options);
            $notification_popup_msg = '<div class="linguise-notification-popup"><span class="material-icons">done</span> Linguise settings saved!</div>';
        }

        // Re-render
        $this->run($notification_popup_msg, $api_web_errors);
    }

    public function updateConfigIframe()
    {
        // Verify session
        $sess = Session::getInstance()->start();
        if (!$sess->hasSession()) {
            $this->errorJSON('Unauthorized', 401);
        }
        if (!isset($_POST['nonce'])) {
            $this->errorJSON('Missing nonce token', 400);
        }
        if (!$sess->verifyCsrfToken('linguise_config_iframe', $_POST['nonce'])) {
            $this->errorJSON('Invalid nonce token', 403);
        }

        $data = $_POST['config'];
        $db = Database::getInstance()->ensureConnection();
        $options = $db->retrieveOtherParam('linguise_options');

        // Check if all required fields are present
        $required_fields = [
            'token',
            'language',
            'allowed_languages',
        ];

        foreach ($required_fields as $field) {
            if (!isset($data[$field])) {
                // response with error
                $this->errorJSON('Missing required field: ' . $field, 400);
            }
        }

        $options['token'] = $data['token'];
        $options['default_language'] = $data['language'];
        $options['enabled_languages'] = $data['allowed_languages'];

        $dynamic_translations = $options['dynamic_translations'];
        if (isset($data['dynamic_translations'])) {
            $dynamic_translations['enabled'] = $data['dynamic_translations'] === true ? 1 : 0;
        }
        if (isset($data['public_key'])) {
            $dynamic_translations['public_key'] = $data['public_key'];
        }
        $options['dynamic_translations'] = $dynamic_translations;
        $expert_mode = isset($options['expert_mode']) ? $options['expert_mode'] : [];
        if (isset($data['api_host'])) {
            $expert_mode['api_host'] = $data['api_host'];
            // Extract the port from the host if it is set
            if (strpos($data['api_host'], ':') !== false) {
                $parts = explode(':', $data['api_host']);
                $expert_mode['api_host'] = $parts[0];
                if (isset($parts[1])) {
                    $expert_mode['api_port'] = (int)$parts[1];
                }
            } else {
                $expert_mode['api_port'] = 443; // Default port
            }

            $options['expert_mode'] = $expert_mode;
        }

        $db->saveOtherParam('linguise_options', $options);
        $this->successJSON(true, 'Configuration updated successfully', 200);
    }

    // This is from API -> Website
    public function remoteUpdate()
    {
        // Get X-Linguise-Hash header
        $jwt_token = isset($_SERVER['HTTP_X_LINGUISE_HASH']) ? $_SERVER['HTTP_X_LINGUISE_HASH'] : null;
        if (empty($jwt_token)) {
            $this->errorJSON('Missing Hash header', 400);
        }

        // Read JSON input
        $input_data = file_get_contents('php://input');
        if (empty($input_data)) {
            $this->errorJSON('Invalid request', 400);
        }

        $input_data = json_decode($input_data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->errorJSON('Invalid JSON data', 400);
        }

        // XXX: In WP/Joomla, change with how the get the options
        $db = Database::getInstance()->ensureConnection();
        $options = $db->retrieveOtherParam('linguise_options');

        if ($input_data['token'] !== $options['token']) {
            $this->errorJSON('Invalid token', 401);
        }

        // Verify hash data
        $this->verifyRemoteToken($jwt_token);

        // Hash verified? We replace stuff
        if ($input_data['forced']) {
            $options['token'] = $input_data['token'];
        }

        $options['dynamic_translations']['enabled'] = $input_data['dynamic_translations'] === true ? 1 : 0;
        $options['dynamic_translations']['public_key'] = $input_data['public_key'];
        $options['default_language'] = $input_data['language'];
        $options['enabled_languages'] = $input_data['allowed_languages'];

        if (!empty($input_data['language_settings'])) {
            foreach ($input_data['language_settings'] as $flag_key => $flag_value) {
                $flag_real_key = $flag_key;
                if (isset(self::$flag_options_map[$flag_key])) {
                    $flag_real_key = self::$flag_options_map[$flag_key];
                }
                $options[$flag_real_key] = $this->boolInt($flag_value);
            }
        }

        // Save
        $db->saveOtherParam('linguise_options', $options);
        $this->successJSON(true, 'Configuration updated successfully', 200);
    }

    public function login()
    {
        if (!isset($_POST['_token'])) {
            $message = '<div class="linguise-notification-popup"><span class="material-icons fail">check</span>Missing CSRF token</div>';
            $this->oobeRun($message);
        }
        $sess = Session::getInstance()->start();
        if (!$sess->verifyCsrfToken('linguise_oobe_login', $_POST['_token'])) {
            $message = '<div class="linguise-notification-popup"><span class="material-icons fail">check</span>Invalid CSRF token</div>';
            $this->oobeRun($message);
        }

        // Authenticate ourself, get the token or password
        if ($sess->oobeComplete() && !empty($_POST['password'])) {
            $existing_password = Database::getInstance()->ensureConnection()->retrieveOtherParam('linguise_password');
            if ($existing_password && password_verify($_POST['password'], $existing_password)) {
                // Set session
                $sess->setSession($existing_password, true);
                $this->run(\null, []);
            } else {
                $message = '<div class="linguise-notification-popup"><span class="material-icons fail">check</span>Invalid password</div>';
                $this->oobeRun($message);
            }
        } else {
            $sess->unsetSession();
            $message = '<div class="linguise-notification-popup"><span class="material-icons fail">check</span>Invalid request</div>';
            $this->oobeRun($message);
        }
    }

    public function mergeConfig($skip_missing = \false)
    {
        /**
         * @disregard P1011 - already checked
         */
        $use_mysql = defined('LINGUISE_OOBE_MYSQL') && LINGUISE_OOBE_MYSQL;

        // Each config for the mysql, LINGUISE_OOBE_MYSQL_DB_{HOST|USER|PASSWORD|NAME|PREFIX|PORT}
        $oobe_config = [
            'DB_HOST',
            'DB_USER',
            'DB_PASSWORD',
            'DB_NAME',
            'DB_PREFIX',
            'DB_PORT',
            'DB_FLAGS',
        ];

        if ($use_mysql) {
            foreach ($oobe_config as $config) {
                if (defined('LINGUISE_OOBE_MYSQL_' . $config)) {
                    Configuration::getInstance()->set(strtolower($config), constant('LINGUISE_OOBE_MYSQL_' . $config));
                }
            }
        }

        // Connect to database and install options
        Helper::prepareDataDir();
        $db = Database::getInstance(true);

        if (!LINGUISE_OOBE_DONE) {
            // Not yet ready
            return;
        }

        $db->ensureConnection()->installOptions();

        // Get metadata
        $existing_options = $db->retrieveOtherParam('linguise_options');
        if (!empty($existing_options)) {
            // Merge existing options
            Configuration::getInstance()->set('token', $existing_options['token']);
            Configuration::getInstance()->set('cache_enabled', $existing_options['cache_enabled']);
            Configuration::getInstance()->set('cache_max_size', $existing_options['cache_max_size']);
            Configuration::getInstance()->set('search_translations', $existing_options['search_translations']);
            Configuration::getInstance()->set('debug', $existing_options['debug']);

            foreach ($existing_options['expert_mode'] as $key => $value) {
                Configuration::getInstance()->set($key, $value);
            }
        } else {
            if ($skip_missing) {
                // Skip if missing
                return;
            }

            // We create new options
            $current_token = Configuration::getInstance()->get('token');
            if ($current_token === 'REPLACE_BY_YOUR_TOKEN') {
                $current_token = '';
            }
            $this->createOptionsWithToken($current_token);
        }
    }

    private function oobeRunError($message_str, $status_code = 200)
    {
        $message = '<div class="linguise-notification-popup"><span class="material-icons fail">check</span>' . $message_str . '</div>';
        http_response_code($status_code);
        $this->oobeRun($message);
        die();
    }

    public function activateLinguise()
    {
        // Check if OOBE
        if (defined('LINGUISE_OOBE_DONE') && LINGUISE_OOBE_DONE) {
            $this->oobeRunError('Not allowed', 403);
        } elseif (!defined('LINGUISE_OOBE_DONE')) {
            // Missing data
            $this->oobeRunError('Unknown status', 500);
        }

        if (!isset($_POST['_token'])) {
            $this->oobeRunError('Missing CSRF token', 400);
        }
        $sess = Session::getInstance()->start();
        if (!$sess->verifyCsrfToken('linguise_oobe_register', $_POST['_token'])) {
            $this->oobeRunError('Invalid CSRF token', 403);
        }

        $new_pass = isset($_POST['password']) ? $_POST['password'] : null;
        if (empty($new_pass)) {
            $this->oobeRunError('Missing password', 400);
        }

        // == Password check ==
        // Must be at least 10 characters long
        if (strlen($new_pass) < 10) {
            $this->oobeRunError('Password must be at least 10 characters long', 400);
        }

        if ($sess->hasSession()) {
            $this->oobeRunError('Already activated', 403);
        }

        $is_token = defined('LINGUISE_OOBE_TOKEN_EXIST') && LINGUISE_OOBE_TOKEN_EXIST;
        if ($is_token) {
            if (!isset($_POST['token'])) {
                $this->oobeRunError('Missing token', 400);
            }

            $token = $_POST['token'];
            if ($token !== Configuration::getInstance()->get('token')) {
                $this->oobeRunError('Invalid token provided in session', 401);
            }

            // This will automatically return error response
            $database_store = $this->storeDatabaseConnection(false);
            // set configuration
            if ($database_store['MYSQL']) {
                Configuration::getInstance()->set('db_host', $database_store['MYSQL_DB_HOST']);
                Configuration::getInstance()->set('db_user', $database_store['MYSQL_DB_USER']);
                Configuration::getInstance()->set('db_password', $database_store['MYSQL_DB_PASSWORD']);
                Configuration::getInstance()->set('db_name', $database_store['MYSQL_DB_NAME']);
                Configuration::getInstance()->set('db_prefix', $database_store['MYSQL_DB_PREFIX']);
                Configuration::getInstance()->set('db_port', $database_store['MYSQL_DB_PORT']);
            } else {
                // SQLite
                Configuration::getInstance()->set('db_host', '');
                Configuration::getInstance()->set('db_user', '');
                Configuration::getInstance()->set('db_password', '');
                Configuration::getInstance()->set('db_name', '');
                Configuration::getInstance()->set('db_prefix', '');

                $sqlite_test = $this->prepareRootDatabaseSQLite();
                if ($sqlite_test !== true) {
                    $this->oobeRunError($sqlite_test, 500);
                }
            }

            // Init database
            Helper::prepareDataDir();
            $db = Database::getInstance(true, true)->ensureConnection();
            $db->installOptions();

            // Valid, then let's set the password
            $hashed_pass = $this->hashPassword($new_pass);
            $db->saveOtherParam('linguise_password', $hashed_pass);
            // Create the options
            $db_options = $this->createOptionsWithToken($token);

            // Write OOBE config
            $this->writeOOBE($database_store);

            $api_result = $this->getRemoteData($token);
            if ($api_result !== false && isset($api_result['data'])) {
                $default_language = Helper::sanitizeKey($api_result['data']['language']);

                $translation_languages = $api_result['data']['languages'];
                $translate_languages = [];
                if (!empty($translation_languages)) {
                    foreach ($translation_languages as $translation_language) {
                        $translate_languages[] = Helper::sanitizeKey($translation_language['code']);
                    }
                }

                $db_options['enabled_languages'] = $translate_languages;
                $db_options['default_language'] = $default_language;
                $db_options['dynamic_translations']['enabled'] = $this->intBool($api_result['data']['dynamic_translations']['enabled']);
                $db_options['dynamic_translations']['public_key'] = $api_result['data']['public_key'];

                if (!empty($api_result['data']['language_settings'])) {
                    foreach ($api_result['data']['language_settings'] as $flag_key => $flag_value) {
                        $flag_real_key = $flag_key;
                        if (isset(self::$flag_options_map[$flag_key])) {
                            $flag_real_key = self::$flag_options_map[$flag_key];
                        }
                        $db_options[$flag_real_key] = $this->boolInt($flag_value);
                    }
                }

                // Re-save with API data
                $db->saveOtherParam('linguise_options', $db_options);
            }

            // Set session, and login the user.
            $sess->setOobeForced();
            $sess->setSession($hashed_pass, true); // Set session with password mode
            $this->run();
            die();
        } else {
            // This will automatically return error response
            $database_store = $this->storeDatabaseConnection(false);
            // set configuration
            if ($database_store['MYSQL']) {
                Configuration::getInstance()->set('db_host', $database_store['MYSQL_DB_HOST']);
                Configuration::getInstance()->set('db_user', $database_store['MYSQL_DB_USER']);
                Configuration::getInstance()->set('db_password', $database_store['MYSQL_DB_PASSWORD']);
                Configuration::getInstance()->set('db_name', $database_store['MYSQL_DB_NAME']);
                Configuration::getInstance()->set('db_prefix', $database_store['MYSQL_DB_PREFIX']);
                Configuration::getInstance()->set('db_port', $database_store['MYSQL_DB_PORT']);
            } else {
                // SQLite
                Configuration::getInstance()->set('db_host', '');
                Configuration::getInstance()->set('db_user', '');
                Configuration::getInstance()->set('db_password', '');
                Configuration::getInstance()->set('db_name', '');
                Configuration::getInstance()->set('db_prefix', '');

                $sqlite_test = $this->prepareRootDatabaseSQLite();
                if ($sqlite_test !== true) {
                    $this->oobeRunError($sqlite_test, 500);
                }
            }

            // Init database
            Helper::prepareDataDir();
            $db = Database::getInstance(true, true)->ensureConnection();
            $db->installOptions();

            // No token, we just set it immediately
            $existing_password = $db->retrieveOtherParam('linguise_password');
            if ($existing_password) {
                $this->oobeRunError('Password already set', 400);
            }

            // Set the password
            $hashed_pass = $this->hashPassword($new_pass);

            // Create
            $db->saveOtherParam('linguise_password', $hashed_pass);
            // Create the options
            $this->createOptionsWithToken(''); // Empty token since no token provided yet.

            // Write OOBE config
            $this->writeOOBE($database_store);


            // Set session, and login the user.
            $sess->setOobeForced();
            $sess->setSession($hashed_pass, true); // Set session with password mode
            $this->run();
            die();
        }
    }

    public function storeDatabaseConnection($testMode = \false)
    {
        if ($testMode) {
            if (!isset($_POST['_token'])) {
                $this->errorJSON('Missing CSRF token', 400);
            }
            $sess = Session::getInstance()->start();
            if (!$sess->verifyCsrfToken('linguise_oobe_register', $_POST['_token'])) {
                $this->errorJSON('Invalid CSRF token', 403);
            }
        }

        // Get from POST data
        $mode = $_POST['db_mode'] ?? null;
        $host = $_POST['db_host'] ?? null;
        $user = $_POST['db_user'] ?? null;
        $password = $_POST['db_password'] ?? null;
        $name = $_POST['db_name'] ?? null;
        $port = $_POST['db_port'] ?? 3306;
        $prefix = $_POST['db_prefix'] ?? null;

        if (empty($mode)) {
            $this->errorJSON('Missing `db_mode` data', 400);
        }

        switch ($mode) {
            case 'mysql':
                if (empty($host) || empty($user) || empty($name)) {
                    $this->errorJSON('Missing `db_host`, `db_user` or `db_name` data', 400);
                }
                if (!$testMode && (empty($prefix))) {
                    $this->errorJSON('Missing `db_prefix` data', 400);
                }

                if ($testMode) {
                    $result = $this->testMySQL($host, $user, $password, $name, $port, $prefix);
                    if ($result !== true) {
                        $this->errorJSON($result, 500);
                    }
                    $this->successJSON(true, 'MySQL connection test successful', 200);
                }

                return [
                    'MYSQL' => true,
                    'MYSQL_DB_HOST' => $host,
                    'MYSQL_DB_USER' => $user,
                    'MYSQL_DB_PASSWORD' => $password,
                    'MYSQL_DB_NAME' => $name,
                    'MYSQL_DB_PORT' => $port,
                    'MYSQL_DB_PREFIX' => $prefix,
                ];
            case 'sqlite':
                // Check if SQLite3 is enabled
                if ($testMode) {
                    $result = $this->testSqlite();
                    if ($result !== true) {
                        $this->errorJSON($result, 500);
                    }
                    $this->successJSON(true, 'SQLite connection test successful', 200);
                }

                // Store the SQLite connection
                return [
                    'MYSQL' => false,
                ];
            default:
                $this->errorJSON('Invalid `db_mode` data', 400);
        }
    }

    private function testMySQL($host, $user, $password, $name, $port = 3306, $prefix = '')
    {
        // Check if MySQLi is enabled
        if (!extension_loaded('mysqli')) {
            return 'MySQLi extension not loaded';
        }

        // Check if MySQLi class exist
        if (!class_exists('mysqli')) {
            return 'MySQLi class not found';
        }

        // Attempt to connect to the database
        $connection = \null;
        try {
            $connection = new \mysqli($host, $user, $password, $name, $port);
        } catch (\mysqli_sql_exception $e) {
            return 'Connection failed: ' . $e->getMessage();
        }

        if (empty($connection)) {
            return 'Connection failed: No connection object created';
        }

        if ($connection->connect_error) {
            return 'Connection failed: ' . $connection->connect_error;
        }

        // Close the connection
        $connection->close();

        return true;
    }

    private function testSqlite()
    {
        // Check if SQLite3 is enabled
        if (!extension_loaded('sqlite3')) {
            return 'SQLite3 extension not loaded';
        }

        // Check if SQLite3 class exist
        if (!class_exists('SQLite3')) {
            return 'SQLite3 class not found';
        }

        // Check if we can write in LINGUISE_BASE_DIR / .linguise-main.db
        $sqlite_test = $this->prepareRootDatabaseSQLite();
        if ($sqlite_test !== true) {
            return $sqlite_test;
        }

        if (!Helper::checkDataDirAvailable()) {
            return 'Cannot write to data directory';
        }

        return true;
    }

    private function prepareRootDatabaseSQLite()
    {
        $databases_dir = LINGUISE_BASE_DIR . '.databases' . DIRECTORY_SEPARATOR;
        if (!file_exists($databases_dir)) {
            if (!mkdir($databases_dir, 0766, true)) {
                return 'Cannot create database directory: ' . $databases_dir;
            }
        }

        $htaccess_file = $databases_dir . '.htaccess';
        if (!file_exists($htaccess_file)) {
            $written = file_put_contents($htaccess_file, 'deny from all');
            if ($written === false) {
                return 'Cannot write to database directory: ' . $databases_dir;
            }
        }

        $db_path = $databases_dir . 'linguise-main.db';
        if (!file_exists($db_path)) {
            // touch the file
            $db_touch = touch($db_path);
            if (!$db_touch) {
                return 'Cannot create database file: ' . $db_path;
            }
            unlink($db_path);
        } else {
            if (!is_writable($db_path)) {
                return 'Cannot write database to ' . $db_path;
            }
        }

        return true;
    }

    private function createOptionsWithToken($token)
    {
        $db = Database::getInstance()->ensureConnection();
        $options = [
            'token' => $token,
            'cache_enabled' => Configuration::getInstance()->get('cache_enabled'),
            'cache_max_size' => Configuration::getInstance()->get('cache_max_size'),
            'search_translations' => Configuration::getInstance()->get('search_translations'),
            'debug' => Configuration::getInstance()->get('debug'),
            'dynamic_translations' => [
                'enabled' => 0,
                'public_key' => null,
            ],

            // Languages related
            'default_language' => 'en',
            'enabled_languages' => [],

            // Flag related
            'display_position' => 'bottom_right',
            'flag_display_type' => 'popup',
            'enable_flag' => 1,
            'enable_language_name' => 1,
            'enable_language_short_name' => 0,
            'language_name_display' => 'en',
            'flag_shape' => 'round',
            'flag_en_type' => 'en-us',
            'flag_de_type' => 'de',
            'flag_es_type' => 'es',
            'flag_pt_type' => 'pt',
            'flag_tw_type' => 'zh-tw',
            'flag_border_radius' => 0,
            'flag_width' => 24,
            'pre_text' => '',
            'post_text' => '',
            'custom_css' => '',
            'language_name_color' => '#222',
            'language_name_hover_color' => '#222',
            'popup_language_name_color' => '#222',
            'popup_language_name_hover_color' => '#222',
            'flag_shadow_h' => 2,
            'flag_shadow_v' => 2,
            'flag_shadow_blur' => 12,
            'flag_shadow_spread' => 0,
            'flag_shadow_color' => '#eee',
            'flag_shadow_color_alpha' => (float)1.0, // we use 100% scaling, 0.0-1.0
            'flag_hover_shadow_h' => 3,
            'flag_hover_shadow_v' => 3,
            'flag_hover_shadow_blur' => 6,
            'flag_hover_shadow_spread' => 0,
            'flag_hover_shadow_color' => '#bfbfbf',
            'flag_hover_shadow_color_alpha' => (float)1.0,

            'expert_mode' => [],
        ];

        $db->saveOtherParam('linguise_options', $options);

        return $options;
    }

    private function errorJSON($message, $code = 500)
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($code);
        echo json_encode([
            'error' => true,
            'message' => $message
        ]);
        exit;
    }

    private function successJSON($data, $message = '', $code = 200)
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($code);
        echo json_encode([
            'error' => false,
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }

    private function hashPassword($input)
    {
        $hashed = password_hash($input, PASSWORD_BCRYPT, [
            'cost' => 12,
        ]);

        return $hashed;
    }

    private function writeOOBE($database_store)
    {
        // Modify OOBE status in ui-config.php
        $ui_config = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..') . DIRECTORY_SEPARATOR . 'ui-config.php';
        $content = file_get_contents($ui_config);

        $replaced_content = preg_replace('/define\([\'"]LINGUISE_OOBE_DONE[\'"], .*\);/m', 'define(\'LINGUISE_OOBE_DONE\', true);', $content);
        if (empty($replaced_content)) {
            $this->errorJSON('Failed to update ui-config.php', 500);
        }

        // Update the database connection
        foreach ($database_store as $key => $value) {
            // Push content
            $value_wrap = json_encode($value);
            $replaced_content .= "\ndefine('LINGUISE_OOBE_" . $key . "', " . $value_wrap . ");\n";
        }

        file_put_contents($ui_config, $replaced_content);
    }

    public function clearDebug()
    {
        // Verify session
        $sess = Session::getInstance()->start();
        if (!$sess->hasSession()) {
            $this->errorJSON('Unauthorized', 401);
        }
        if (!isset($_GET['nonce'])) {
            $this->errorJSON('Missing nonce token', 400);
        }
        if (!$sess->verifyCsrfToken('linguise_clear_debug', $_GET['nonce'])) {
            $this->errorJSON('Invalid nonce token', 403);
        }

        // Clear the debug file
        $debug_file = LINGUISE_BASE_DIR . 'debug.php';
        $last_errors_file = LINGUISE_BASE_DIR . 'errors.php';
        if (file_exists($debug_file)) {
            file_put_contents($debug_file, "<?php die(); ?>" . PHP_EOL);
        }
        if (file_exists($last_errors_file)) {
            file_put_contents($last_errors_file, "<?php die(); ?>" . PHP_EOL);
        }

        $this->successJSON(true, 'Log truncated!', 200);
    }

    public function clearCache()
    {
        // Verify session
        $sess = Session::getInstance()->start();
        if (!$sess->hasSession()) {
            $this->errorJSON('Unauthorized', 401);
        }
        if (!isset($_GET['nonce'])) {
            $this->errorJSON('Missing nonce token', 400);
        }
        if (!$sess->verifyCsrfToken('linguise_clear_cache', $_GET['nonce'])) {
            $this->errorJSON('Invalid nonce token', 403);
        }

        // Clear the cache
        Cache::getInstance()->clearAll();
    }

    private function downloadDebug()
    {
        // Verify session
        $sess = Session::getInstance()->start();
        if (!$sess->hasSession()) {
            die('Unauthorized');
        }

        $debug_file = LINGUISE_BASE_DIR . 'debug.php';
        if (file_exists($debug_file)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="debug.txt"');
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . filesize($debug_file));
            ob_clean();
            ob_end_flush();
            $handle = fopen($debug_file, 'rb');
            while (!feof($handle)) {
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo fread($handle, 1000);
            }

            die();
        } else {
            die('No debug file found');
        }
    }

    private function defineConstants($is_logged_in = false)
    {
        if (!defined('LINGUISE_MANAGEMENT')) {
            define('LINGUISE_MANAGEMENT', 1);
        }

        if ($is_logged_in && !defined('LINGUISE_AUTHORIZED')) {
            define('LINGUISE_AUTHORIZED', 1);
        }

        // Silent debug noises
        $request = Request::getInstance(true);
        $base_dir = rtrim($request->getBaseDir(), '/');
        if (!defined('LINGUISE_BASE_URL')) {
            define('LINGUISE_BASE_URL', $base_dir . '/linguise');
        }
    }

    /**
     * Verify the hash token from the server
     *
     * This will ensure the request comes from the Linguise server and is valid.
     *
     * @param string $token The token to verify
     * @param string $jwt_token The JWT token to verify
     *
     * @return boolean True if the token is valid, false otherwise
     */
    public function verifyRemoteToken($jwt_token, $api_url = null)
    {
        if (empty($api_url)) {
            // Sync verify is api.linguise.com/api/sync/verify
            $api_url = $this->getApiRoot() . '/api/sync/verify';
        }

        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // We're sending back $jwt_token as JSON content
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'token' => $jwt_token,
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
        ]);
        if (strpos($api_url, 'https://') === 0) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            if (Configuration::getInstance()->get('dl_certificates') === true) {
                curl_setopt($ch, CURLOPT_CAINFO, Certificates::getInstance()->getPath());
            }
        }

        curl_exec($ch);
        $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // log error to apache error
        curl_close($ch);

        if ($response_code !== 200) {
            $this->errorJSON('Invalid JWT verification token: ' . print_r($response_code, true), 403);
        }

        // Nothing goes wrong, we can just return
        return true;
    }

    private function getRemoteData($token)
    {
        // Config get is api.linguise.com/api/config
        $api_url = $this->getApiRoot() . '/api/config';

        $headers = [
            'Referer: ' . Request::getInstance(true)->getBaseUrl(),
            'Authorization: ' . $token,
        ];

        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        if (strpos($api_url, 'https://') === 0) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            if (Configuration::getInstance()->get('dl_certificates') === true) {
                curl_setopt($ch, CURLOPT_CAINFO, Certificates::getInstance()->getPath());
            }
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

        // request
        $response = curl_exec($ch);
        $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response_code !== 200) {
            return false;
        }
        if (empty($response)) {
            return false;
        }

        $response = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        return $response;
    }

    /**
     * Push the data to the Linguise server for storing and syncing
     *
     * @param array  $data  The data to push
     * @param string $token The token to use for authentication
     * @param string|null $api_url The URL to push the data to
     *
     * @return array|false The response from the server, or false on failure
     */
    public function pushRemoteSync($data, $token, $api_url = null)
    {
        if (empty($api_url)) {
            // Push remote sync is api.linguise.com/api/sync/domain
            $api_url = $this->getApiRoot() . '/api/sync/domain';
        }

        $headers = [
            'Authorization: ' . $token,
            'Referer: ' . Request::getInstance(true)->getBaseUrl(),
            'Content-Type: application/json',
        ];

        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        if (strpos($api_url, 'https://') === 0) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            if (Configuration::getInstance()->get('dl_certificates') === true) {
                curl_setopt($ch, CURLOPT_CAINFO, Certificates::getInstance()->getPath());
            }
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

        // request
        $response = curl_exec($ch);
        $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response_code !== 200) {
            return false;
        }
        if (empty($response)) {
            return false;
        }

        $response = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        return $response;
    }

    public function logout()
    {
        // Verify session
        $sess = Session::getInstance()->start();
        // Verify session
        if ($sess->hasSession()) {
            // Unset session
            $sess->unsetSession();
            $this->successJSON(true, 'Logout successful', 200);
        } else {
            // No session, we just redirect to login page
            $this->successJSON(true, 'No session found', 200);
            exit;
        }
    }

    private function getApiRoot()
    {
        $db = Database::getInstance()->ensureConnection();
        $options = $db->retrieveOtherParam('linguise_options');
        $api_port_base = [443, 80];
        if (isset($options['expert_mode'])) {
            $api_host = isset($options['expert_mode']['api_host']) ? $options['expert_mode']['api_host'] : null;
            $api_port = isset($options['expert_mode']['api_port']) ? (int)$options['expert_mode']['api_port'] : 443;
            $protocol = $api_port === 443 ? 'https' : 'http';

            if (!empty($api_host)) {
                return $protocol . '://' . $api_host . (in_array($api_port, $api_port_base) ? '' : ':' . $api_port) . '';
            }
        }

        $api_host = Configuration::getInstance()->get('api_host') ?? 'api.linguise.com';
        $api_port = (int)Configuration::getInstance()->get('api_port') ?? 443;
        $protocol = $api_port === 443 ? 'https' : 'http';
        return $protocol . '://' . $api_host . (in_array($api_port, $api_port_base) ? '' : ':' . $api_port) . '';
    }

    public function rejectGET()
    {
        header('Content-Type: text/html; charset=utf-8');
        http_response_code(403);
        echo '<h1>403 Forbidden</h1>';
        echo '<p>You are not allowed to access this page.</p>';
        die();
    }

    private function unknownGETAction()
    {
        header('Content-Type: text/html; charset=utf-8');
        http_response_code(400);
        echo '<h1>400 Bad Request</h1>';
        echo '<p>Unknown action.</p>';
        die();
    }

    /**
     * Convert a boolean value to an integer (0 or 1)
     *
     * @param mixed $value The value to convert
     *
     * @return mixed The converted value (0 or 1), or original
     */
    private function boolInt($value)
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        return $value;
    }

    /**
     * Convert an integer (0 or 1) to a boolean value
     *
     * @param mixed $value The value to convert
     *
     * @return mixed The converted value (true or false), or original
     */
    private function intBool($value)
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value)) {
            return $value === 1;
        }

        return $value;
    }
}
