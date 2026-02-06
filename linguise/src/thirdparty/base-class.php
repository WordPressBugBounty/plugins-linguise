<?php

namespace Linguise\WordPress\Integrations;

use Linguise\Vendor\Linguise\Script\Core\Boundary;
use Linguise\Vendor\Linguise\Script\Core\Configuration;
use Linguise\Vendor\Linguise\Script\Core\Database;
use Linguise\Vendor\Linguise\Script\Core\Debug;
use Linguise\Vendor\Linguise\Script\Core\Helper;
use Linguise\Vendor\Linguise\Script\Core\Processor;
use Linguise\Vendor\Linguise\Script\Core\Request;
use Linguise\Vendor\Linguise\Script\Core\Translation;
use Linguise\Vendor\Linguise\Script\Core\Url;

use Linguise\WordPress\Helper as WPHelper;

defined('ABSPATH') || die('');

/**
 * Base class for Linguise integrations
 */
class LinguiseBaseIntegrations
{
    /**
     * The name of the integration
     *
     * @var string
     */
    public static $name = '';

    /**
     * A collection of fragment keys that you want to translate/ignore
     *
     * Please see the FragmentHandler::isKeyAllowed() implementation for example.
     *
     * @var array{key:string, mode:'exact'|'path'|'wildcard'|'regex'|'regex_full',kind:'allow'|'deny'}
     */
    protected static $fragment_keys = [];

    /**
     * A collection of fragment overrides that you want to translate
     *
     * You give a matcher and replacement matcher that will match a JSON data that we
     * will translate.
     *
     * @var array
     */
    protected static $fragment_overrides = [];

    /**
     * A collection of HTML attributes that you want to translate
     *
     * You give a matcher and replacement matcher that will match a JSON data that we
     * will translate.
     *
     * @var array
     */
    protected static $fragment_attributes = [];

    /**
     * A list of WP-REST ajax methods that should be intercepted.
     *
     * @var string[]
     */
    protected static $wp_rest_ajax_intercept = [];

    /**
     * Has the configuration been initialized
     *
     * @var boolean
     */
    private $initialized_config = false;

    /**
     * Initialize the integration
     *
     * @return void
     */
    public function __construct()
    {
        if (empty(static::$name)) {
            throw new \Exception('Linguise: Please set a $name for the ' . get_class($this) . ' class.');
        }
    }

    /**
     * Check if the integration should be loaded
     *
     * @return boolean
     */
    public function shouldLoad()
    {
        return false;
    }

    /**
     * Initialize the integration
     *
     * @codeCoverageIgnore
     *
     * @return void
     */
    public function init()
    {
        // Throw an error if the class is not implemented
        throw new \Exception('Linguise: Please implement the ' . get_class($this) . '::init() function.');
    }

    /**
     * Unhook the integration
     *
     * @codeCoverageIgnore
     *
     * @return void
     */
    public function destroy()
    {
        // Throw an error if the class is not implemented
        throw new \Exception('Linguise: Please implement the ' . get_class($this) . '::destroy() function.');
    }

    /**
     * Reload the integration
     *
     * This is a helper function to reload the integration. By default it calls the destroy and init functions.
     *
     * @codeCoverageIgnore
     *
     * @return void
     */
    public function reload()
    {
        // By default this destroy and re-init the process
        $this->destroy();
        $this->init();
    }

    /**
     * Get the collection of fragment keys that you want to translate/ignore
     *
     * Please see the FragmentHandler::isKeyAllowed() implementation for example.
     *
     * @return array{key:string, mode:'exact'|'path'|'wildcard'|'regex'|'regex_full',kind:'allow'|'deny'}
     */
    public function getFragmentKeys()
    {
        return static::$fragment_keys;
    }

    /**
     * Get the collection of fragment overrides that you want to translate
     *
     * You give a matcher and replacement matcher that will match a JSON data that we
     * will translate.
     *
     * @return array
     */
    public function getFragmentOverrides()
    {
        return static::$fragment_overrides;
    }

    /**
     * Get the collection of HTML attributes that you want to translate
     *
     * You give a matcher and replacement matcher that will match a JSON data that we
     * will translate.
     *
     * @return array
     */
    public function getFragmentAttributes()
    {
        return static::$fragment_attributes;
    }

    /**
     * A list of WP-REST ajax methods that should be intercepted.
     *
     * @see https://developer.wordpress.org/rest-api/
     *
     * @return string[]
     */
    public function getWpRestAjaxIntercept()
    {
        return static::$wp_rest_ajax_intercept;
    }

    /**
     * Initialize the configuration of Linguise for the plugin.
     *
     * We need to define the constant LINGUISE_SCRIPT_TRANSLATION to 1.
     * Then we load the configuration from the plugin's directory.
     *
     * @codeCoverageIgnore
     *
     * @return void
     */
    protected function initializeConfiguration()
    {
        if (!defined('LINGUISE_SCRIPT_TRANSLATION')) {
            define('LINGUISE_SCRIPT_TRANSLATION', 1);
        }

        if ($this->initialized_config) {
            return;
        }

        if (defined('LINGUISE_PLUGIN_PATH')) {
            $plugin_dir = LINGUISE_PLUGIN_PATH;
        } else {
            // Get the main root dir from thirdparty folder
            $plugin_dir = plugin_dir_path(__FILE__);
        }

        // Strip last slash if exist
        if (substr($plugin_dir, -1) === '/') {
            $plugin_dir = substr($plugin_dir, 0, -1);
        }
    
        // We require instead of include so that the autoloader can find the plugin's directory
        require_once($plugin_dir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

        linguiseInitializeConfiguration();

        $this->initialized_config = true;
    }

    /**
     * Tiny helper function to let Linguise translate URL
     *
     * We only set the protected property, if we not set the property it would be null sometimes.
     *
     * @param string $linguise_language Linguise language
     * @param string $url               The string URL
     *
     * @codeCoverageIgnore
     *
     * @return string
     */
    protected function translateUrl($linguise_language, $url)
    {
        $this->initializeConfiguration();

        if (!WPHelper::isTranslatableLanguage($linguise_language)) {
            return $url;
        }

        $hostname = $_SERVER['HTTP_HOST'];
        $protocol = (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' || !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] === 443) ? 'https' : 'http';
    
        $request = Request::getInstance();
        $language_reflection = new \ReflectionProperty(get_class($request), 'language');
        if (PHP_VERSION_ID < 80100) {
            // phpcs:ignore Generic.PHP.DeprecatedFunctions.Deprecated -- Since PHP 8.0+, we guard it here
            $language_reflection->setAccessible(true); // Since PHP 8.1+, this does not do anything anymore
        }
        $language_reflection->setValue($request, $linguise_language);
    
        $hostname_reflection = new \ReflectionProperty(get_class($request), 'hostname');
        if (PHP_VERSION_ID < 80100) {
            // phpcs:ignore Generic.PHP.DeprecatedFunctions.Deprecated -- Since PHP 8.0+, we guard it here
            $hostname_reflection->setAccessible(true); // Since PHP 8.1+, this does not do anything anymore
        }
        $hostname_reflection->setValue($request, $hostname);
    
        $protocol_reflection = new \ReflectionProperty(get_class($request), 'protocol');
        if (PHP_VERSION_ID < 80100) {
            // phpcs:ignore Generic.PHP.DeprecatedFunctions.Deprecated -- Since PHP 8.0+, we guard it here
            $protocol_reflection->setAccessible(true); // Since PHP 8.1+, this does not do anything anymore
        }
        $protocol_reflection->setValue($request, $protocol);
    
        // Let Linguise\Vendor\Linguise\Script\Core\Url translate the url
        return Url::translateUrl($url);
    }

    /**
     * Tiny helper function to let Linguise translate search object
     *
     * @param array  $search_obj The search object to translate
     * @param string $language   The language to translate to
     *
     * @codeCoverageIgnore
     *
     * @return object|false The array object from Linguise API call. `false` if the translation failed or failed to decode JSON.
     */
    protected function translateSearch($search_obj, $language)
    {
        $this->initializeConfiguration();

        if (!WPHelper::isTranslatableLanguage($language)) {
            return false;
        }

        $options = linguiseGetOptions();
        Configuration::getInstance()->set('token', $options['token']);

        $translation = Translation::getInstance()->translateJson($search_obj, linguiseGetSite(), $language);
        if (empty($translation)) {
            return false;
        }

        return $translation;
    }

    /**
     * A helper function to translate fragments or any other HTML content with Linguise
     * This function will automatically translate then store the translate URL (if needed)
     *
     * Note: Using this function will increase your quota usage greatly.
     *
     * @param string $html_content   The HTML content
     * @param string $language       The language
     * @param string $requested_path The requested path
     *
     * @codeCoverageIgnore
     *
     * @return object|false The array object from Linguise API call. `false` if the translation failed or failed to decode JSON.
     */
    protected function translateFragments($html_content, $language, $requested_path)
    {
        $this->initializeConfiguration();

        if (!WPHelper::isTranslatableLanguage($language)) {
            return false;
        }

        $options = linguiseGetOptions();

        if ($options['debug']) {
            Debug::enable(5, Configuration::getInstance()->get('debug_ip'));
        }

        Configuration::getInstance()->set('token', $options['token']);

        $boundary = new Boundary();
        $request = Request::getInstance();

        $boundary->addPostFields('version', Processor::$version);
        $boundary->addPostFields('url', rtrim(linguiseGetSite(), '/'));
        $boundary->addPostFields('language', $language);
        $boundary->addPostFields('requested_path', $requested_path);
        $boundary->addPostFields('content', $html_content);
        $boundary->addPostFields('token', $options['token']);
        $boundary->addPostFields('ip', Helper::getIpAddress());
        $boundary->addPostFields('response_code', 200);
        $boundary->addPostFields('user_agent', !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');

        $ch = curl_init();
        list($translated_content, $response_code) = Translation::getInstance()->_translate($ch, $boundary);
        if (PHP_VERSION_ID < 80000) {
            // phpcs:ignore PHPCompatibility.FunctionUse.RemovedFunctions.curl_closeDeprecated,Generic.PHP.DeprecatedFunctions.Deprecated -- Since PHP 8.0+, we guard it here
            curl_close($ch); // Since, PHP 8+ this thing actually does not do anything (deprecated in PHP 8.5)
        }
    
        if (!$translated_content || $response_code !== 200) {
            // We failed to translate
            Debug::saveError('Failed to translate fragments for language: ' . $language . ' with response code: ' . $response_code);
            return false;
        }

        $result = json_decode($translated_content);
        // Check if failed to decode JSON
        $json_error = json_last_error();
        $json_error_message = json_last_error_msg();
        if ($json_error !== JSON_ERROR_NONE) {
            Debug::saveError('Failed to decode JSON for language: ' . $language . ' with error code: ' . $json_error . ' and message: ' . $json_error_message);
            return false;
        }

        // Use reflection to access private property language.
        // We do this since the Database use Request::getInstance()->getLanguage()
        // to get the current language which should be "null" or missing in this case
        // if we don't replace it.
        $req_reflect = new \ReflectionClass($request);
        $reflect_lang = $req_reflect->getProperty('language');
        if (PHP_VERSION_ID < 80100) {
            // phpcs:ignore Generic.PHP.DeprecatedFunctions.Deprecated -- Since PHP 8.0+, we guard it here
            $reflect_lang->setAccessible(true); // Since PHP 8.1+, this does not do anything anymore
        }
        // Get the old value first
        $req_language = $request->getLanguage();
        // Set the new value from the referer data
        $reflect_lang->setValue($request, $language);

        $db_updated = false;
        // Store the new URLs
        if (isset($result->url_translations)) {
            $new_urls = get_object_vars($result->url_translations);
            Database::getInstance()->saveUrls((array)$new_urls);
            $db_updated = true;
        }
    
        // Remove stored URLs
        if (isset($result->urls_untranslated)) {
            Database::getInstance()->removeUrls((array)$result->urls_untranslated);
            $db_updated = true;
        }
    
        register_shutdown_function(function () use ($db_updated) {
            if ($db_updated) {
                Database::getInstance()->close();
            }
        });

        // Revert the language back
        $reflect_lang->setValue($request, $req_language);

        // Return the full result
        return $result;
    }

    /**
     * Mark the tag as translated to avoid being translated again by dynamic translation
     *
     * @param string $html_data The HTML data to mark.
     * @param string $tag_name  The tag name to mark.
     *
     * @return string
     */
    protected function markByTag($html_data, $tag_name)
    {
        $replacement = preg_replace_callback(
            '/<(' . preg_quote($tag_name, '/') . ')([^>]*)>/i',
            function ($matches) {
                // Add a data-linguise-parent-ignore="1" attribute to the tag
                return '<' . $matches[1] . $matches[2] . ' data-linguise-parent-ignore="1">';
            },
            $html_data
        );
        if (is_string($replacement) && !empty($replacement)) {
            return $replacement;
        }
        // fail
        return $html_data;
    }

    /**
     * Mark the tag by attribute to avoid being translated again by dynamic translation
     *
     * @param string $html_data      The HTML data to mark.
     * @param string $attribute_name The attribute name to mark.
     *
     * @return string
     */
    protected function markByAttribute($html_data, $attribute_name)
    {
        $replacement = preg_replace_callback(
            '/' . preg_quote($attribute_name, '/') . '=(["\'])([^"\']+)\1/',
            function ($matches) use ($attribute_name) {
                // Add a data-linguise-parent-ignore="1" attribute to the tag
                return $attribute_name . '=' . $matches[1] . $matches[2] . $matches[1] . ' data-linguise-parent-ignore="1"';
            },
            $html_data
        );
        if (is_string($replacement) && !empty($replacement)) {
            return $replacement;
        }
        // fail
        return $html_data;
    }
}
