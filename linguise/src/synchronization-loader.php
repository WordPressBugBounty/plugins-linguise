<?php
use Linguise\Vendor\Linguise\Script\Core\Management;
use Linguise\WordPress\Synchronization;
use Linguise\Vendor\Linguise\Script\Core\Debug;

defined('ABSPATH') || die('');

/**
 * Sync config when languages changed in WP
 */
add_action('updated_option', function ($option, $old_value, $value) {
    linguiseInitializeConfiguration();
    $original_data = [$option, $old_value, $value];

    // Not linguise config
    if ($option !== 'linguise_options') {
        return $original_data;
    }

    // If the config updated by Api-JS via REST API
    // We don't need to sync
    if (defined('REST_REQUEST') && REST_REQUEST) {
        return $original_data;
    }

    try {
        $options =  linguiseGetOptions();
        $synchronization = Synchronization::getInstance();
        $configs = $synchronization->buildPayload($options);
        $token = $options['token'];
    
        $management = Management::getInstance();
        $api_url = $synchronization->getApiRoot($options, '/api/sync/domain');
        $result = $management->pushRemoteSync($configs, $token, $api_url);

        if (!$result) {
            Debug::saveError('configuration synchronization to Linguise failed');
        }
    } catch (Exception $e) {
        Debug::saveError('configuration synchronization to Linguise failed: ' . $e->getMessage());
    }

    return $original_data;
}, 10, 3);

/**
 * Handle sync languages request from Linguise API
 *
 * @param WP_REST_Request $request Ret API params
 *
 * @return WP_REST_Response
 */
function update_config(WP_REST_Request $request)
{
    $jwt_token = $request->get_header('X-Linguise-Hash');
    $options =  linguiseGetOptions();

    $params = $request->get_json_params();
    $token = isset($params['token']) ? $params['token'] : null;
    
    $management = Management::getInstance();
    $synchronization = Synchronization::getInstance();
    $api_url = $synchronization->getApiRoot($options, '/api/sync/verify');
    // Verify hash data
    $management->verifyRemoteToken($jwt_token, $api_url);

    // Validate token
    if ($token !== $options['token']) {
        return new WP_Error('unauthorized', 'Invalid Token', array( 'status' => 401 ));
    }

    try {
        $config = $synchronization->convertparamsToWPOptions($params);
        $merged_config = array_merge($options, $config);
    } catch (Exception $e) {
        return new WP_Error('error', $e->getMessage(), array( 'status' => 500 ));
    }

    update_option('linguise_options', $merged_config);

    return rest_ensure_response(['message' => 'Config successfully updated']);
}

add_action('rest_api_init', function () {
    register_rest_route('linguise/v1', '/sync', array(
      'methods' => 'POST',
      'callback' => 'update_config',
    ));
});
