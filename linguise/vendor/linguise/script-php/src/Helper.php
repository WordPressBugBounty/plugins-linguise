<?php

namespace Linguise\Vendor\Linguise\Script\Core;

defined('LINGUISE_SCRIPT_TRANSLATION') or die();

class Helper {
    /**
     * Languages information
     *
     * @var null|array
     */
    protected static $languages_information = null;

    /**
     * CSRF token key
     *
     * @var string
     */
    public static $csrf_token_key = '_linguise_csrf_token';
    /**
     * CSRF token timing key
     *
     * @var string
     */
    public static $csrf_token_time_key = '_linguise_csrf_token_time';

    public static function getIpAddress()
    {
        if (isset($_SERVER['HTTP_X_REAL_IP'])) {
            return $_SERVER['HTTP_X_REAL_IP'];
        }

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = (string)trim(current(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])));
            if ($ip) {
                return $ip;
            }
        }

        if (isset( $_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }

        return '';
    }

    public static function getClassStaticVars($classname)
    {
        $class = new \ReflectionClass($classname);
        return $class->getStaticProperties();
    }

    public static function prepareDataDir()
    {
        if (Configuration::getInstance()->get('data_dir') === null) {
            $data_folder = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..') . DIRECTORY_SEPARATOR . md5('data' . Configuration::getInstance()->get('token'));
            if (!file_exists($data_folder)) {
                mkdir($data_folder, 0766, true);
                mkdir($data_folder . DIRECTORY_SEPARATOR . 'database', 0766, true);
                mkdir($data_folder . DIRECTORY_SEPARATOR . 'cache', 0766, true);
                mkdir($data_folder . DIRECTORY_SEPARATOR . 'tmp', 0766, true);
                file_put_contents($data_folder . DIRECTORY_SEPARATOR . '.htaccess', 'deny from all');
            }
            Configuration::getInstance()->set('data_dir', $data_folder);
        }
    }

    public static function checkDataDirAvailable()
    {
        $data_dir = Configuration::getInstance()->get('data_dir');
        if (!empty($data_dir)) {
            return true;
        }

        $data_folder = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..') . DIRECTORY_SEPARATOR . md5('data' . Configuration::getInstance()->get('token'));
        if (file_exists($data_folder)) {
            return true;
        }

        if (!mkdir($data_folder)) {
            return false;
        }

        // Check if writeable
        if (!is_writable($data_folder)) {
            return false;
        }

        $db_data_folder = $data_folder . DIRECTORY_SEPARATOR . 'database';
        if (!file_exists($db_data_folder)) {
            if (!mkdir($db_data_folder, 0766, true)) {
                return false;
            }
        }

        if (!is_writable($db_data_folder)) {
            return false;
        }

        return true;
    }

    /**
     * Convert a query string to an array
     * 
     * @param string $query
     * @return array
     */
    public static function queryStringToArray($query)
    {
        if (empty($query)) {
            return [];
        }
        $name_values = explode('&', $query);
        $query_params = [];
        foreach ($name_values as $name_value) {
            $values = explode('=', $name_value);
            if (empty($values[1])) {
                $query_params[$values[0]] = '';
            } else {
                $query_params[$values[0]] = rawurldecode($values[1]);
            }
        }
        return $query_params;
    }

    /**
     * Convert an array to a query string
     * 
     * @param array $query
     * @return string
     */
    public static function arrayToQueryString($query)
    {
        if (empty($query)) {
            return '';
        }

        $new_queries = [];
        foreach ($query as $name => $value) {
            $new_queries[] = $name . '=' . rawurlencode($value);
        }

        return implode('&', $new_queries);
    }

    /**
     * Create a new URL based on the parsed_url output
     * @param array   $parsed_url The parsed URL
     * @param boolean $encoded    Should we encode the URL or not.
     *
     * @return string
     */
    public static function buildUrl($parsed_url, $encoded = \false)
    {
        $final_url = '';
        if (empty($parsed_url['scheme'])) {
            $final_url .= '//';
        } else {
            $final_url .= $parsed_url['scheme'] . '://';
        }

        if (!empty($parsed_url['user'])) {
            $final_url .= $parsed_url['user'];
            if (!empty($parsed_url['pass'])) {
                $final_url .= ':' . $parsed_url['pass'];
            }
            $final_url .= '@';
        }

        $final_url .= empty($parsed_url['host']) ? '' : $parsed_url['host'];

        if (!empty($parsed_url['port'])) {
            $final_url .= ':' . $parsed_url['port'];
        }

        if (!empty($parsed_url['path'])) {
            if ($encoded) {
                $explode_path = array_map('rawurlencode', explode('/', $parsed_url['path']));
                $final_url .= implode('/', $explode_path);
            } else {
                $final_url .= $parsed_url['path'];
            }
        }

        if (!empty($parsed_url['query'])) {
            $final_url .= '?' . $parsed_url['query'];
        }

        if (!empty($parsed_url['fragment'])) {
            $final_url .= '#' . $parsed_url['fragment'];
        }

        return $final_url;
    }

    /**
     * Return all languages information
     *
     * @return array
     */
    public static function getLanguagesInfos() {
        if (self::$languages_information !== null) {
            return self::$languages_information;
        }

        // Root dir + assets/languages.json
        $languages_file = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'languages.json');

        $languages = file_get_contents($languages_file);
        self::$languages_information = json_decode($languages, true);
        return self::$languages_information;
    }

    /**
     * Sanitizes a string key.
     *
     * Keys are used as internal identifiers. Lowercase alphanumeric characters,
     * dashes, and underscores are allowed.
     *
     * @param string $key String key
     *
     * @return string Sanitized key
     */
    public static function sanitizeKey($key)
    {
        if (is_scalar($key)) {
            $sanitized_key = strtolower($key);
            $sanitized_key = preg_replace('/[^a-z0-9_\-]/', '', $sanitized_key);
            return $sanitized_key;
        }
        return $key;
    }
}