<?php

namespace Linguise\WordPress\Integrations;

use Linguise\WordPress\Helper as WPHelper;

defined('ABSPATH') || die('');

/**
 * Integration for Kickflip Product Configurators
 *
 * Replace the `lang` query parameter with the correct linguise language.
 */
class KickflipCustomizerIntegration extends LinguiseBaseIntegrations
{
    /**
     * Plugin name
     *
     * @var string
     */
    public static $name = 'Kickflip Product Configurators';

    /**
     * Decides if the integration should be loaded.
     *
     * @codeCoverageIgnore
     *
     * @return boolean
     */
    public function shouldLoad()
    {
        return is_plugin_active('mycustomizer-woocommerce-connector/kickflip-product-configurators.php');
    }

    /**
     * Load the integration
     *
     * @codeCoverageIgnore
     *
     * @return void
     */
    public function init()
    {
        $this->commonInit();

        $correct_locale = null;

        $language = WPHelper::getLanguage();
        if (!empty($language)) {
            // Map language to WordPress locale
            $correct_locale = WPHelper::mapLanguageToWordPressLocale($language);
        }

        $wc_hooks = [
            'woocommerce_before_main_content',
            'woocommerce_after_main_content',
            'woocommerce_before_single_product_summary',
            'woocommerce_after_single_product_summary',
        ];

        if (!empty($correct_locale)) {
            // Ensure the locale is in the correct format
            $correct_locale = str_replace('_', '-', $correct_locale);

            // Hook for the iframe language
            add_action('mczrIframe', function () use ($correct_locale) {
                ob_start(function ($buffer) use ($correct_locale) {
                    return $this->hookIframeLanguage($buffer, $correct_locale);
                });
            }, 1);

            foreach ($wc_hooks as $hook) {
                add_action($hook, function () use ($correct_locale) {
                    ob_start(function ($buffer) use ($correct_locale) {
                        return $this->hookIframeLanguage($buffer, $correct_locale);
                    });
                }, 1);
            }
        }
    }

    /**
     * Common initialization for the integration
     *
     * @codeCoverageIgnore
     *
     * @return void
     */
    private function commonInit()
    {
        add_filter('bloginfo', [$this, 'hookBlogInfoLanguage'], 100, 2);
    }

    /**
     * Unload the integration
     *
     * @codeCoverageIgnore
     *
     * @return void
     */
    public function destroy()
    {
        remove_filter('bloginfo', [$this, 'hookBlogInfoLanguage'], 100);
    }

    /**
     * Reload the integration
     *
     * @codeCoverageIgnore
     *
     * @return void
     */
    public function reload()
    {
        $this->destroy();
        $this->commonInit();
    }

    /**
     * Hook to modify the blog info output for language.
     *
     * @param string $output The original output of bloginfo.
     * @param string $show   The type of information requested (e.g., 'language').
     *
     * @return string The modified output with the correct language.
     */
    public function hookBlogInfoLanguage($output, $show)
    {
        if ($show === 'language') {
            $language = WPHelper::getLanguage();
            if (empty($language)) {
                // No language set, return the buffer as is
                return $output;
            }

            // Map language to WordPress locale
            $wp_locale = WPHelper::mapLanguageToWordPressLocale($language);
            if (empty($wp_locale)) {
                // No valid locale found, return the buffer as is
                return $output;
            }

            return $wp_locale;
        }

        return $output;
    }

    /**
     * Hook to modify the iframe language query parameter.
     *
     * @param string $buffer         The HTML buffer containing the iframe.
     * @param string $correct_locale The correct locale to set in the iframe URL.
     *
     * @return string The modified HTML buffer with the correct language query parameter.
     */
    public function hookIframeLanguage($buffer, $correct_locale)
    {
        if (empty($buffer)) {
            return $buffer;
        }

        if (strpos($buffer, 'mczrMainIframe') === false) {
            // No Kickflip iframe found, return the buffer as is
            return $buffer;
        }

        if (preg_match_all('/<iframe[^>]+src=["\']([^"]+)["\']/', $buffer, $full_matches, PREG_SET_ORDER, 0)) {
            foreach ($full_matches as $matches) {
                // @codeCoverageIgnoreStart
                if (strpos($matches[0], 'mczrMainIframe') === false) {
                    // No Kickflip iframe found, return the buffer as is
                    continue;
                }
                // @codeCoverageIgnoreEnd

                $iframeUrl = $matches[1];
                $newIframeUrl = add_query_arg('lang', $correct_locale, html_entity_decode($iframeUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
                $buffer = str_replace($iframeUrl, htmlspecialchars($newIframeUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), $buffer);
    
                $is_mczr_fallback = strpos($buffer, '<div id=\'mczrFallback') === 0 || strpos($buffer, '<div id="mczrFallback') === 0;
                if ($is_mczr_fallback && strpos($buffer, '<html') !== false) {
                    // The buffer starts with the mczrFallback div and contains an HTML tag, indicating it might be a full HTML document.
                    $buffer_repl = preg_replace('/^<div id=["\']mczrFallback["\'][^>]*>.*?<\/div>/s', '', $buffer);
                    if (!empty($buffer_repl)) {
                        $buffer = $buffer_repl;
                    }
                }
            }
        }

        return $buffer;
    }
}
