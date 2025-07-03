<?php
namespace Linguise\WordPress;

use Linguise\Vendor\Linguise\Script\Core\Configuration;

defined('ABSPATH') || die('');

/**
 * Syncronization class
*/
class Synchronization
{
    /**
     * Synchronization class.
     *
     * @var Synchronization
     */
    private static $instance = null;

    /**
     * Get the singleton instance of the Synchronization class
     *
     * @return Synchronization
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            // Create a new instance if it doesn't exist
            self::$instance = new Synchronization();
        }

        return self::$instance;
    }

    /**
     * Builds the payload to be sent to Linguise API for synchronization.
     *
     * @param array $options The plugin options as an associative array.
     *
     * @return array The payload as an associative array.
     */
    public function buildPayload($options)
    {
        $dynamic_translations = $options['dynamic_translations'];

        $payload = [
            'language' => $options['default_language'],
            'allowed_languages' => $options['enabled_languages'],
            'dynamic_translations' =>  $this->intBool($dynamic_translations['enabled'])
        ];

        return $payload;
    }

    /**
     * Converts the params coming from the Linguise API to the format understood by WordPress options.
     *
     * @param array $params The params coming from the Linguise API as an associative array.
     *
     * @return array The converted params as an associative array.
     */
    public function convertparamsToWPOptions($params)
    {
        $options = [
            'default_language' => $params['language'],
            'enabled_languages' => $params['allowed_languages'],
            'dynamic_translations' => [
                'enabled' => $this->intBool($params['dynamic_translations']),
            ]
        ];

        return $options;
    }

    /**
     * Convert an integer (0 or 1) to a boolean value
     *
     * @param mixed $value The value to convert
     *
     * @return mixed The converted value (true or false), or original
     */
    public function intBool($value)
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value)) {
            return $value === 1;
        }

        return $value;
    }

    /**
     * Constructs the API root URL for synchronization.
     *
     * @param array       $options An associative array containing configuration options
     * @param string|null $path    An optional path to append to the API root URL
     *
     * @return string The constructed API root URL for domain synchronization.
     */
    public static function getApiRoot($options, $path = null)
    {
        $api_port_base = [443, 80];
        if (isset($options['expert_mode'])) {
            $api_host = isset($options['expert_mode']['api_host']) ? $options['expert_mode']['api_host'] : null;
            $api_port = isset($options['expert_mode']['api_port']) ? (int)$options['expert_mode']['api_port'] : 443;
            $protocol = $api_port === 443 ? 'https' : 'http';

            if (!empty($api_host)) {
                return $protocol . '://' . $api_host . (in_array($api_port, $api_port_base) ? '' : ':' . $api_port) . $path;
            }
        }

        $api_host = Configuration::getInstance()->get('api_host') ?? 'api.linguise.com';
        $api_port = (int)Configuration::getInstance()->get('api_port') ?? 443;
        $protocol = $api_port === 443 ? 'https' : 'http';
        return $protocol . '://' . $api_host . (in_array($api_port, $api_port_base) ? '' : ':' . $api_port) . $path;
    }
}
