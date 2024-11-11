<?php
/**
 * Fix: Redirect into original page after place booking
 */

if (!is_plugin_active('bookingpress-appointment-booking/bookingpress-appointment-booking.php')) {
    return;
}

use Linguise\WordPress\Helper;
use Linguise\Vendor\Linguise\Script\Core\Request;
use Linguise\Vendor\Linguise\Script\Core\Url;

/**
 * Let Linguise\Vendor\Linguise\Script\Core\Url to translate the redirect URL
 * We only set the protected property, if we not set the property it would be null
 * Because this method executed via wp-admin/admin-ajax.php
 *
 * @param string $linguise_language Linguise language
 * @param string $url               The string URL
 *
 * @return string
 */
function linguise_bookingpress_translate_url($linguise_language, $url)
{
    $hostname = $_SERVER['HTTP_HOST'];
    $protocol = (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' || !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] === 443) ? 'https' : 'http';

    $request = Request::getInstance();
    $language_reflection = new \ReflectionProperty(get_class($request), 'language');
    $language_reflection->setAccessible(true);
    $language_reflection->setValue($request, $linguise_language);

    $hostname_reflection = new \ReflectionProperty(get_class($request), 'hostname');
    $hostname_reflection->setAccessible(true);
    $hostname_reflection->setValue($request, $hostname);

    $protocol_reflection = new \ReflectionProperty(get_class($request), 'protocol');
    $protocol_reflection->setAccessible(true);
    $protocol_reflection->setValue($request, $protocol);

    // Let Linguise\Vendor\Linguise\Script\Core\Url translate the url
    return Url::translateUrl($url);
}

if (wp_doing_ajax()) {
    /**
     * Process linguise_language and redirect to translated page
     */
    add_action('bookingpress_after_modify_validate_submit_form_data', function ($return_data) {
        linguiseInitializeConfiguration();

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No action
        $ajax_data = $_POST;

        if (!array_key_exists('appointment_data', $ajax_data) || !array_key_exists('linguise_language', $ajax_data['appointment_data'])) {
            return $return_data;
        }

        $linguise_language = $ajax_data['appointment_data']['linguise_language'];

        if (!$linguise_language) {
            return $return_data;
        }

        $approved_appointment_url=$return_data['approved_appointment_url'];
        $pending_appointment_url=$return_data['pending_appointment_url'];
        $canceled_appointment_url=$return_data['canceled_appointment_url'];

        $return_data['approved_appointment_url'] = linguise_bookingpress_translate_url($linguise_language, $approved_appointment_url);
        $return_data['pending_appointment_url'] = linguise_bookingpress_translate_url($linguise_language, $pending_appointment_url);
        $return_data['canceled_appointment_url'] = linguise_bookingpress_translate_url($linguise_language, $canceled_appointment_url);

        return $return_data;
    }, 20, 1);
}

/**
 * Pass Linguise language into create booking ajax request
 */
add_filter('bookingpress_frontend_apointment_form_add_dynamic_data', function ($bookingpress_front_vue_data_fields) {
    $linguise_language = Helper::getLanguage();

    if (!$linguise_language) {
        return $bookingpress_front_vue_data_fields;
    }

    $bookingpress_front_vue_data_fields['appointment_step_form_data']['linguise_language'] = $linguise_language;

    return $bookingpress_front_vue_data_fields;
}, 200);
