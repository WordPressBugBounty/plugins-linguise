<?php

use Linguise\Vendor\Linguise\Script\Core\Configuration;
use Linguise\Vendor\Linguise\Script\Core\Cache;
use Linguise\Vendor\Linguise\Script\Core\Database;

defined('ABSPATH') || die('');

add_action('wp_ajax_linguise_clear_cache', function () {
    check_admin_referer('_linguise_nonce_');
    // check user capabilities
    if (!current_user_can('manage_options')) {
        header('Content-Type: application/json; charset=UTF-8;');
        echo json_encode(['success' => false]);
        die();
    }

    ini_set('display_errors', false);

    if (!defined('LINGUISE_SCRIPT_TRANSLATION')) {
        define('LINGUISE_SCRIPT_TRANSLATION', true);
    }

    require_once(LINGUISE_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');
    // Set token to get correct data_dir
    Configuration::getInstance()->set('cms', 'wordpress');
    Configuration::getInstance()->set('base_dir', realpath(LINGUISE_PLUGIN_PATH . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR  . '..' . DIRECTORY_SEPARATOR  . '..') . DIRECTORY_SEPARATOR);

    $token = Database::getInstance()->retrieveWordpressOption('token', $_SERVER['HTTP_HOST']);
    Configuration::getInstance()->set('token', $token);
    Configuration::getInstance()->set('data_dir', wp_upload_dir()['basedir'] . DIRECTORY_SEPARATOR . 'linguise'. DIRECTORY_SEPARATOR . md5('data' . $token));

    Cache::getInstance()->clearAll();
});
