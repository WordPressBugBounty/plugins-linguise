<?php

namespace Linguise\Vendor\Linguise\Script\Core;

defined('LINGUISE_SCRIPT_TRANSLATION') or die();

class Response {

    /**
     * @var null|Response
     */
    private static $_instance = null;

    /**
     * @var null|string Content to translate
     */
    protected $content = null;

    /**
     * @var null|string Url to redirect the user to
     */
    protected $redirect = null;

    /**
     * @var int Response code received
     */
    protected $response_code = null;

    /**
     * @var null|int Content type
     */
    protected $content_type = null;

    /**
     * @var null|string Content encoding received from the origin (gzip, deflate, br, ...)
     */
    protected $content_encoding = null;

    /**
     * @var array Headers
     */
    protected $headers = [];


    /**
     * @var array Cookies
     */
    protected $cookies = [];

    /**
     * Retrieve singleton instance
     *
     * @return Response|null
     */
    public static function getInstance() {

        if(is_null(self::$_instance)) {
            self::$_instance = new Response();
        }

        return self::$_instance;
    }

    /**
     * Set html content to be translated
     *
     * @param $content string Html content to translate
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * Return current response content
     *
     * @return string|null
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Clear the content
     *
     * @return void
     */
    public function clearContent()
    {
        $this->content = null;
    }

    public function setResponseCode($response_code, $overwrite = true)
    {
        if ($this->response_code === null || $overwrite === true) {
            $this->response_code = $response_code;
        }
    }

    public function getResponseCode()
    {
        return $this->response_code;
    }

    public function setContentType($content_type)
    {
        $this->content_type = $content_type;
    }

    /**
     * Set the content encoding received from the origin (e.g. gzip, deflate, br).
     * When set, the response is re-compressed with this encoding before being sent.
     *
     * @param string|null $content_encoding
     */
    public function setContentEncoding($content_encoding)
    {
        $this->content_encoding = $content_encoding;
    }

    /**
     * Get the content encoding received from the origin
     *
     * @return string|null
     */
    public function getContentEncoding()
    {
        return $this->content_encoding;
    }

    /**
     * Get current redirect url
     * 
     * @return string|null
     */
    public function getRedirect()
    {
        return $this->redirect;
    }

    /**
     * Set redirection
     *
     * @param $url string Url to redirect the user to
     * @param int $response_code int Response code to set
     */
    public function setRedirect($url, $response_code = 303)
    {
        $this->redirect = $url;
        $this->setResponseCode($response_code, true);
    }

    /**
     * Add header
     *
     * @param $name
     * @param $value
     */
    public function addHeader($name, $value)
    {
        $name = ucwords($name, '-');
        if ($name === 'Set-Cookie') {
            $cookie_parser = new SetCookie;
            $this->cookies[] = $cookie_parser->fromString($value);
        } else {
            $this->headers[$name] = $value;
        }
    }

    /**
     * Check if a header is set
     *
     * @param $name
     * @return bool
     */
    public function hasHeader($name) {
        if (!empty($this->headers[$name])) {
            return true;
        }
        return false;
    }

    /**
     * Retrieve a header already set
     *
     * @param $name
     * @return null|String
     */
    public function getHeader($name) {
        if (!empty($this->headers[$name])) {
            return $this->headers[$name];
        }
        return null;
    }

    public function addCookie($name, $value, $expires = 0, $path = "", $domain = "", $secure = false, $httpOnly = false) {
        $cookie_parser = new SetCookie([
            'Name'     => $name,
            'Value'    => $value,
            'Domain'   => $domain,
            'Path'     => $path,
            'Expires'  => $expires,
            'Secure'   => $secure,
            'HttpOnly' => $httpOnly
        ]);
        $this->cookies[] = $cookie_parser;
    }

    /**
     * Get Cookies
     * 
     * @return SetCookie[] Cookies
     */
    public function getCookies()
    {
        return $this->cookies;
    }

    /**
     * Actually redirect
     */
    protected function redirect()
    {
        if ($this->redirect === null) {
            // Nothing to do
            return;
        }

        // Make sure nothing we don't want is echoed
        //ob_end_clean();

        header('Location: '.$this->redirect, true, 301);
    }

    /**
     * Check if a response header must be skipped.
     *
     * @param string $header_name
     * @return bool
     */
    protected function shouldSkipHeader($header_name)
    {
        $header_name = strtolower($header_name);

        if (in_array($header_name, array('transfer-encoding', 'location', 'content-encoding'))) {
            return true;
        }

        // Remove LiteSpeed headers, usually like X-LiteSpeed-*.
        if (strpos($header_name, 'x-litespeed') === 0) {
            return true;
        }

        return false;
    }

    /**
     * Encode a response body using the Content-Encoding the origin response used.
     *
     * Encodings are applied in the order they appear in the header (e.g. "gzip, br").
     * Returns null when the encoding is unknown, unsupported by PHP, or the payload
     * could not be compressed, in which case the caller should send the body as-is.
     *
     * @param string $content  Content to compress
     * @param string $encoding Content-Encoding header value
     * @return string|null Compressed body, or null if it could not be compressed
     */
    protected function encodeContentEncoding($content, $encoding)
    {
        $encodings = array_map('trim', explode(',', $encoding));

        foreach ($encodings as $current_encoding) {
            $current_encoding = strtolower($current_encoding);

            if ($current_encoding === 'identity') {
                continue;
            }

            $encoded = $this->encodeSingleContentEncoding($content, $current_encoding);
            if ($encoded === null) {
                return null;
            }

            $content = $encoded;
        }

        return $content;
    }

    /**
     * Compress content with a single Content-Encoding token.
     *
     * @param string $content  Content to compress
     * @param string $encoding Single lowercase encoding token
     * @return string|null Compressed body, or null when compression is not possible
     */
    protected function encodeSingleContentEncoding($content, $encoding)
    {
        switch ($encoding) {
            case 'gzip':
            case 'x-gzip':
                if (function_exists('gzencode')) {
                    $encoded = gzencode($content);
                    if ($encoded !== false) {
                        return $encoded;
                    }
                }

                return null;

            case 'deflate':
                if (function_exists('gzdeflate')) {
                    $encoded = gzdeflate($content);
                    if ($encoded !== false) {
                        return $encoded;
                    }
                }

                return null;

            case 'br':
                if (function_exists('brotli_compress')) {
                    // call_user_func avoids a static-analysis "undefined function"
                    // warning when the optional brotli extension is not installed.
                    $encoded = @call_user_func('brotli_compress', $content);
                    if ($encoded !== false) {
                        return $encoded;
                    }
                }

                return null;

            case 'zstd':
                if (function_exists('zstd_compress')) {
                    // call_user_func avoids a static-analysis "undefined function"
                    // warning when the optional zstd extension is not installed.
                    $encoded = @call_user_func('zstd_compress', $content);
                    if ($encoded !== false) {
                        return $encoded;
                    }
                }

                return null;

            default:
                return null;
        }
    }

    public function end()
    {
        ignore_user_abort(true);

        // Remove current request headers
        header_remove();

        // Remove all content that could have been sent to buffer
        ob_end_clean();

        // Turn on output buffering
        ob_start();

        if ($this->content) {
            $content = $this->content;
            // Re-compress the (translated) content with the encoding the origin
            // response used, so the client still receives a compressed response.
            if ($this->content_encoding !== null && Configuration::getInstance()->get('compress_response') !== false) {
                $compressed = $this->encodeContentEncoding($content, $this->content_encoding);
                if ($compressed !== null) {
                    $content = $compressed;
                    header('Content-Encoding: ' . $this->content_encoding);
                }
            }
            echo $content;
        }

        // Set redirection if any
        $this->redirect();

        foreach ($this->headers as $header_name => $header_value) {
            if ($this->shouldSkipHeader($header_name)) continue;
            header($header_name.': '.$header_value);
        }

        foreach ($this->cookies as $cookie) {
            $cookie_name = $cookie->getName();
            // PHP 8.0+ throws a fatal ValueError when setrawcookie() receives an empty name
            if (PHP_VERSION_ID >= 80000 && ($cookie_name === null || $cookie_name === '')) {
                continue;
            }
            setrawcookie($cookie_name, $cookie->getValue(), $cookie->getExpires(), $cookie->getPath(), $cookie->getDomain(), $cookie->getSecure(), $cookie->getHttpOnly());
        }

        header('Connection: close');
        header_remove('Content-Length');

        http_response_code($this->response_code);

        ob_end_flush();

        // @codeCoverageIgnoreStart
        if (!defined('LINGUISE_SCRIPT_TESTING')) {
            exit(0);
        }
        // @codeCoverageIgnoreEnd
    }
}
