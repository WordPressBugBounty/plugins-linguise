<?php

namespace Linguise\WordPress\Integrations;

use Linguise\WordPress\FragmentHandler;
use Linguise\WordPress\Helper as WPHelper;

defined('ABSPATH') || die('');

/**
 * Integration with Smart Search for WooCommerce by Searchanise.
 *
 * Searchanise exports one catalog per language. This integration translates
 * only selected catalog text through Linguise fragments, preserving all
 * identifiers and Searchanise transport data.
 */
class WCSearchaniseIntegration extends LinguiseBaseIntegrations
{
    /**
     * Plugin name
     *
     * @var string
     */
    public static $name = 'Searchanise - Smart Search for WooCommerce';

    /**
     * Decides if the integration should be loaded.
     *
     * @return boolean
     */
    public function shouldLoad()
    {
        return is_plugin_active('smart-search-for-woocommerce/woocommerce-searchanise.php') || is_plugin_active('smart-search-and-product-filter/woocommerce-searchanise.php');
    }

    /**
     * Initialize Searchanise language, URL and catalog export hooks.
     *
     * @return void
     */
    public function init()
    {
        add_filter('searchanise_get_active_languages', [$this, 'hookGetActiveLanguages'], 10, 1);
        add_filter('searchanise_get_translate', [$this, 'hookTranslateExport'], 10, 2);
        add_filter('searchanise_get_current_language', [$this, 'hookGetCurrentLanguage'], 10, 1);
        add_filter('searchanise_get_language_link', [$this, 'hookGetLanguageLink'], 10, 2);
        add_filter('searchanise_get_frontend_url_pre', [$this, 'hookGetFrontendUrlPre'], 10, 3);
        add_filter('searchanise_get_english_name', [$this, 'hookGetEnglishName'], 10, 1);
    }

    /**
     * Unload Searchanise hooks.
     *
     * @return void
     */
    public function destroy()
    {
        remove_filter('searchanise_get_active_languages', [$this, 'hookGetActiveLanguages'], 10);
        remove_filter('searchanise_get_translate', [$this, 'hookTranslateExport'], 10);
        remove_filter('searchanise_get_current_language', [$this, 'hookGetCurrentLanguage'], 10);
        remove_filter('searchanise_get_language_link', [$this, 'hookGetLanguageLink'], 10);
        remove_filter('searchanise_get_frontend_url_pre', [$this, 'hookGetFrontendUrlPre'], 10);
        remove_filter('searchanise_get_english_name', [$this, 'hookGetEnglishName'], 10);
    }

    /**
     * Add configured Linguise destination languages as Searchanise engines.
     *
     * @param array $languages Searchanise language codes
     *
     * @return array
     */
    public function hookGetActiveLanguages($languages)
    {
        $options = linguiseGetOptions();
        $enabled_languages = isset($options['enabled_languages']) && is_array($options['enabled_languages']) ? $options['enabled_languages'] : [];

        return array_values(array_unique(array_merge((array) $languages, $enabled_languages)));
    }

    /**
     * Translate selected Searchanise export text using the fragment pipeline.
     *
     * @param array  $content Searchanise export data
     * @param string $language Linguise destination language
     *
     * @return array
     */
    public function hookTranslateExport($content, $language)
    {
        if (!is_array($content) || !WPHelper::isTranslatableLanguage($language)) {
            return $content;
        }

        $translatable_content = $this->getTranslatableExportContent($content);
        $fragments = FragmentHandler::collectFragmentFromJson($translatable_content, false);
        if (empty($fragments)) {
            return $content;
        }

        $html_fragments = FragmentHandler::intoHTMLFragments('searchanise-export', 'catalog', [
            'mode' => 'auto',
            'fragments' => $fragments,
        ]);
        $result = $this->translateFragments('<html><head></head><body>' . $html_fragments . '</body></html>', $language, '/');

        if (empty($result) || isset($result->redirect) || empty($result->content)) {
            return $content;
        }

        $translated_fragments = FragmentHandler::intoJSONFragments($result->content);
        if (empty($translated_fragments['searchanise-export']['catalog']['fragments'])) {
            return $content;
        }

        $translated_content = FragmentHandler::applyTranslatedFragmentsForAuto(
            $content,
            $translated_fragments['searchanise-export']['catalog']['fragments']
        );

        return is_array($translated_content) ? $translated_content : $content;
    }

    /**
     * Return current Linguise destination language for frontend engine selection.
     *
     * @param false $language Searchanise source-language marker
     *
     * @return string|false
     */
    public function hookGetCurrentLanguage($language)
    {
        return WPHelper::getLanguage() ?: $language;
    }

    /**
     * Return a Linguise URL for a Searchanise catalog entry.
     *
     * @param string $url Catalog URL
     * @param string $language Linguise destination language
     *
     * @return string
     */
    public function hookGetLanguageLink($url, $language)
    {
        return WPHelper::isTranslatableLanguage($language) ? $this->translateUrl($language, $url) : $url;
    }

    /**
     * Return translated base URL used by Searchanise frontend widgets.
     *
     * @param string $url Site URL
     * @param string $language Linguise destination language
     * @param array  $params Searchanise frontend parameters
     *
     * @return string
     */
    public function hookGetFrontendUrlPre($url, $language, $params = [])
    {
        return $this->hookGetLanguageLink($url, $language);
    }

    /**
     * Return Searchanise engine display name from Linguise language metadata.
     *
     * @param string $language Linguise language code
     *
     * @return string
     */
    public function hookGetEnglishName($language)
    {
        $languages = WPHelper::getLanguagesInfos();
        return isset($languages->$language->name) ? $languages->$language->name : $language;
    }

    /**
     * Build a sparse export payload containing only catalog text approved for translation.
     *
     * @param array $content Searchanise export data
     *
     * @return array
     */
    private function getTranslatableExportContent($content)
    {
        $translatable = [];

        foreach (['categories', 'pages'] as $type) {
            if (!empty($content[$type]) && is_array($content[$type])) {
                foreach ($content[$type] as $index => $entry) {
                    $this->copyTextFields($entry, $translatable[$type][$index], ['title', 'summary']);
                }
            }
        }

        if (!empty($content['items']) && is_array($content['items'])) {
            foreach ($content['items'] as $index => $item) {
                if (!is_array($item)) {
                    continue;
                }

                $this->copyTextFields($item, $translatable['items'][$index], ['title', 'summary', 'full_description']);
                $this->copyTextArrayFields($item, $translatable['items'][$index], ['categories', 'tags', 'meta_title', 'meta_description', 'meta_keywords']);

                foreach ($item as $key => $value) {
                    if (strpos($key, 'pa_') === 0 && is_array($value)) {
                        $this->copyTextArray($value, $translatable['items'][$index][$key]);
                    }
                }

                if (!empty($item['woocommerce_variants']) && is_array($item['woocommerce_variants'])) {
                    foreach ($item['woocommerce_variants'] as $variant_index => $variant) {
                        $this->copyTextFields($variant, $translatable['items'][$index]['woocommerce_variants'][$variant_index], ['description']);
                    }
                }
            }
        }

        if (!empty($content['schema']) && is_array($content['schema'])) {
            foreach ($content['schema'] as $key => $schema) {
                if (is_array($schema) && isset($schema['facet']) && is_array($schema['facet'])) {
                    $this->copyTextFields($schema['facet'], $translatable['schema'][$key]['facet'], ['title']);
                }
            }
        }

        return $translatable;
    }

    /**
     * Copy named string fields when present.
     *
     * @param array $source Source array
     * @param array $target Sparse target array
     * @param array $fields Allowed field names
     *
     * @return void
     */
    private function copyTextFields($source, &$target, $fields)
    {
        if (!is_array($source)) {
            return;
        }

        foreach ($fields as $field) {
            if (isset($source[$field]) && is_string($source[$field])) {
                $target[$field] = $source[$field];
            }
        }
    }

    /**
     * Copy named array fields containing text values.
     *
     * @param array $source Source array
     * @param array $target Sparse target array
     * @param array $fields Allowed field names
     *
     * @return void
     */
    private function copyTextArrayFields($source, &$target, $fields)
    {
        foreach ($fields as $field) {
            if (isset($source[$field]) && is_array($source[$field])) {
                $this->copyTextArray($source[$field], $target[$field]);
            }
        }
    }

    /**
     * Copy nested string values while preserving source keys and indexes.
     *
     * @param array $source Source array
     * @param array $target Sparse target array
     *
     * @return void
     */
    private function copyTextArray($source, &$target)
    {
        foreach ($source as $key => $value) {
            if (is_string($value)) {
                $target[$key] = $value;
            } elseif (is_array($value)) {
                $this->copyTextArray($value, $target[$key]);
            }
        }
    }
}
