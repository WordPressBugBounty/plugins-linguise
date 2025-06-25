<?php

namespace Linguise\Vendor\Linguise\Script\Core;

defined('LINGUISE_SCRIPT_TRANSLATION') or die();

class Session {
    /**
     * @var null|Session
     */
    private static $_instance = null;

    private $state = 0;

    private static $SESSION_READY = 1;
    private static $SESSION_NOT_READY = 0;

    private static $SESSION_NAME = 'LINGSESSION';

    /**
     * Session key for the token
     *
     * @var string
     */
    public static $token_key = '_linguise_token';
    /**
     * Session key for the token
     *
     * @var string
     */
    public static $use_pass_key = '_linguise_use_password';
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

    private function __construct()
    {
        // Private constructor to prevent instantiation
        $this->state = self::$SESSION_NOT_READY;
    }

    /**
     * Get the singleton instance
     *
     * @return Session
     */
    public static function getInstance()
    {
        if (is_null(self::$_instance)) {
            // Create a new instance if it doesn't exist
            self::$_instance = new Session();
        }
        return self::$_instance;
    }

    /**
     * Start the session, this will return the session object
     *
     * @return Session
     */
    public function start()
    {
        // Check if cookie exists
        if (isset($_COOKIE[self::$SESSION_NAME])) {
            // Check if the session is already started
            if ($this->state === self::$SESSION_READY) {
                return $this;
            } else {
                if (session_status() === PHP_SESSION_NONE) {
                    // Session is not started, so start it
                    session_start([
                        'name' => self::$SESSION_NAME,
                    ]);
                } else {
                    // Session is already started, but not ready
                    $this->state = self::$SESSION_READY;
                }

                return $this;
            }
        }

        if ($this->state === self::$SESSION_NOT_READY) {
            session_name(self::$SESSION_NAME);
            session_start([
                'name' => self::$SESSION_NAME,
            ]);
            $this->state = self::$SESSION_READY;
        }

        return $this;
    }

    /**
     * Destroy all session data
     *
     * @return void
     */
    public function destroy()
    {
        if ($this->state === self::$SESSION_READY) {
            unset($_SESSION[self::$token_key]);
            unset($_SESSION[self::$use_pass_key]);
            unset($_SESSION[self::$csrf_token_key]);
            unset($_SESSION[self::$csrf_token_time_key]);

            $this->state = self::$SESSION_NOT_READY;
        }
    }

    /**
     * Check if the session is set and valid
     *
     * @return boolean True if the session is valid, false otherwise
     */
    public function hasSession()
    {
        if (!isset($_SESSION[self::$token_key]) || !isset($_SESSION[self::$use_pass_key])) {
            return false;
        }

        if (defined('LINGUISE_OOBE_DONE') && !LINGUISE_OOBE_DONE) {
            return false;
        }

        $token_data = $_SESSION[self::$token_key];
        $password_mode = $_SESSION[self::$use_pass_key];

        $db = Database::getInstance(true, true)->ensureConnection();
        if ($password_mode) {
            $pass = $db->retrieveOtherParam('linguise_password');
            if ($pass !== $token_data) {
                // Token does not match the password
                $this->unsetSession();
                return false;
            }

            return true;
        }

        $options = $db->retrieveOtherParam('linguise_options');
        if (!empty($options) && $options['token'] === $token_data) {
            return true;
        }

        $token_conf = Configuration::getInstance()->get('token');
        if ($token_conf !== null && $token_conf === $token_data) {
            return true;
        }

        // Token does not match the password or token
        $this->unsetSession();
        return false;
    }

    /**
     * Set the session token
     *
     * @param string  $token         The token to set
     * @param boolean $password_mode Whether to use password mode or not
     */
    public function setSession($token, $password_mode = \false)
    {
        $this->start();
        $_SESSION[self::$token_key] = $token;
        $_SESSION[self::$use_pass_key] = $password_mode;
    }

    /**
     * Unset the session token
     */
    public function unsetSession()
    {
        $this->start();
        unset($_SESSION[self::$token_key]);
        unset($_SESSION[self::$use_pass_key]);
    }

    public function generateCsrfToken()
    {
        $this->start();

        $_SESSION[self::$csrf_token_key] = bin2hex(random_bytes(32));
        $_SESSION[self::$csrf_token_time_key] = time();
    }

    /**
     * Get the CSRF token for the form
     *
     * @param string $context What the CSRF token is for?
     *
     * @return string
     */
    public function getCsrfToken($context)
    {
        $this->start();

        if (!isset($_SESSION[self::$csrf_token_key])) {
            $_SESSION[self::$csrf_token_key] = bin2hex(random_bytes(32));
        }
        if (!isset($_SESSION[self::$csrf_token_time_key])) {
            $_SESSION[self::$csrf_token_time_key] = time();
        }

        $hmac_data = hash_hmac('sha256', $context . $_SESSION[self::$csrf_token_time_key], $_SESSION[self::$csrf_token_key]);
        $csrf_token = base64_encode($context . '::' . $hmac_data);
        return $csrf_token;
    }

    /**
     * Verify the CSRF token
     *
     * @param string $context What the CSRF token is for?
     * @param string $token The CSRF token to verify
     *
     * @return boolean True if the token is valid, false otherwise
     */
    public function verifyCsrfToken($context, $token)
    {
        $this->start();

        if (!isset($_SESSION[self::$csrf_token_key])) {
            return false;
        }
        if (!isset($_SESSION[self::$csrf_token_time_key])) {
            return false;
        }

        $decode_token = base64_decode($token);
        if ($decode_token === false) {
            return false;
        }

        $token_parts = explode('::', $decode_token);
        if (count($token_parts) !== 2) {
            return false;
        }

        [$context, $hmac_data] = $token_parts;
        if ($context !== $context) {
            return false;
        }

        $new_hmac_data = hash_hmac('sha256', $context . $_SESSION[self::$csrf_token_time_key], $_SESSION[self::$csrf_token_key]);
        return hash_equals($hmac_data, $new_hmac_data);
    }
}
