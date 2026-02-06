<?php

add_action('wp_ajax_linguise_update_config_iframe', function () {
    // nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'linguise_update_config_iframe')) {
        // response with error
        wp_send_json_error(array(
            'message' => __('Nonce verification failed', 'linguise')
        ));
        return;
    }

    // check if user is logged in
    if (!is_user_logged_in()) {
        // response with error
        wp_send_json_error(array(
            'message' => __('User is not logged in', 'linguise')
        ));
        return;
    }
    // check if user has permission to manage options
    if (!current_user_can('manage_options')) {
        // response with error
        wp_send_json_error(array(
            'message' => __('User does not have permission to manage options', 'linguise')
        ));
        return;
    }

    // time to update the data, data is nested in $_POST['data']
    if (!isset($_POST['config'])) {
        // response with error
        wp_send_json_error(array(
            'message' => __('No data to update', 'linguise')
        ));
        return;
    }

    $data = $_POST['config'];
    $options = linguiseGetOptions();

    // Check if all required fields are present
    $required_fields = [
        'token',
        'language',
        'allowed_languages',
    ];

    foreach ($required_fields as $field) {
        if (!isset($data[$field])) {
            // response with error
            wp_send_json_error(array(
                'message' => sprintf(
                    /* translators: %s: Missing required field name */
                    __('Missing required field: %s', 'linguise'),
                    esc_html($field)
                )
            ));
            return;
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
    $expert_mode = isset($data['expert_mode']) ? $data['expert_mode'] : [];
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
    $options['dynamic_translations'] = $dynamic_translations;

    // save the options
    linguiseSwitchMainSite();
    update_option('linguise_options', $options);
    linguiseRestoreMultisite();
    wp_send_json_success(array(
        'message' => __('Linguise settings saved!', 'linguise')
    ));
});

add_action('wp_ajax_linguise_get_headers', function () {
    // nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'linguise_update_config_iframe')) {
        // set header
        wp_die(esc_html__('Nonce verification failed', 'linguise'), esc_html__('Unauthorized', 'linguise'), array(
            'response' => 403,
            'back_link' => true,
        ));
        return;
    }

    // check if user is logged in
    if (!is_user_logged_in()) {
        // response with error
        wp_die(esc_html__('User is not logged in', 'linguise'), esc_html__('Unauthorized', 'linguise'), array(
            'response' => 403,
            'back_link' => true,
        ));
        return;
    }
    // check if user has permission to manage options
    if (!current_user_can('manage_options')) {
        // response with error
        wp_die(esc_html__('User does not have permission to manage options', 'linguise'), esc_html__('Unauthorized', 'linguise'), array(
            'response' => 403,
            'back_link' => true,
        ));
        return;
    }

    // Dump request headers
    $headers = getallheaders();
    if ($headers === false) {
        $headers = [];
    }

    $headers_line_by_line = [];
    foreach ($headers as $key => $value) {
        $headers_line_by_line[] = sprintf('%s: %s', $key, $value);
    }
    $headers_line_by_line = implode("\n", $headers_line_by_line);
    wp_die(esc_html($headers_line_by_line), esc_html__('Request Headers', 'linguise'), array(
        'response' => 200,
        'back_link' => false,
    ));
});
