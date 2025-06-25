<?php

defined('LINGUISE_MANAGEMENT') or die('No access to this page.');

// Stubs
function __($input, $domain = null) {
    return $input;
}
function esc_html($input) {
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}
function esc_attr($input) {
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}
function esc_url($input) {
    return str_replace('&amp;', '&', htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
}

?>