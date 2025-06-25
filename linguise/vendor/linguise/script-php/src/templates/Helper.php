<?php

namespace Linguise\Vendor\Linguise\Script\Core\Templates;

use Linguise\Vendor\Linguise\Script\Core\Request;

defined('LINGUISE_SCRIPT_TRANSLATION') or die();

class Helper {
    /**
     * Retrieve the latest errors from Linguise php script
     *
     * @return array
     */
    public static function getLastErrors()
    {
        $errorsFile = LINGUISE_BASE_DIR . 'errors.php';
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
     * @param string  $content The content to display inside the admonition
     * @param string  $mode    The mode of the admonition (info, warning, error, success, primary)
     * @param bool    $toggle  Whether to show a close button
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
            '<div ' . (!empty($merged_options['id']) ? 'id="' . $merged_options['id'] . '" ' : '') . 'class="linguise-admonition mode-' . $mode . ' ' . ($merged_options['toggle'] ? 'with-close' : '') . (!empty($merged_options['class']) ? ' ' . $merged_options['class'] : '') . '"' . ($merged_options['hide'] ? 'style="display: none;">' : '>') .
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
     * Private helper function for checked, selected, disabled and readonly.
     *
     * Compares the first two arguments and if identical marks as $type
     *
     * @param mixed   $helper  One of the values to compare
     * @param mixed   $current (true) The other value to compare if not just true
     * @param boolean $echo    Whether to echo or just return the string
     * @param string  $type    The type of checked|selected|disabled|readonly we are doing
     *
     * @return string HTML attribute or empty string
     */
    public static function __checked_selected_helper($helper, $current, $echo, $type)
    {
        if ((string)$helper === (string)$current) {
            $result = " $type='$type'";
        } else {
            $result = '';
        }

        if ($echo) {
            echo $result;
        }

        return $result;
    }

    /**
     * Outputs the HTML selected attribute.
     *
     * Compares the first two arguments and if identical marks as selected
     *
     * @param mixed   $selected One of the values to compare
     * @param mixed   $current  (true) The other value to compare if not just true
     * @param boolean $echo     Whether to echo or just return the string
     *
     * @return string HTML attribute or empty string
     */
    public static function selected($selected, $current = true, $echo = true)
    {
        return self::__checked_selected_helper($selected, $current, $echo, 'selected');
    }

    /**
     * Outputs the HTML checked attribute.
     *
     * @param mixed   $checked One of the values to compare
     * @param mixed   $current The other value to compare if not just true
     * @param boolean $echo    Whether to echo or just return the string
     *
     * @return string HTML attribute or empty string
     */
    public static function checked($checked, $current = true, $echo = true)
    {
        return self::__checked_selected_helper($checked, $current, $echo, 'checked');
    }

    /**
     * Get the base URL for the management interface.
     *
     * Should include the trailing slashes if they are present in the request.
     *
     * @return string The base URL for the management interface
     */
    public static function getManagementBase()
    {
        $trailing = Request::getInstance()->getTrailingSlashes();
        if (defined('LINGUISE_UI_MANAGEMENT_ROOT')) {
            // Check if we have a trailing slash
            return LINGUISE_UI_MANAGEMENT_ROOT . $trailing;
        } else {
            return 'zz-zz' . $trailing;
        }
    }
}
