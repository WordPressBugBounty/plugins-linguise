<?php

namespace Linguise\WordPress;

defined('ABSPATH') || die('');

require_once(__DIR__ . DIRECTORY_SEPARATOR . 'Helper.php');
require_once(__DIR__ . DIRECTORY_SEPARATOR . 'APIHelper.php');

/**
 * Repairs public keys saved by the CMS integration.
 *
 * The public key belongs to the local installation. It is therefore kept out
 * of remote sync payloads and repaired independently from the dynamic toggle.
 */
class PublicKeyRepair
{
    const STATE_OPTION = 'linguise_public_key_repair';
    const CLAIM_OPTION = 'linguise_public_key_repair_claim';
    const REPAIR_HOOK = 'linguise_repair_public_key';
    const INITIAL_DELAY = 300;
    const MAX_DELAY = 3600;
    const CLAIM_TTL = 120;

    /**
     * Normalize the nested dynamic translation contract.
     *
     * @param mixed $dynamic_translations Dynamic translation options.
     *
     * @return array
     */
    public static function normalizeDynamicTranslations($dynamic_translations)
    {
        $defaults = array(
            'enabled' => 0,
            'public_key' => '',
        );

        if (!is_array($dynamic_translations)) {
            return $defaults;
        }

        return array_merge($defaults, $dynamic_translations);
    }

    /**
     * Check whether a public key can be used by Script-JS.
     *
     * @param mixed $public_key Public key value.
     *
     * @return bool
     */
    public static function hasPublicKey($public_key)
    {
        return is_scalar($public_key) && trim((string)$public_key) !== '';
    }

    /**
     * Register lifecycle hooks once the plugin is loaded.
     *
     * @return void
     */
    public static function registerHooks()
    {
        add_action('init', array(__CLASS__, 'bootstrap'), 1);
        add_action('admin_init', array(__CLASS__, 'bootstrap'), 1);
        add_action(self::REPAIR_HOOK, array(__CLASS__, 'repair'), 10, 0);
    }

    /**
     * Lightweight request bootstrap. It schedules work but never performs a
     * remote request in the normal page lifecycle.
     *
     * @return void
     */
    public static function bootstrap()
    {
        if (!function_exists('linguiseGetOptions')) {
            return;
        }

        self::scheduleForOptions(linguiseGetOptions());
    }

    /**
     * Schedule repair for an options snapshot.
     *
     * @param array $options Current plugin options.
     * @param bool  $immediate Whether first attempt may run immediately.
     *
     * @return void
     */
    public static function scheduleForOptions($options, $immediate = false)
    {
        if (!is_array($options)) {
            return;
        }

        self::enterOptionScope();
        try {
            $dynamic_translations = self::normalizeDynamicTranslations(
                isset($options['dynamic_translations']) ? $options['dynamic_translations'] : null
            );
            $token = isset($options['token']) ? trim((string)$options['token']) : '';

            if ($token === '' || self::hasPublicKey($dynamic_translations['public_key'])) {
                self::clearState();
                return;
            }

            $now = time();
            $token_hash = hash('sha256', $token);
            $state = get_option(self::STATE_OPTION, array());
            $token_changed = !is_array($state) || ($state['token_hash'] ?? '') !== $token_hash;
            if ($token_changed) {
                if (function_exists('wp_clear_scheduled_hook')) {
                    wp_clear_scheduled_hook(self::REPAIR_HOOK);
                }
                $state = array(
                    'token_hash' => $token_hash,
                    'attempts' => 0,
                    'next_attempt' => $now + ($immediate ? 0 : self::INITIAL_DELAY),
                );
                update_option(self::STATE_OPTION, $state, false);
            } elseif ($immediate && (int)($state['next_attempt'] ?? 0) > $now) {
                $state['next_attempt'] = $now;
                update_option(self::STATE_OPTION, $state, false);
            }

            if (!function_exists('wp_next_scheduled') || !function_exists('wp_schedule_single_event')) {
                return;
            }

            if (!wp_next_scheduled(self::REPAIR_HOOK)) {
                $next_attempt = (int)($state['next_attempt'] ?? ($now + self::INITIAL_DELAY));
                wp_schedule_single_event(max($now, $next_attempt), self::REPAIR_HOOK);
            }
        } finally {
            self::leaveOptionScope();
        }
    }

    /**
     * Cron worker. State is written before I/O and is bound to the token that
     * was read before the request, so a concurrent token change cannot receive
     * a stale key.
     *
     * @return void
     */
    public static function repair()
    {
        if (!function_exists('linguiseGetOptions')) {
            return;
        }

        $options = linguiseGetOptions();
        if (!is_array($options)) {
            return;
        }

        self::enterOptionScope();
        try {
            $dynamic_translations = self::normalizeDynamicTranslations(
                isset($options['dynamic_translations']) ? $options['dynamic_translations'] : null
            );
            $token = isset($options['token']) ? trim((string)$options['token']) : '';
            if ($token === '' || self::hasPublicKey($dynamic_translations['public_key'])) {
                self::clearState();
                return;
            }

            $now = time();
            $state = get_option(self::STATE_OPTION, array());
            if (!is_array($state) || ($state['token_hash'] ?? '') !== hash('sha256', $token)) {
                $state = array(
                    'token_hash' => hash('sha256', $token),
                    'attempts' => 0,
                    'next_attempt' => $now,
                );
            }

            if ((int)($state['next_attempt'] ?? 0) > $now) {
                self::scheduleForOptions($options);
                return;
            }

            $claim_id = self::acquireClaim($now);
            if ($claim_id === false) {
                return;
            }

            $attempts = (int)($state['attempts'] ?? 0) + 1;
            $delay = min(self::MAX_DELAY, self::INITIAL_DELAY * (2 ** min($attempts - 1, 4)));
            $state['token_hash'] = hash('sha256', $token);
            $state['attempts'] = $attempts;
            $state['next_attempt'] = $now + $delay;
            update_option(self::STATE_OPTION, $state, false);

            try {
                $response = APIHelper::requestRemoteConfig($token, APIHelper::getConfigApiUrl($options));
                $public_key = self::getPublicKeyFromConfig(
                    is_array($response) && isset($response['data']) ? $response['data'] : false
                );
                if ($public_key === '') {
                    self::scheduleAfterAttempt($state);
                    return;
                }

                if (!self::persistPublicKey($token, $public_key)) {
                    self::scheduleForOptions(linguiseGetOptions());
                    return;
                }

                self::clearState();
            } finally {
                self::releaseClaim($claim_id);
            }
        } finally {
            self::leaveOptionScope();
        }
    }

    /**
     * Extract public key from API data without accepting empty values.
     *
     * @param mixed $data API response data.
     *
     * @return string
     */
    public static function getPublicKeyFromConfig($data)
    {
        if (!is_object($data) || !isset($data->public_key) || !self::hasPublicKey($data->public_key)) {
            return '';
        }

        return trim((string)$data->public_key);
    }

    /**
     * Persist only the public key into the latest option snapshot.
     *
     * @param string $token     Token used for the remote request.
     * @param string $public_key Public key returned by the API.
     *
     * @return bool Whether the key was written for the same token.
     */
    private static function persistPublicKey($token, $public_key)
    {
        // Use WordPress global option API explicitly. Tests and integrations
        // may provide namespaced compatibility wrappers for this option.
        $options = \get_option('linguise_options', array());
        if (!is_array($options)) {
            return false;
        }

        $current_token = isset($options['token']) ? trim((string)$options['token']) : '';
        $dynamic_translations = self::normalizeDynamicTranslations(
            isset($options['dynamic_translations']) ? $options['dynamic_translations'] : null
        );
        if ($current_token !== $token || self::hasPublicKey($dynamic_translations['public_key'])) {
            return false;
        }

        $dynamic_translations['public_key'] = $public_key;
        $options['dynamic_translations'] = $dynamic_translations;
        return (bool)update_option('linguise_options', $options);
    }

    private static function acquireClaim($now)
    {
        $claim_id = function_exists('wp_generate_uuid4') ? wp_generate_uuid4() : uniqid('linguise-', true);
        $claim = array('id' => $claim_id, 'expires' => $now + self::CLAIM_TTL);

        if (function_exists('add_option') && add_option(self::CLAIM_OPTION, $claim, '', false)) {
            return $claim_id;
        }

        $existing = get_option(self::CLAIM_OPTION, array());
        if (is_array($existing) && (int)($existing['expires'] ?? 0) > $now) {
            return false;
        }

        if (function_exists('delete_option')) {
            delete_option(self::CLAIM_OPTION);
        }

        if (function_exists('add_option')) {
            return add_option(self::CLAIM_OPTION, $claim, '', false) ? $claim_id : false;
        }

        update_option(self::CLAIM_OPTION, $claim, false);
        return $claim_id;
    }

    private static function releaseClaim($claim_id)
    {
        $claim = get_option(self::CLAIM_OPTION, array());
        if (!is_array($claim) || ($claim['id'] ?? '') !== $claim_id) {
            return;
        }

        if (function_exists('delete_option')) {
            delete_option(self::CLAIM_OPTION);
        }
    }

    private static function scheduleAfterAttempt($state)
    {
        update_option(self::STATE_OPTION, $state, false);
        if (function_exists('wp_next_scheduled') && function_exists('wp_schedule_single_event')
            && !wp_next_scheduled(self::REPAIR_HOOK)
        ) {
            wp_schedule_single_event((int)$state['next_attempt'], self::REPAIR_HOOK);
        }
    }

    private static function clearState()
    {
        if (function_exists('delete_option')) {
            delete_option(self::STATE_OPTION);
            delete_option(self::CLAIM_OPTION);
        }
        if (function_exists('wp_clear_scheduled_hook')) {
            wp_clear_scheduled_hook(self::REPAIR_HOOK);
        }
    }

    /**
     * Switch option reads/writes to the main site in subfolder multisite mode.
     *
     * @return void
     */
    private static function enterOptionScope()
    {
        if (function_exists('linguiseSwitchMainSite')) {
            linguiseSwitchMainSite();
        }
    }

    /**
     * Restore the original site after option reads/writes.
     *
     * @return void
     */
    private static function leaveOptionScope()
    {
        if (function_exists('linguiseRestoreMultisite')) {
            linguiseRestoreMultisite();
        }
    }
}
