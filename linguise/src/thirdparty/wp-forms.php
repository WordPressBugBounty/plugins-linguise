<?php

namespace Linguise\WordPress\Integrations;

use Linguise\WordPress\Helper as WPHelper;

defined('ABSPATH') || die('');

/**
 * Integration for WPForms
 *
 * Exclude some js vars
 */
class WPFormsIntegration extends LinguiseBaseIntegrations
{
    /**
     * Plugin name
     *
     * @var string
     */
    public static $name = 'WPForms';

    /**
     * Regex/matcher for our custom email fragment
     *
     * @var string
     */
    private static $regex_email = '/<(linguise-email-body|linguise-email-subject) class="linguise-email-fragment">(.*?)<\/\1>/si';

    /**
     * A collection of fragment keys that will be translated
     *
     * @var array{key:string, mode:'exact'|'path'|'wildcard'|'regex'|'regex_full',kind:'allow'|'deny'}
     */
    protected static $fragment_keys = [
        [
            'key' => 'ajaxurl',
            'mode' => 'exact',
            'kind' => 'deny',
        ],
        [
            'key' => 'wpforms_plugin_url',
            'mode' => 'exact',
            'kind' => 'deny',
        ],
        [
            'key' => 'css_vars',
            'mode' => 'exact',
            'kind' => 'deny',
        ],
    ];

    /**
     * Decides if the WPForms integration should be loaded.
     *
     * @return boolean
     */
    public function shouldLoad()
    {
        return is_plugin_active('wpforms-lite/wpforms.php') || is_plugin_active('wpforms/wpforms.php');
    }

    /**
     * Load the integration
     *
     * @return void
     */
    public function init()
    {
        add_filter('wpforms_process_redirect_url', [$this, 'translateRedirectUrl'], 1000, 1);

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action
        if (!empty($_REQUEST['action']) && $_REQUEST['action'] === 'wpforms_submit') {
            add_filter('wp_mail', [$this, 'translateEmails'], 1000, 1);
            add_filter('wpforms_frontend_confirmation_message', [$this, 'translateConfirmationMessage'], 10, 1);
        }
    }

    /**
     * Unload the integration
     *
     * @return void
     */
    public function destroy()
    {
        remove_filter('wpforms_process_redirect_url', [$this, 'translateRedirectUrl'], 1000);

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action
        if (!empty($_REQUEST['action']) && $_REQUEST['action'] === 'wpforms_submit') {
            remove_filter('wp_mail', [$this, 'translateEmails'], 1000);
            remove_filter('wpforms_frontend_confirmation_message', [$this, 'translateConfirmationMessage'], 10);
        }
    }

    /**
     * Translates WPForms email content based on subscriber language.
     *
     * @param array $args WP mail arguments
     *
     * @return array WP mail arguments
     */
    public function translateEmails($args)
    {
        // Check if headers has X-Linguise-Language
        $linguise_lang = WPHelper::getLanguageFromReferer();
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
                $subject_clean = html_entity_decode($subject_match[1], ENT_QUOTES, 'UTF-8');
                $args['subject'] = $subject_clean;
            }

            // Set the body
            $args['message'] = $html_translated;
        }

        return $args;
    }

    /**
     * Translates a confirmation message.
     *
     * @param string $confirmation_message Confirmation message to be translated.
     *
     * @return string translated confirmation message.
     */
    public function translateConfirmationMessage($confirmation_message)
    {
        if (empty($confirmation_message)) {
            return $confirmation_message;
        }

        $language = WPHelper::getLanguageFromReferer();

        if (!$language) {
            return $confirmation_message;
        }

        $html_content = '<html><head></head><body>';
        $html_content .= $confirmation_message;
        $html_content .= '</body></html>';

        $translated_content = $this->translateFragments($html_content, $language, '/');

        if (empty($translated_content)) {
            // Failed to translate
            return $confirmation_message;
        }

        if (isset($translated_content->redirect)) {
            // We got redirect URL for some reason?
            return $confirmation_message;
        }

        preg_match('/<body>(.*)<\/body>/s', $translated_content->content, $matches);

        if (!$matches) {
            // No body match
            return $confirmation_message;
        }

        return $matches[1];
    }

    /**
     * Logs the redirect URL for debugging purposes.
     *
     * @param string $url The redirect URL to be translated.
     *
     * @return string
     */
    public function translateRedirectUrl($url)
    {
        // Store to error log for debugging
        $language = WPHelper::getLanguageFromReferer();
        if (!$language) {
            return $url;
        }

        // Translate the URL
        $result = $this->translateUrl($language, $url);
        if (empty($result)) {
            return $url;
        }

        return $result;
    }
}
