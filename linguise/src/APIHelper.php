<?php

namespace Linguise\WordPress;

defined('ABSPATH') || die('');

/**
 * Shared helpers for authenticated Linguise API requests.
 */
class APIHelper
{
    const REMOTE_CONFIG_TIMEOUT = 10;

    /**
     * Build authenticated configuration endpoint from current options.
     *
     * @param array $options Plugin options.
     *
     * @return string
     */
    public static function getConfigApiUrl($options)
    {
        $expert_mode = isset($options['expert_mode']) && is_array($options['expert_mode'])
            ? $options['expert_mode']
            : array();
        $api_host = isset($expert_mode['api_host']) && $expert_mode['api_host'] !== ''
            ? $expert_mode['api_host']
            : 'api.linguise.com';
        $api_port = isset($expert_mode['api_port']) && $expert_mode['api_port'] !== ''
            ? (string)$expert_mode['api_port']
            : '443';
        $protocol = $api_port === '443' ? 'https' : 'http';
        $port = in_array($api_port, array('80', '443'), true) ? '' : ':' . $api_port;

        return $protocol . '://' . $api_host . $port . '/api/config';
    }

    /**
     * Request remote configuration with the authenticated CMS contract.
     *
     * @param string $token API token.
     * @param string $api_url Configuration endpoint.
     *
     * @return array{data: object|false, code: int, empty: bool}
     */
    public static function requestRemoteConfig($token, $api_url)
    {
        $args = array(
            'method' => 'GET',
            'timeout' => self::REMOTE_CONFIG_TIMEOUT,
            'headers' => array(
                'Referer' => linguiseGetSite(),
                'authorization' => $token,
            ),
        );

        try {
            $result = wp_remote_get($api_url, $args);
        } catch (\Throwable $exception) {
            return array('data' => false, 'code' => 0, 'empty' => false);
        }

        if ((function_exists('is_wp_error') && is_wp_error($result)) || !is_array($result)) {
            return array('data' => false, 'code' => 0, 'empty' => false);
        }

        $code = isset($result['response']['code']) ? (int)$result['response']['code'] : 0;
        $body = isset($result['body']) && is_string($result['body']) ? $result['body'] : '';
        if ($code !== 200 || empty($body)) {
            return array('data' => false, 'code' => $code, 'empty' => false);
        }

        $api_response = json_decode($body);
        if (!empty($api_response) && is_object($api_response)
            && isset($api_response->data) && is_object($api_response->data)
        ) {
            return array('data' => $api_response->data, 'code' => $code, 'empty' => false);
        }

        return array('data' => false, 'code' => $code, 'empty' => true);
    }
}
