<?php

namespace Linguise\WordPress;

defined('ABSPATH') || die('');

/**
 * A collection of HTML helper functions used by Linguise
 *
 * Mostly used to parse HTML content and protect HTML entities
 * for processing by the Fragment handler system
 */
class HTMLHelper
{
    /**
     * Marker used to protect the HTML entities
     *
     * @var array
     */
    protected static $marker_entity = [
        'common' => 'linguise-internal-entity',
        'named' => 'linguise-internal-entity1',
        'numeric' => 'linguise-internal-entity2',
    ];

    /**
     * Protect the HTML entities in the source code.
     *
     * Adapted from: https://github.com/ivopetkov/html5-dom-document-php/blob/master/src/HTML5DOMDocument.php
     *
     * @param string $source The source code to be protected
     *
     * @return string The protected source code
     */
    public static function protectEntity($source)
    {
        // Replace the entity with our own
        $source = preg_replace('/&([a-zA-Z]*);/', self::$marker_entity['named'] . '-$1-end', $source);
        $source = preg_replace('/&#([0-9]*);/', self::$marker_entity['numeric'] . '-$1-end', $source);

        return $source;
    }

    /**
     * Unprotect the HTML entities in the source code.
     *
     * @param string $html The HTML code to be unprotected
     *
     * @return string The unprotected HTML code
     */
    public static function unprotectEntity($html)
    {
        if (strpos($html, self::$marker_entity['common']) !== false) {
            $html = preg_replace('/' . self::$marker_entity['named'] . '-(.*?)-end/', '&$1;', $html);
            $html = preg_replace('/' . self::$marker_entity['numeric'] . '-(.*?)-end/', '&#$1;', $html);
        }

        return $html;
    }


    /**
     * Protect the HTML string before processing with DOMDocument.
     *
     * It does:
     * - Add CDATA around script tags content
     * - Preserve html entities
     *
     * Adapted from: https://github.com/ivopetkov/html5-dom-document-php/blob/master/src/HTML5DOMDocument.php
     *
     * @param string $source The HTML source code to be protected
     *
     * @return string The protected HTML source code
     */
    private static function protectHTML($source)
    {
        // Add CDATA around script tags content
        $matches = null;
        preg_match_all('/<script(.*?)>/', $source, $matches);
        if (isset($matches[0])) {
            $matches[0] = array_unique($matches[0]);
            foreach ($matches[0] as $match) {
                if (substr($match, -2, 1) !== '/') { // check if ends with />
                    $source = str_replace($match, $match . '<![CDATA[-linguise-dom-internal-cdata', $source); // Add CDATA after the open tag
                }
            }
        }

        $source = str_replace('</script>', '-linguise-dom-internal-cdata]]></script>', $source); // Add CDATA before the end tag
        $source = str_replace('<![CDATA[-linguise-dom-internal-cdata-linguise-dom-internal-cdata]]>', '', $source); // Clean empty script tags
        $matches = null;
        preg_match_all('/\<!\[CDATA\[-linguise-dom-internal-cdata.*?-linguise-dom-internal-cdata\]\]>/s', $source, $matches);
        if (isset($matches[0])) {
            $matches[0] = array_unique($matches[0]);
            foreach ($matches[0] as $match) {
                if (strpos($match, '</') !== false) { // check if contains </
                    $source = str_replace($match, str_replace('</', '<-linguise-dom-internal-cdata-endtagfix/', $match), $source);
                }
            }
        }

        // Preserve html entities
        $source = self::protectEntity($source);

        return $source;
    }


    /**
     * Unclobber the CDATA internal
     *
     * NOTE: Only use this when processing a script content internal data not the whole HTML data.
     *
     * This is used to protect the CDATA internal data from being mangled by the DOMDocument.
     *
     * @param string $html_data The HTML data to be unclobbered
     *
     * @return string The unclobbered HTML data
     */
    public static function unclobberCdataInternal($html_data)
    {
        // Unclobber the CDATA internal
        $html_data = str_replace('<![CDATA[-linguise-dom-internal-cdata', '', $html_data);
        $html_data = str_replace('-linguise-dom-internal-cdata]]>', '', $html_data);
        $html_data = str_replace('<-linguise-dom-internal-cdata-endtagfix/', '</', $html_data);

        return $html_data;
    }

    /**
     * Clobber back the CDATA internal
     *
     * NOTE: Only use this when processing a script content internal data not the whole HTML data.
     *
     * This is used to protect the CDATA internal data from being mangled by the DOMDocument.
     *
     * @param string $html_data The HTML data to be clobbered
     *
     * @return string The clobbered HTML data
     */
    public static function clobberCdataInternal($html_data)
    {
        // Append prefix and suffix
        return '<![CDATA[-linguise-dom-internal-cdata' . $html_data . '-linguise-dom-internal-cdata]]>';
    }

    /**
     * Load the HTML data into a DOMDocument object.
     *
     * @param string $html_data The HTML data to be loaded
     *
     * @return \DOMDocument|null The loaded HTML DOM object
     */
    public static function loadHTML($html_data)
    {
        // Check if DOMDocument is available or xml extension is loaded
        if (!class_exists('\\DOMDocument') || !extension_loaded('xml')) {
            return null;
        }

        // Load HTML
        $html_dom = new \DOMDocument();
        @$html_dom->loadHTML(self::protectHTML($html_data), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged

        /**
         * Avoid mangling the CSS and JS code with encoded HTML entities
         *
         * See: https://www.php.net/manual/en/domdocument.savehtml.php#119813
         *
         * While testing, we found this issue with css inner text got weirdly mangled
         * following the issue above, I manage to correct this by adding proper content-type equiv
         * which is really weird honestly but it manages to fix the issue.
         */
        $has_utf8 = false;
        $meta_attrs = $html_dom->getElementsByTagName('meta');
        foreach ($meta_attrs as $meta) {
            if ($meta->hasAttribute('http-equiv') && strtolower($meta->getAttribute('http-equiv')) === 'content-type') {
                // force UTF-8s
                $meta->setAttribute('content', 'text/html; charset=UTF-8');
                $has_utf8 = true;
                break;
            }
        }

        if (!$has_utf8) {
            // We didn't found any meta tag with content-type equiv, add our own
            $meta = $html_dom->createElement('meta');
            $meta->setAttribute('http-equiv', 'Content-Type');
            $meta->setAttribute('content', 'text/html; charset=UTF-8');
            $head_doc = $html_dom->getElementsByTagName('head');
            if ($head_doc->length > 0) {
                // Add to head tag on the child as the first node
                $head = $head_doc->item(0);
                @$head->insertBefore($meta, $head->firstChild); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged -- ignore any errors for now
            }
        }

        return $html_dom;
    }

    /**
     * Save the HTML data into a string.
     *
     * @param \DOMDocument $dom The DOMDocument object to be saved
     *
     * @return string The saved HTML data
     */
    public static function saveHTML($dom)
    {
        // Save HTML
        $html_data = $dom->saveHTML();
        if ($html_data === false) {
            return '';
        }

        // Unprotect HTML entities
        $html_data = self::unprotectEntity($html_data);

        // Unprotect HTML
        $code_to_be_removed = [
            'linguise-dom-internal-content',
            '<![CDATA[-linguise-dom-internal-cdata',
            '-linguise-dom-internal-cdata]]>',
            '-linguise-dom-internal-cdata-endtagfix'
        ];
        foreach ($code_to_be_removed as $code) {
            $html_data = str_replace($code, '', $html_data);
        }

        // Unmangle stuff like &amp;#xE5;
        $html_data = preg_replace('/&amp;#x([0-9A-Fa-f]+);/', '&#x$1;', $html_data);

        return $html_data;
    }
}
