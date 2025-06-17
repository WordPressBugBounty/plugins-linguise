<?php

namespace Linguise\WordPress\Admin;

defined('ABSPATH') || die('');


/**
 * Class Helper
 */
class Helper
{
    /**
     * Retrieve the latest errors from Linguise php script
     *
     * @return array
     */
    public static function getLastErrors()
    {
        $errorsFile = LINGUISE_PLUGIN_PATH . DIRECTORY_SEPARATOR. 'vendor' . DIRECTORY_SEPARATOR . 'linguise' . DIRECTORY_SEPARATOR . 'script-php' . DIRECTORY_SEPARATOR . 'errors.php';
        if (file_exists($errorsFile)) {
            $errors = file_get_contents($errorsFile);
        } else {
            $errors = '';
        }

        $errorsList = [];
        if (!preg_match_all('/^\[([0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2})\] (?:([0-9]{3}): )?(.*)$/m', $errors, $matches, PREG_SET_ORDER)) {
            return $errorsList;
        }

        foreach ($matches as $error) {
            $message_extract = self::extractMoreFromMessage($error[3]);

            $errorsList[] = self::mergeErrorList($error, $message_extract);
        }

        return $errorsList;
    }

    /**
     * Extract more data from JSON message data
     *
     * @param string $message The message to extract data from
     *
     * @return array|null The extracted data
     */
    private static function extractMoreFromMessage($message)
    {
        if (empty($message)) {
            return null;
        }

        // parse as JSON
        $json = json_decode($message, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        $data = [];
        if (isset($json['error_code'])) {
            $data['code'] = $json['error_code'];
        } elseif (isset($json['code'])) {
            $data['code'] = $json['code'];
        }

        if (isset($json['message'])) {
            $data['message'] = $json['message'];
        }

        return $data;
    }

    /**
     * Merge error data from the main and the extracted data
     *
     * @param array      $error     The error data from the main message
     * @param array|null $extracted The extracted data from the JSON message
     *
     * @return array|null The extracted data
     */
    private static function mergeErrorList($error, $extracted)
    {
        $current = [
            'time' => $error[1],
            'code' => $error[2],
            'message' => $error[3],
        ];

        if ($extracted !== null && empty($current['code']) && !empty($extracted['code'])) {
            $current['code'] = $extracted['code'];
            $current['message'] = $extracted['message'];
        }

        return $current;
    }

    /**
     * Render an admonition box
     *
     * @param string $content The content to display inside the admonition
     * @param string $mode    The mode of the admonition (info, warning, error, success, primary)
     * @param array  $options Options for the admonition
     *
     * @return string The HTML for the admonition box
     */
    public static function renderAdmonition($content, $mode = 'info', $options = [])
    {
        $merged_options = array_merge([
            'toggle' => false,
            'id' => '',
            'hide' => false,
            'class' => '',
        ], $options);
        $admonition_icons = [
            'info' => 'info_outline',
            'warning' => 'warning_amber',
            'error' => 'error_outline',
            'success' => 'check_circle',
            'primary' => 'info_outline',
        ];

        return (
            '<div ' . (!empty($merged_options['id']) ? 'id="' . esc_attr($merged_options['id']) . '" ' : '') . 'class="linguise-admonition mode-' . esc_attr($mode) . ' ' . ($merged_options['toggle'] ? 'with-close' : '') . (!empty($merged_options['class']) ? ' ' . esc_attr($merged_options['class']) : '') . '"' . ($merged_options['hide'] ? 'style="display: none;">' : '>') .
                '<div class="admonition-icon">' .
                    '<span class="material-icons">' . $admonition_icons[$mode] . '</span>' .
                '</div>' .
                '<div class="admonition-content">' .
                    $content .
                '</div>' .
                ($merged_options['toggle'] ? '<span class="close-icon"><span class="material-icons">close</span></span>' : '') .
            '</div>'
        );
    }

    /**
     * Get the native name of a language code
     *
     * @param string $lang_code The language code to get the native name for
     *
     * @return string
     */
    public static function getWPLangNativeName($lang_code)
    {
        require_once ABSPATH . 'wp-admin/includes/translation-install.php';
        $translations = wp_get_available_translations();
        if (isset($translations[$lang_code])) {
            return $translations[$lang_code]['native_name'];
        }
    
        return 'Unknown';
    }

    /**
     * Get multisite data information
     *
     * @param string $mode The mode to check for multisite
     *
     * @return array the multisite information
     */
    public static function getMultisiteInfo($mode = 'main-page')
    {
        $is_multisite_and_not_main = false;
        if (linguiseIsMultisiteFolder()) {
            $current_network_id = get_current_network_id();
            $main_site_id = get_main_site_id($current_network_id);
            $current_blog_id = get_current_blog_id();
            $is_multisite_and_not_main = $main_site_id !== $current_blog_id;
        }

        $actual_mode = $mode !== 'main-page' ? '&ling_mode=' . $mode : '';

        linguiseSwitchMainSite();
        $main_site_url = admin_url('admin.php?page=linguise' . $actual_mode);
        linguiseRestoreMultisite();

        return [
            'multisite' => $is_multisite_and_not_main,
            'main_site' => $main_site_url,
        ];
    }
}
