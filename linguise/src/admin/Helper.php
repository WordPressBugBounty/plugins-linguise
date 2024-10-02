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
            $errorsList[] = [
                'time' => $error[1],
                'code' => $error[2],
                'message' => $error[3],
            ];
        }

        return $errorsList;
    }
}
