<?php

namespace Linguise\WordPress\Integrations;

use Linguise\WordPress\Helper;

defined('ABSPATH') || die('');

/**
 * Integration with BookingPress
 *
 * This fix some issue with redirection going to the original page after booking
 */
class BookingPressIntegration extends LinguiseBaseIntegrations
{
    /**
     * Plugin name
     *
     * @var string
     */
    public static $name = 'BookingPress';

    /**
     * Simple guard check if AJAX has been hooked or not.
     *
     * @var boolean
     */
    private $hooked_ajax;

    /**
     * Initialize the integration.
     *
     * Sets up some additional stuff for hooking.
     */
    public function __construct()
    {
        parent::__construct();

        $this->hooked_ajax = false;
    }

    /**
     * Determine if the integration should be loaded.
     *
     * @return boolean
     */
    public function shouldLoad()
    {
        return is_plugin_active('bookingpress-appointment-booking/bookingpress-appointment-booking.php');
    }

    /**
     * Initializes the integration.
     *
     * @return void
     */
    public function init()
    {
        add_filter('bookingpress_frontend_apointment_form_add_dynamic_data', [$this, 'hookFormAddLinguiseMetadata'], 200, 1);

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action
        $is_save_ajax = wp_doing_ajax() && !empty($_REQUEST['action']) && $_REQUEST['action'] === 'bookingpress_front_save_appointment_booking';
        if ($is_save_ajax) {
            add_filter('bookingpress_after_modify_validate_submit_form_data', [$this, 'hookAfterModifyValidateData'], 20, 1);
            $this->hooked_ajax = true;
        }
    }


    /**
     * Destroy the integration.
     *
     * @return void
     */
    public function destroy()
    {
        remove_filter('bookingpress_frontend_apointment_form_add_dynamic_data', [$this, 'hookFormAddLinguiseMetadata'], 200, 1);

        if ($this->hooked_ajax) {
            remove_filter('bookingpress_after_modify_validate_submit_form_data', [$this, 'hookAfterModifyValidateData'], 20, 1);
            $this->hooked_ajax = false;
        }
    }

    /**
     * Pass Linguise language into create booking ajax request
     *
     * @param array $front_details Frontend details
     *
     * @return array
     */
    public function hookFormAddLinguiseMetadata($front_details)
    {
        $linguise_language = Helper::getLanguage();

        if (!$linguise_language) {
            return $front_details;
        }
    
        $front_details['appointment_step_form_data']['linguise_language'] = $linguise_language;
    
        return $front_details;
    }

    /**
     * Process linguise_language and redirect to translated page
     *
     * @param array $return_data Return data
     *
     * @return array
     */
    public function hookAfterModifyValidateData($return_data)
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No action
        $ajax_data = $_POST;

        if (!array_key_exists('appointment_data', $ajax_data) || !array_key_exists('linguise_language', $ajax_data['appointment_data'])) {
            return $return_data;
        }

        $linguise_language = $ajax_data['appointment_data']['linguise_language'];

        if (!$linguise_language) {
            return $return_data;
        }

        $approved_appointment_url = $return_data['approved_appointment_url'];
        $pending_appointment_url = $return_data['pending_appointment_url'];
        $canceled_appointment_url = $return_data['canceled_appointment_url'];

        $return_data['approved_appointment_url'] = $this->translateUrl($linguise_language, $approved_appointment_url);
        $return_data['pending_appointment_url'] = $this->translateUrl($linguise_language, $pending_appointment_url);
        $return_data['canceled_appointment_url'] = $this->translateUrl($linguise_language, $canceled_appointment_url);

        return $return_data;
    }
}
