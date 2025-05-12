<?php

namespace Linguise\WordPress\Integrations;

use Linguise\WordPress\Helper as WPHelper;

defined('ABSPATH') || die('');

/**
 * Integration for FluentCRM
 *
 * Mainly hooking the email translation process for campaign emails.
 */
class FluentCRMIntegration extends LinguiseBaseIntegrations
{
    /**
     * Plugin name
     *
     * @var string
     */
    public static $name = 'FluentCRM';

    /**
     * Regex/matcher for our custom email fragment
     *
     * @var string
     */
    private static $regex_email = '/<(linguise-email-body|linguise-email-subject) class="linguise-email-fragment">(.*?)<\/\1>/si';

    /**
     * Determines if the integration should be loaded.
     *
     * @return boolean
     */
    public function shouldLoad()
    {
        $options = linguiseGetOptions();
        return is_plugin_active('fluent-crm/fluent-crm.php') && (bool)$options['woocommerce_emails_translation'];
    }

    /**
     * Load the integration
     *
     * @return void
     */
    public function init()
    {
        // Email hooks
        add_filter('fluent_crm/email_headers', [$this, 'injectFluentMetadata'], 1000, 3);
        add_filter('wp_mail', [$this, 'translateEmails'], 1000, 1);

        // Subscriber hooks
        add_action('fluent_crm/contact_created', [$this, 'hookRegisterUser'], 1000, 1);
        add_action('fluent_crm/contact_added_by_fluentform', [$this, 'hookRegisterUser'], 1000, 1);
        add_action('fluent_crm/contact_updated_by_fluentform', [$this, 'hookRegisterUser'], 1000, 1);
    }

    /**
     * Unload the integration
     *
     * @return void
     */
    public function destroy()
    {
        // Email hooks
        remove_filter('fluent_crm/email_headers', [$this, 'injectFluentMetadata'], 1000, 3);
        remove_filter('wp_mail', [$this, 'translateEmails'], 1000, 1);

        // Subscriber hooks
        remove_action('fluent_crm/contact_created', [$this, 'hookRegisterUser'], 1000, 1);
        remove_action('fluent_crm/contact_added_by_fluentform', [$this, 'hookRegisterUser'], 1000, 1);
        remove_action('fluent_crm/contact_updated_by_fluentform', [$this, 'hookRegisterUser'], 1000, 1);
    }

    /**
     * Update the WP user for the subscriber with the current language.
     *
     * Will first try to get the language from the current page, then from the referer.
     * If no language is found, it will skip the update.
     *
     * @param \FluentCrm\App\Models\Subscriber $subscriber The subscriber model itself
     *
     * @return void
     */
    protected function hookRegisterUserWP($subscriber)
    {
        /**
         * The WP user from subscriber
         *
         * @var \WP_User|null
         */
        $user = $subscriber->getWpUser();
        if (empty($user)) {
            return;
        }

        // Get current language
        $language = WPHelper::getLanguage();
        if (empty($language)) {
            $language = WPHelper::getLanguageFromReferer();
        }
        if (empty($language)) {
            // Skip, since no language was found
            return;
        }

        // Update metadata to add linguise_language
        update_user_meta($user->ID, 'linguise_language', $language);
    }

    /**
     * Update the FluentCRM subscriber with the current language.
     *
     * Will first try to get the language from the current page, then from the referer.
     * If no language is found, it will skip the update.
     *
     * @param \FluentCrm\App\Models\Subscriber $subscriber The subscriber model itself
     *
     * @return void
     */
    protected function hookRegisterUserFluent($subscriber)
    {
        if (!function_exists('fluentcrm_update_subscriber_meta')) {
            // Try importing fluentcrm
            $fluentcrm_path = wp_normalize_path(WP_PLUGIN_DIR . '/fluent-crm/app/functions/helpers.php');

            include_once $fluentcrm_path;
        }

        // Still check in case import still fails
        if (function_exists('fluentcrm_update_subscriber_meta')) {
            // Get current language
            $language = WPHelper::getLanguage();
            if (empty($language)) {
                $language = WPHelper::getLanguageFromReferer();
            }
            if (empty($language)) {
                // Skip, since no language was found
                return;
            }

            /**
             * We check already, silent intelephense
             *
             * @disregard P1010
             */
            fluentcrm_update_subscriber_meta($subscriber->id, 'linguise_language', $language);
        }
    }

    /**
     * Hooks on FluentCRM and WP registration to update the user
     * with the current language metadata.
     *
     * @param \FluentCrm\App\Models\Subscriber $subscriber The subscriber model itself
     *
     * @return void
     */
    public function hookRegisterUser($subscriber)
    {
        // Try hooking on both
        $this->hookRegisterUserFluent($subscriber);
        $this->hookRegisterUserWP($subscriber);
    }

    /**
     * Get language from FluentCRM subscriber model
     *
     * This will either check in FluentCRM SubscriberMeta
     * or in WP user meta
     *
     * @param \FluentCrm\App\Models\Subscriber $subscriber FluentCRM Subscriber model
     *
     * @return string|null Language code, null if missing
     */
    protected function getLanguageFromSubscriber($subscriber)
    {
        if (!function_exists('fluentcrm_get_subscriber_meta')) {
            // Try importing fluentcrm
            $fluentcrm_path = wp_normalize_path(WP_PLUGIN_DIR . '/fluent-crm/app/functions/helpers.php');

            include_once $fluentcrm_path;
        }

        // Still check in case import still fails
        if (function_exists('fluentcrm_get_subscriber_meta')) {
            /**
             * We check already, silent intelephense
             *
             * @disregard P1010
             */
            $language = fluentcrm_get_subscriber_meta($subscriber->id, 'linguise_language');

            if (!empty($language)) {
                // Found language
                return $language;
            }
        }

        $user_wp_id = $subscriber->getWpUserId();
        if ($user_wp_id === null) {
            // Skip, since no user was found
            return null;
        }

        // Find the language field
        $user_language = get_user_meta($user_wp_id, 'linguise_language', true);
        if (!empty($user_language)) {
            // Found user language, use it.
            return $user_language;
        }

        $register_language = get_user_meta($user_wp_id, 'linguise_register_language', true);
        if (!empty($register_language)) {
            // Found register_language? We should register that to the subscriber meta
            if (function_exists('fluentcrm_update_subscriber_meta')) {
                /**
                 * We already tried to import, and the above function check for visibility.
                 * Silent intelephense error.
                 *
                 * @disregard P1010
                 */
                fluentcrm_update_subscriber_meta($subscriber->id, 'linguise_language', $register_language);
            }

            return $register_language;
        }

        return null;
    }

    /**
     * Get the language from the headers, if it exists
     *
     * @param string[] $headers Array of headers that will be checked
     *
     * @return string|null
     */
    private function getLinguiseLanguageFromHeaders($headers)
    {
        foreach ($headers as $header) {
            if (strpos($header, 'X-Linguise-Language:') === 0) {
                return trim(substr($header, 20));
            }
        }

        return null;
    }

    /**
     * Translates FluentCRM email content based on subscriber language.
     *
     * @param array $args WP mail arguments
     *
     * @return array WP mail arguments
     */
    public function translateEmails($args)
    {
        // Check if headers has X-Linguise-Language
        $linguise_lang = $this->getLinguiseLanguageFromHeaders($args['headers']);
        if (empty($linguise_lang)) {
            // Skip
            return $args;
        }

        $options = linguiseGetOptions();
        if (!$options['woocommerce_emails_translation']) {
            // Skip, email translation is disabled
            return $args;
        }

        // Ensure the language is supported
        if (!WPHelper::isTranslatableLanguage($linguise_lang)) {
            // Skip, language unsupported
            return $args;
        }

        $html_content = '';

        // Check if the email is already full HTML
        $is_wrapped = false;
        if (preg_match('/^[\s]*(?:<!DOCTYPE html>)?[\s]*<html /i', $args['message'])) {
            // Already HTML, let's inject the linguise-email-subject to the existing body tag
            $email_sub = '<linguise-email-subject>' . $args['subject'] . "</linguise-email-subject>\n";
            $html_content = str_replace('</body>', $email_sub . '</body>', $args['message']);
        } else {
            // Email merging
            $html_content = '<linguise-email-body class="linguise-email-fragment">' . $args['message'] . "</linguise-email-body>\n";
            $html_content .= '<linguise-email-subject class="linguise-email-fragment>' . $args['subject'] . "</linguise-email-subject>\n";

            $html_content = '<html><body>' . $html_content . '</body></html>';
            $is_wrapped = true;
        }

        $translated_email = $this->translateFragments($html_content, $linguise_lang, '/wp-admin/fluentcrm-emails');

        if (empty($translated_email)) {
            // Failed to translate
            return $args;
        }

        if (isset($translated_email->redirect)) {
            // Somehow we got this...?
            return $args;
        }

        $html_translated = $translated_email->content;
        if ($is_wrapped) {
            preg_match_all(self::$regex_email, $html_translated, $html_matches, PREG_SET_ORDER, 0);

            foreach ($html_matches as $html_match) {
                $tag_match = $html_match[1];
                $email_content = $html_match[2];
    
                if ($tag_match === 'linguise-email-body') {
                    $args['message'] = $email_content;
                } elseif ($tag_match === 'linguise-email-subject') {
                    $args['subject'] = $email_content;
                }
            }
        } else {
            // Find the subject
            preg_match('/<linguise-email-subject>(.*?)<\/linguise-email-subject>/', $html_translated, $subject_match);
            if (!empty($subject_match)) {
                // Remove the subject from HTML
                $html_translated = str_replace($subject_match[0], '', $html_translated);
                $args['subject'] = $subject_match[1];
            }

            // Set the body
            $args['message'] = $html_translated;
        }

        return $args;
    }

    /**
     * Injects the subscriber's language as a header to the email if it exists and is translatable.
     *
     * @param string[]                         $headers    The headers for the email
     * @param mixed                            $data       The email data
     * @param \FluentCrm\App\Models\Subscriber $subscriber The subscriber model
     *
     * @return string[] The modified headers
     */
    public function injectFluentMetadata($headers, $data, $subscriber)
    {
        $user_language = $this->getLanguageFromSubscriber($subscriber);
        if (empty($user_language)) {
            // Skip
            return $headers;
        }

        if (!WPHelper::isTranslatableLanguage($user_language)) {
            // Skip, language unsupported
            return $headers;
        }

        // Final check options if email translation is enabled.
        $options = linguiseGetOptions();
        if (!$options['woocommerce_emails_translation']) {
            // Skip
            return $headers;
        }

        $headers[] = 'X-Linguise-Language: ' . $user_language;
        return $headers;
    }

    /**
     * Ignore template data like ##meta_data## and {{meta_data}}
     *
     * @param string $textData The text data to ignore
     *
     * @return string
     */
    protected function ignoreTemplateData($textData)
    {
        $replaced = preg_replace('/([\#{]{2})(.+?)([\#}]{2})/', '<span-linguise class="template-data" translate="no">$1$2$3</span-linguise>', $textData);
        if (!empty($replaced)) {
            return $replaced;
        }

        return $textData;
    }

    /**
     * Replace back the ignored template data
     *
     * @param string $textData The text data to ignore
     *
     * @return string
     */
    protected function replaceBackTemplateData($textData)
    {
        $replaced = preg_replace('/<span-linguise class="template-data" translate="no">([\#{]{2})(.+?)([\#}]{2})<\/span-linguise>/', '$1$2$3', $textData);
        if (!empty($replaced)) {
            return $replaced;
        }

        return $textData;
    }
}
