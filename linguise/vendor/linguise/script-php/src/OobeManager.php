<?php

namespace Linguise\Vendor\Linguise\Script\Core;

defined('LINGUISE_SCRIPT_TRANSLATION') or die();

use Linguise\Vendor\Linguise\Script\Core\Helper;

class OobeManager {
    /**
     * @var null|OobeManager
     */
    private static $_instance = null;

    /**
     * @var string
     */
    private static $token_key = 'linguise_token';

    private function __construct()
    {
        // Private constructor to prevent instantiation
    }

    /**
     * Get the singleton instance of the OobeManager class
     *
     * @return OobeManager
     */
    public static function getInstance()
    {
        if (is_null(self::$_instance)) {
            // Create a new instance if it doesn't exist
            self::$_instance = new OobeManager();
        }
        return self::$_instance;
    }

    public function oobeRun($html_message = \null) {
        // Start session
        $sess = Session::getInstance()->start();
        // Set our CSRF token, always overrides
        $sess->generateCsrfToken();

        Helper::defineConstants(false);

        require_once LINGUISE_BASE_DIR . 'src' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'stubs.php';
        require_once LINGUISE_BASE_DIR . 'src' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
        if (!empty($html_message)) {
            echo $html_message;
        }
        require_once LINGUISE_BASE_DIR . 'src' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'oobe.php';
        require_once LINGUISE_BASE_DIR . 'src' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';
    }

    private function writeOOBE($database_store)
    {
        // Modify OOBE status in ui-config.php
        $ui_config = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..') . DIRECTORY_SEPARATOR . 'ui-config.php';
        $content = file_get_contents($ui_config);

        $replaced_content = preg_replace('/define\([\'"]LINGUISE_OOBE_DONE[\'"], .*\);/m', 'define(\'LINGUISE_OOBE_DONE\', true);', $content);
        if (empty($replaced_content)) {
            HttpResponse::errorJSON('Failed to update ui-config.php', 500);
        }

        // Update the database connection
        foreach ($database_store as $key => $value) {
            // Push content
            $value_wrap = json_encode($value);
            $replaced_content .= "\ndefine('LINGUISE_OOBE_" . $key . "', " . $value_wrap . ");\n";
        }

        file_put_contents($ui_config, $replaced_content);
    }

    private function oobeRunError($message_str, $status_code = 200)
    {
        $message = '<div class="linguise-notification-popup"><span class="material-icons fail">check</span>' . $message_str . '</div>';
        http_response_code($status_code);
        $this->oobeRun($message);
        die();
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

    private function hashPassword($input)
    {
        $hashed = password_hash($input, PASSWORD_BCRYPT, [
            'cost' => 12,
        ]);

        return $hashed;
    }

    public function storeDatabaseConnection($testMode = \false)
    {
        if ($testMode) {
            if (!isset($_POST['_token'])) {
                HttpResponse::errorJSON('Missing CSRF token', 400);
            }
            $sess = Session::getInstance()->start();
            if (!$sess->verifyCsrfToken('linguise_oobe_register', $_POST['_token'])) {
                HttpResponse::errorJSON('Invalid CSRF token', 403);
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
            HttpResponse::errorJSON('Missing `db_mode` data', 400);
        }

        switch ($mode) {
            case 'mysql':
                if (empty($host) || empty($user) || empty($name)) {
                    HttpResponse::errorJSON('Missing `db_host`, `db_user` or `db_name` data', 400);
                }
                if (!$testMode && (empty($prefix))) {
                    HttpResponse::errorJSON('Missing `db_prefix` data', 400);
                }

                if ($testMode) {
                    $result = $this->testMySQL($host, $user, $password, $name, $port, $prefix);
                    if ($result !== true) {
                        HttpResponse::errorJSON($result, 500);
                    }
                    HttpResponse::successJSON(true, 'MySQL connection test successful', 200);
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
                        HttpResponse::errorJSON($result, 500);
                    }
                    HttpResponse::successJSON(true, 'SQLite connection test successful', 200);
                }

                // Store the SQLite connection
                return [
                    'MYSQL' => false,
                ];
            default:
                HttpResponse::errorJSON('Invalid `db_mode` data', 400);
        }
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

            $management = Management::getInstance();
            $api_result = $management->getRemoteData($token);
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
                $db_options['dynamic_translations']['enabled'] = Helper::intBool($api_result['data']['dynamic_translations']['enabled']);
                $db_options['dynamic_translations']['public_key'] = $api_result['data']['public_key'];

                if (!empty($api_result['data']['language_settings'])) {
                    foreach ($api_result['data']['language_settings'] as $flag_key => $flag_value) {
                        $flag_real_key = $flag_key;
                        if (isset(Management::$flag_options_map[$flag_key])) {
                            $flag_real_key = Management::$flag_options_map[$flag_key];
                        }
                        $db_options[$flag_real_key] = Helper::boolInt($flag_value);
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

    public function run($html_message = \null, $api_web_errors = []) {
        // Start session
        $sess = Session::getInstance()->start();
        // Set our CSRF token, always overrides
        $sess->generateCsrfToken();

        // We define this constant so user can't do direct access to the template
        Helper::defineConstants(false);

        if (isset($_GET['linguise_action'])) {
            switch ($_GET['linguise_action']) {
                case 'download-debug':
                    $this->downloadDebug();
                    break;
                case 'update-config':
                    break;
                default:
                    if (empty($_SESSION[self::$token_key])) {
                        HttpResponse::rejectGET();
                    } else {
                        HttpResponse::unknownGETAction();
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
            Helper::defineConstants(true);
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

    public function logout()
    {
        // Verify session
        $sess = Session::getInstance()->start();
        // Verify session
        if ($sess->hasSession()) {
            // Unset session
            $sess->unsetSession();
            HttpResponse::successJSON(true, 'Logout successful', 200);
        } else {
            // No session, we just redirect to login page
            HttpResponse::successJSON(true, 'No session found', 200);
            exit;
        }
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

    public function clearDebug()
    {
        // Verify session
        $sess = Session::getInstance()->start();
        if (!$sess->hasSession()) {
            HttpResponse::errorJSON('Unauthorized', 401);
        }
        if (!isset($_GET['nonce'])) {
            HttpResponse::errorJSON('Missing nonce token', 400);
        }
        if (!$sess->verifyCsrfToken('linguise_clear_debug', $_GET['nonce'])) {
            HttpResponse::errorJSON('Invalid nonce token', 403);
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

        HttpResponse::successJSON(true, 'Log truncated!', 200);
    }

    public function clearCache()
    {
        // Verify session
        $sess = Session::getInstance()->start();
        if (!$sess->hasSession()) {
            HttpResponse::errorJSON('Unauthorized', 401);
        }
        if (!isset($_GET['nonce'])) {
            HttpResponse::errorJSON('Missing nonce token', 400);
        }
        if (!$sess->verifyCsrfToken('linguise_clear_cache', $_GET['nonce'])) {
            HttpResponse::errorJSON('Invalid nonce token', 403);
        }

        // Clear the cache
        Cache::getInstance()->clearAll();
    }
}