<?php

/**
 * Compatibility for Plugin: Make Column Clickable Elementor
 * Addon for Elementor
 *
 * @link https://wordpress.org/plugins/make-column-clickable-elementor/
 */

$linguise_options = linguiseGetOptions();

if (!empty($_SERVER['HTTP_LINGUISE_ORIGINAL_LANGUAGE']) && $_SERVER['HTTP_LINGUISE_ORIGINAL_LANGUAGE'] !== $linguise_options['default_language'] && in_array($_SERVER['HTTP_LINGUISE_ORIGINAL_LANGUAGE'], $linguise_options['enabled_languages'])) {
    add_filter('wpml_translate_single_string', function ($source_link, $domain, $original_string) {
        $language = $_SERVER['HTTP_LINGUISE_ORIGINAL_LANGUAGE'];
        $source_path = rtrim(str_replace(wp_parse_url(site_url(), PHP_URL_PATH), '', parse_url($source_link, PHP_URL_PATH)), '/');

        global $wpdb;
        $prefix = $wpdb->prefix;
        $results = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT translation
                  FROM ' . $prefix . 'linguise_urls
                  WHERE source = %s
                  AND language = %s
                  LIMIT 1',
                $source_path,
                $language
            )
        );

        if ($results) {
            $source_link = esc_url(site_url() . $results[0]->translation);
        }

        return $source_link;
    }, 10, 3);
}
