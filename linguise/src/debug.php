<?php
defined('ABSPATH') || die('');

add_action('wp_ajax_linguise_download_debug', function () {
    // check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }

    $debug_file = LINGUISE_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'linguise' . DIRECTORY_SEPARATOR . 'script-php' . DIRECTORY_SEPARATOR . 'debug.php';
    if (!file_exists($debug_file)) {
        wp_die('No debug file found');
    }

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
        while (! feof($handle)) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo fread($handle, 1000);
        }
        die();
    }
});

add_action('wp_ajax_linguise_truncate_debug', function () {
    // check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }

    check_admin_referer('_linguise_nonce_');

    $log_path = LINGUISE_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'linguise' . DIRECTORY_SEPARATOR . 'script-php' . DIRECTORY_SEPARATOR;
    $full_debug_file =  $log_path . 'debug.php';
    $last_errors_file = $log_path . 'errors.php';

    if (file_exists($full_debug_file)) {
        file_put_contents($full_debug_file, '<?php die(); ?>' . PHP_EOL);
    }

    if (file_exists($last_errors_file)) {
        file_put_contents($last_errors_file, '<?php die(); ?>' . PHP_EOL);
    }

    wp_send_json_success('Log truncated!');
});

add_action('wp_ajax_linguise_disable_debug', function () {
    if (!current_user_can('manage_options')) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false]);
        die();
    }

    check_admin_referer('_linguise_nonce_');

    $options = linguiseGetOptions();
    $options['debug'] = false;
    update_option('linguise_options', $options);
    wp_send_json_success('Debug disabled!');
});
