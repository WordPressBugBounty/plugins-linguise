<?php
use Linguise\Vendor\Linguise\Script\Core\Configuration;
use Linguise\Vendor\Linguise\Script\Core\Boundary;
use Linguise\Vendor\Linguise\Script\Core\Helper;
use Linguise\Vendor\Linguise\Script\Core\Processor;
use Linguise\Vendor\Linguise\Script\Core\Request;
use Linguise\Vendor\Linguise\Script\Core\Translation;
use Linguise\WordPress\Helper as WPHelper;

include_once ABSPATH . 'wp-admin/includes/plugin.php';
if (!is_plugin_active('woocommerce/woocommerce.php')) {
    return;
}

$linguise_options = linguiseGetOptions();

if ($linguise_options['woocommerce_emails_translation']) {
    /**
     * Translate WooCommerce Admin emails, if order processed via action in admin order grid
     */
    add_filter('woocommerce_mail_callback_params', function ($args, $wc_email) {
        if (!$wc_email->is_customer_email()) {
            return $args;
        }
    
        $language_meta = $wc_email->object->get_meta('linguise_language', true);
        $language_fallback = WPHelper::getLanguage();
        $language = $language_meta ? $language_meta : $language_fallback;
    
        if (!$language || !WPHelper::isTranslatableLanguage($language)) {
            return $args;
        }
    
        if (!defined('LINGUISE_SCRIPT_TRANSLATION')) {
            define('LINGUISE_SCRIPT_TRANSLATION', 1);
        }
    
        include_once(LINGUISE_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');
    
        linguiseInitializeConfiguration();
    
        $options = linguiseGetOptions();
        Configuration::getInstance()->set('token', $options['token']);
    
        $content = '<html><head></head><body>';
        $content .= '<divlinguisesubject>' . $args[1] . '</divlinguisesubject>';
        $content .= '<divlinguisecontent>' . $args[2] . '</divlinguisecontent>';
        $content .= '</body>';
    
        $boundary = new Boundary();
        $request = Request::getInstance();
    
        $boundary->addPostFields('version', Processor::$version);
        $boundary->addPostFields('url', $request->getBaseUrl());
        $boundary->addPostFields('language', $language);
        $boundary->addPostFields('requested_path', '/');
        $boundary->addPostFields('content', $content);
        $boundary->addPostFields('token', Configuration::getInstance()->get('token'));
        $boundary->addPostFields('ip', Helper::getIpAddress());
        $boundary->addPostFields('response_code', 200);
        $boundary->addPostFields('user_agent', !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');
    
        $ch = curl_init();
    
        list($translated_content, $response_code) = Translation::getInstance()->_translate($ch, $boundary);
    
        if (!$translated_content || $response_code !== 200) {
            // We failed to translate
            return $args;
        }
    
        curl_close($ch);
    
        $result = json_decode($translated_content);
    
        preg_match('/<divlinguisesubject>(.*?)<\/divlinguisesubject>/s', $result->content, $matches);
        if (!$matches) {
            return $args;
        }
        $args[1] =  html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
        preg_match('/<divlinguisecontent>(.*?)<\/divlinguisecontent>/s', $result->content, $matches);
        if (!$matches) {
            return $args;
        }
        $args[2] = $matches[1];
    
        return $args;
    }, 100, 2);
}
