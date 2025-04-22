<?php

defined('ABSPATH') || die('No direct access allowed!');

include_once ABSPATH . 'wp-admin/includes/plugin.php';

if (!is_plugin_active('woo-custom-product-addons/start.php')) {
    return;
}

add_filter('linguise_fragment_attributes', function ($fragments, $html_data) {
    // Check if there is data-wcpa attribute in the HTML data
    if (preg_match('/data-wcpa=(["\'])([^"]+)\1/', $html_data, $matches)) {
        // Get the value of the data-wcpa attribute
        $wcpa = $matches[1];

        // Check if the value is not empty
        if (!empty($wcpa)) {
            // Add the data-wcpa attribute to the fragments array
            $fragments[] = [
                'name' => 'wcpa-data-attrs',
                'key' => 'data-wcpa',
            ];
        }
    }

    return $fragments;
}, 100, 2);

add_filter('linguise_fragment_override', function ($fragments) {
    $fragments[] = [
        'name' => 'wcpa-context',
        'match' => 'var wcpa_front = (.*?);',
        'replacement' => 'var wcpa_front = $$JSON_DATA$$;',
    ];
    return $fragments;
}, 100);

add_filter('linguise_fragment_filters', function ($filters) {
    $key_exact_ignore = [
        'assets_url',
        'api_nonce',
        'root',
        'date_format',
        'ajax_add_to_cart',
        'time_format',
        'className',
        'tempId',
        'elementId',
    ];
    foreach ($key_exact_ignore as $key) {
        $filters[] = [
            'key' => $key,
            'mode' => 'exact',
            'kind' => 'deny',
        ];
    }

    $regex_full = [
        '^init_triggers\.*',
        '^design\.*',
        'form_rules\.*',
    ];
    foreach ($regex_full as $key) {
        $filters[] = [
            'key' => $key,
            'mode' => 'regex_full',
            'kind' => 'deny',
        ];
    }
    $regex_fields = [
        'name',
        'value',
        'type',
        'elementId',
        'cl_rule',
    ];
    foreach ($regex_fields as $key) {
        $filters[] = [
            'key' => '^fields\.([\w\d_-]+)\.fields\.(\d+)\.(\d+)\.' . $key,
            'mode' => 'regex',
            'kind' => 'deny',
        ];
    }

    return $filters;
}, 100, 1);
