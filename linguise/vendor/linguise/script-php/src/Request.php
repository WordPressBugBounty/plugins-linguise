<?php

namespace Linguise\Vendor\Linguise\Script\Core;

defined('LINGUISE_SCRIPT_TRANSLATION') or die(); // @codeCoverageIgnore

class Request
{
    /**
     * @var null|Request
     */
    private static $_instance = null;

    protected $href;
    protected $protocol;
    protected $hostname;
    protected $pathname;
    protected $base_directory;
    protected $query;
    protected $language;
    protected $trailing_slashes;
    protected $metadata;

    /**
     * Whether the Host header sent by the client matches a hostname that is
     * known to belong to this installation
     *
     * @var bool
     */
    protected $host_trusted = true;

    /**
     * Cached list of authorities (host[:port]) that can safely be used as the
     * origin host, ordered from most to least trustworthy
     *
     * @var null|array
     */
    protected $trusted_hostnames = null;

    private function __construct($silent_debug = \false)
    {
        // Create an empty metadata
        $this->metadata = [];

        $this->parseBaseDirectory();

        $this->language = isset($_GET['linguise_language'])?$_GET['linguise_language']:'';

        $this->protocol = (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' || !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https" : "http";
        $this->hostname = $this->resolveHostname();
        $href = $this->protocol . '://' . $this->hostname . substr($_SERVER['REQUEST_URI'], strlen($this->base_directory . $this->language) + 1);
        $this->pathname = parse_url($href, PHP_URL_PATH);
        $this->pathname = $this->pathname === null ? '' : urldecode($this->pathname);
        $this->query = parse_url($href, PHP_URL_QUERY);
        $this->query = $this->query === null ? '' : $this->query;
        preg_match('/.*?(\/*)$/', $this->pathname, $matches);
        $this->trailing_slashes = $matches[1];

        if (!$silent_debug) {
            Debug::log('Requested url: ' . $this->getRequestedUrl()); // @codeCoverageIgnore
        }
    }

    protected function parseBaseDirectory() {
        $current_cms = CmsDetect::detect();
        $is_wp = defined('ABSPATH') || (isset($_SERVER['IS_WP_REQUEST_TEST']) && $_SERVER['IS_WP_REQUEST_TEST'] === '1');
        if (defined('JPATH_ROOT') && method_exists('JURI', 'getPath')) {
            // We are in a Joomla installation
            /**
             * @disregard P1009 -- Intelliphense false positive
             */
            $this->base_directory = \JUri::getInstance()->root(true); // @codeCoverageIgnore
        } elseif ($is_wp && function_exists('site_url')) {
            // We are in a WordPress installation
            if (function_exists('home_url')) {
                // When home_url exist, use it as it give better and correct result
                $this->base_directory = home_url( '', 'relative' );
            } else {
                $this->base_directory = site_url( '', 'relative' );
            }
        } elseif (in_array($current_cms, ['laravel', 'magento'])) {
            // We are in a Laravel or Magento installation
            $this->base_directory = '';
        } else {
            $base_dir = rtrim(str_replace('\\', '/', Configuration::getInstance()->get('base_dir')), '/');
            if (empty($_SERVER['CONTEXT_DOCUMENT_ROOT']) || (isset($_SERVER['SCRIPT_FILENAME']) && strpos($_SERVER['SCRIPT_FILENAME'], $_SERVER['CONTEXT_DOCUMENT_ROOT']) !== 0)) {
                $document_root = $_SERVER['DOCUMENT_ROOT'];
                $document_root = rtrim(str_replace('\\', '/', $document_root), '/');
                $base_directory = substr($base_dir, strlen($document_root));
            } else {
                $document_root = $_SERVER['CONTEXT_DOCUMENT_ROOT'];
                $document_root = rtrim(str_replace('\\', '/', $document_root), '/');
                $base_directory = substr($base_dir, strlen($document_root));
                $base_directory = trim($base_directory, '/') . $_SERVER['CONTEXT_PREFIX'];
            }

            if ($base_directory) {
                $this->base_directory = '/' . trim($base_directory, '/');
            } else {
                $this->base_directory = ''; // @codeCoverageIgnore
            }
        }
    }

    /**
     * Retrieve singleton instance
     *
     * @return Request
     */
    public static function getInstance($silent_debug = \false) {
        if (is_null(self::$_instance)) {
            self::$_instance = new Request($silent_debug);
        }

        return self::$_instance;
    }

    /**
     * Resolve the host that is used to reach the origin (source) server.
     *
     * The Host header is fully controlled by the client. Using it as-is to
     * build the origin URL lets an unauthenticated attacker make the server
     * issue requests to arbitrary hosts and ports (SSRF through the
     * translation proxy). The requested host is therefore only kept when it
     * is known to belong to this installation, otherwise the first known
     * hostname of the site is used, which keeps the request on the site
     * itself.
     *
     * @return string
     */
    protected function resolveHostname()
    {
        $requested_hostname = isset($_SERVER['HTTP_HOST']) ? $this->normalizeAuthority($_SERVER['HTTP_HOST']) : null;
        $trusted_hostnames = $this->getTrustedHostnames();

        if ($requested_hostname !== null && in_array($requested_hostname, $trusted_hostnames, true)) {
            return $requested_hostname;
        }

        $this->host_trusted = false;

        if (!empty($trusted_hostnames)) {
            if ($requested_hostname !== null) {
                Debug::log('Rejected request host "' . $requested_hostname . '", origin request will use "' . $trusted_hostnames[0] . '" instead');
            }
            return $trusted_hostnames[0];
        }

        // No trustworthy hostname could be determined. The requested host can
        // still be used when it points to a public host, but internal targets
        // are refused: failing the origin request is better than letting the
        // proxy be used as an SSRF vector.
        if ($requested_hostname !== null && !$this->isInternalHostname($this->getHostnameWithoutPort($requested_hostname))) {
            Debug::log('Unable to verify request host "' . $requested_hostname . '": no known hostname for this site');
            return $requested_hostname;
        }

        if ($requested_hostname !== null) {
            Debug::log('Refused request host "' . $requested_hostname . '": internal hostname without a known site hostname');
        }

        return '';
    }

    /**
     * Collect the authorities (host[:port]) that are allowed to be used as the
     * origin host. Values coming from the CMS configuration are listed first
     * because they cannot be influenced by the client, while the web server
     * variables are only used as a last resort.
     *
     * @return array
     */
    protected function getTrustedHostnames()
    {
        if ($this->trusted_hostnames !== null) {
            return $this->trusted_hostnames;
        }

        $candidates = [];

        // Explicit configuration, for setups where the site host can't be
        // detected automatically
        if (defined('LINGUISE_SITE_URL')) {
            $candidates[] = constant('LINGUISE_SITE_URL');
        }
        $configured_site_url = Configuration::getInstance()->get('site_url');
        if (!empty($configured_site_url)) {
            $candidates[] = $configured_site_url;
        }

        // WordPress
        if (function_exists('home_url')) {
            $candidates[] = home_url();
        }
        if (function_exists('site_url')) {
            $candidates[] = site_url();
        }

        // Joomla
        $joomla_live_site = $this->getJoomlaLiveSite();
        if (!empty($joomla_live_site)) {
            $candidates[] = $joomla_live_site;
        }

        // PrestaShop
        if (defined('_PS_ROOT_DIR_') && class_exists('\Configuration') && method_exists('\Configuration', 'get')) {
            foreach (['PS_SHOP_DOMAIN_SSL', 'PS_SHOP_DOMAIN'] as $prestashop_option) {
                $shop_domain = $this->readPrestaShopOption($prestashop_option);
                if (!empty($shop_domain)) {
                    $candidates[] = $shop_domain;
                }
            }
        }

        // OpenCart / Zen Cart
        foreach (['HTTP_SERVER', 'HTTPS_SERVER'] as $url_constant) {
            if (defined($url_constant)) {
                $candidates[] = constant($url_constant);
            }
        }

        // Laravel
        if (function_exists('app') && function_exists('config')) {
            $app_url = null;
            try {
                $app_url = config('app.url');
            } catch (\Throwable $e) {
                $app_url = null; // @codeCoverageIgnore
            }
            if (!empty($app_url) && is_string($app_url)) {
                $candidates[] = $app_url;
            }
        }

        // Web server variables: they are not trustworthy on their own (Apache
        // with UseCanonicalName Off or a catch-all vhost both echo the Host
        // header) but they still cover the usual canonical configurations
        foreach (['SERVER_NAME', 'SERVER_ADDR'] as $server_variable) {
            if (empty($_SERVER[$server_variable])) {
                continue;
            }
            $authority = $_SERVER[$server_variable];
            if (!empty($_SERVER['SERVER_PORT']) && !$this->isDefaultPort($_SERVER['SERVER_PORT'])) {
                $authority .= ':' . (int)$_SERVER['SERVER_PORT'];
            }
            $candidates[] = $authority;
        }

        $trusted_hostnames = [];
        foreach ($candidates as $candidate) {
            $normalized = $this->normalizeAuthority($candidate);
            if ($normalized === null || in_array($normalized, $trusted_hostnames, true)) {
                continue;
            }
            $trusted_hostnames[] = $normalized;
        }

        $this->trusted_hostnames = $trusted_hostnames;

        return $this->trusted_hostnames;
    }

    /**
     * Read the Joomla live_site setting, which is the only hostname of a
     * Joomla installation that is not derived from the request.
     *
     * Note: Uri::root()/JUri::root() are NOT used here on purpose, Joomla
     * builds the site base URI from $_SERVER['HTTP_HOST'] when live_site is
     * empty, so they would return the client supplied host.
     *
     * @return string|null
     */
    protected function getJoomlaLiveSite()
    {
        if (class_exists('JConfig')) {
            $jconfig_vars = get_class_vars('JConfig');
            if (!empty($jconfig_vars['live_site'])) {
                return $jconfig_vars['live_site'];
            }
        } elseif (CmsDetect::detect() === 'joomla') {
            $base_dir = rtrim(str_replace('\\', '/', (string)Configuration::getInstance()->get('base_dir')), '/');
            $joomla_configuration = $base_dir . '/configuration.php';
            if ($base_dir !== '' && file_exists($joomla_configuration)) {
                include_once $joomla_configuration;
                if (class_exists('JConfig')) {
                    $jconfig_vars = get_class_vars('JConfig');
                    if (!empty($jconfig_vars['live_site'])) {
                        return $jconfig_vars['live_site'];
                    }
                }
            }
        }

        // Joomla's own configuration registry, used when JConfig is not
        // available or when live_site is empty in it
        return $this->readJoomlaFactoryLiveSite();
    }

    /**
     * Read live_site through Joomla's configuration registry (Joomla 4/5
     * Joomla\CMS\Factory, Joomla 3 JFactory)
     *
     * @return string|null
     */
    protected function readJoomlaFactoryLiveSite()
    {
        foreach (['\Joomla\CMS\Factory', '\JFactory'] as $factory_class) {
            if (!class_exists($factory_class) || !method_exists($factory_class, 'getConfig')) {
                continue;
            }

            try {
                $config = $factory_class::getConfig();
            } catch (\Throwable $e) {
                continue; // @codeCoverageIgnore
            }

            if (!is_object($config) || !method_exists($config, 'get')) {
                continue; // @codeCoverageIgnore
            }

            $live_site = $config->get('live_site');
            if (is_string($live_site) && $live_site !== '') {
                return $live_site;
            }
        }

        return null;
    }

    /**
     * Safely read a PrestaShop configuration value
     *
     * @param string $option
     * @return string|null
     */
    protected function readPrestaShopOption($option)
    {
        try {
            $value = \Configuration::get($option);
        } catch (\Throwable $e) {
            return null; // @codeCoverageIgnore
        }

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * Normalize an authority (host[:port]) so it can be compared safely.
     * Returns null when the value can't be a valid authority.
     *
     * @param string|null $authority Host header, URL or host[:port] value
     * @return string|null
     */
    protected function normalizeAuthority($authority)
    {
        if (!is_string($authority)) {
            return null;
        }

        $authority = trim($authority);
        if ($authority === '') {
            return null;
        }

        $hostname = $authority;
        $port = null;

        if (strpos($authority, '://') !== false) {
            // Full URL, the path and query are dropped
            $parsed_url = parse_url($authority);
            if (empty($parsed_url['host'])) {
                return null;
            }
            $hostname = $parsed_url['host'];
            $port = isset($parsed_url['port']) ? (int)$parsed_url['port'] : null;
        } else {
            // No path, query, fragment, userinfo, whitespace or control
            // characters when only an authority is expected
            if (preg_match('/[\x00-\x20\x7f\/\\\\@?#]/', $authority)) {
                return null;
            }

            if (preg_match('/^\[([^\]]+)\](?::(\d+))?$/', $authority, $matches)) {
                // IPv6 literal
                $hostname = '[' . $matches[1] . ']';
                $port = isset($matches[2]) && $matches[2] !== '' ? (int)$matches[2] : null;
            } elseif (preg_match('/^([^:\[\]]+)(?::(\d+))?$/', $authority, $matches)) {
                $hostname = $matches[1];
                $port = isset($matches[2]) && $matches[2] !== '' ? (int)$matches[2] : null;
            } else {
                return null;
            }
        }

        $hostname = strtolower($hostname);
        if ($hostname === '' || $hostname === '.' || $hostname === '_') {
            return null;
        }

        if ($hostname[0] !== '[' && !preg_match('/^[a-z0-9]([a-z0-9\.\-]*[a-z0-9])?$/', $hostname)) {
            return null;
        }

        if ($port !== null && $this->isDefaultPort($port)) {
            $port = null;
        }

        return $port === null ? $hostname : $hostname . ':' . $port;
    }

    /**
     * Check if the given port is the default port of the HTTP or HTTPS scheme
     *
     * @param int|string $port
     * @return bool
     */
    protected function isDefaultPort($port)
    {
        $port = (int)$port;

        return $port === 80 || $port === 443;
    }

    /**
     * Check if a hostname points to the local machine or a private network,
     * which must never be reachable through the translation proxy
     *
     * @param string $hostname
     * @return bool
     */
    protected function isInternalHostname($hostname)
    {
        if (!is_string($hostname) || $hostname === '') {
            return true;
        }

        $hostname = strtolower(trim($hostname, '[]'));

        if (in_array($hostname, ['localhost', 'localhost.localdomain', 'ip6-localhost', 'ip6-loopback'], true)) {
            return true;
        }

        foreach (['.localhost', '.local', '.internal', '.home.arpa', '.lan', '.intranet'] as $internal_suffix) {
            if (substr($hostname, -strlen($internal_suffix)) === $internal_suffix) {
                return true;
            }
        }

        if (filter_var($hostname, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        // Loopback, private, link-local and reserved ranges (includes ::1)
        if (filter_var($hostname, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return true;
        }

        // Carrier-grade NAT (100.64.0.0/10) is not covered by the flags above
        if (filter_var($hostname, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            $numeric_hostname = ip2long($hostname);
            if ($numeric_hostname !== false && ($numeric_hostname & 0xFFC00000) === ip2long('100.64.0.0')) {
                return true;
            }
        }

        return false;
    }

    public function getProtocol()
    {
        return $this->protocol;
    }

    public function getHostname()
    {
        return $this->hostname;
    }

    /**
     * Whether the Host header sent by the client matches a hostname known to
     * belong to this installation
     *
     * @return bool
     */
    public function isHostTrusted()
    {
        return $this->host_trusted;
    }

    /**
     * Extract the hostname part of an authority (host[:port])
     *
     * @param string $authority
     * @return string
     */
    protected function getHostnameWithoutPort($authority)
    {
        // IPv6 literal, brackets are kept
        if (preg_match('/^\[[^\]]+\]/', $authority, $matches)) {
            return $matches[0];
        }

        $port_separator = strrpos($authority, ':');

        return $port_separator === false ? $authority : substr($authority, 0, $port_separator);
    }

    public function getPathname($with_trailing_slashes = true)
    {
        if ($with_trailing_slashes === false) {
            return rtrim($this->pathname, '/');
        }
        return $this->pathname;
    }

    public function getQuery($with_mark = false)
    {
        return (($with_mark && $this->query!=='')?'?':'') . $this->query;
    }

    public function getBaseDir()
    {
        return $this->base_directory;
    }

    public function getTrailingSlashes()
    {
        return $this->trailing_slashes;
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function setMetadata($key, $value)
    {
        $this->metadata[$key] = $value;
    }

    public function getMetadata($key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }

    public function getBaseUrl()
    {
        return $this->getProtocol() . '://' . $this->getHostname() . $this->getBaseDir();
    }

    public function getRequestedUrl()
    {
        return $this->getProtocol() . '://' . $this->getHostname() . $this->getBaseDir() . '/' . $this->getLanguage() . $this->getPathname(false) . $this->getTrailingSlashes() . $this->getQuery(true);
    }

    public function setQuery($query) {
        $this->query = $query;
    }

    /**
     * Returns the current query string parsed into a key-value array.
     *
     * @return array
     */
    public function getQueryParams()
    {
        if ($this->query === '') {
            return [];
        }
        parse_str($this->query, $params);
        return $params;
    }

    /**
     * Builds the requested URL keeping only the query params listed in $always_included.
     * All other query params are stripped from the returned URL.
     *
     * @param array $always_included List of query param names to preserve
     * @return string
     */
    public function getRequestedUrlFilteredQuery(array $always_included = [])
    {
        $params = $this->getQueryParams();
        $filtered = !empty($always_included)
            ? array_intersect_key($params, array_flip($always_included))
            : [];
        $query = !empty($filtered) ? '?' . http_build_query($filtered) : '';
        return $this->getProtocol() . '://' . $this->getHostname() . $this->getBaseDir()
            . '/' . $this->getLanguage() . $this->getPathname(false) . $this->getTrailingSlashes()
            . $query;
    }

    /**
     * Retrieve current multilingual non translated url
     *
     * @return string
     */
    public function getNonTranslatedUrl()
    {
        // Find the translated url in database
        $translated_url = '/' . $this->getLanguage() . $this->getPathname(false);
        $non_translated_url = Database::getInstance()->getSourceUrl($translated_url);

        Debug::log('Search translated url in database ' . $translated_url . ', ' . ($non_translated_url===false?'nothing found':'found: '.$non_translated_url), 2);

        if ($non_translated_url === false) {
            // Not found in database, fall back to the current url
            $non_translated_url = $this->getPathname(false);
        }

        return $this->getProtocol() . '://' . $this->getHostname() . $this->getBaseDir() . rtrim($non_translated_url, '/') . $this->getTrailingSlashes() . $this->getQuery(true);
    }
}
