<?php

// Multiple simple helpers that are used in multiple places

/**
 * Return the site URL, or the site URL with the given path.
 *
 * Wraps `home_url` if exists, otherwise use `site_url`.
 *
 * @param string      $path   The path to add to the site URL.
 * @param string|null $scheme The scheme to use (http or https).
 *
 * @return string
 */
function linguiseGetSite($path = '', $scheme = \null)
{
    if (function_exists('home_url')) {
        return home_url($path, $scheme);
    }
    return site_url($path, $scheme);
}


/**
 * Check if we are in subfolders multisite
 *
 * @return boolean
 */
function linguiseIsMultisiteFolder()
{
    // Is multisite subdomains mode or subfolders mode
    $linguise_multisite_subdomains = defined('SUBDOMAIN_INSTALL') && SUBDOMAIN_INSTALL;

    if (is_multisite()) {
        if ($linguise_multisite_subdomains) {
            return false;
        }

        $cached_is_subdomain = get_transient('linguise_multisite_subdomain');
        if ($cached_is_subdomain === '1') {
            return false;
        } elseif ($cached_is_subdomain === '0') {
            // Cached as false, so we need to check the sites
            return true;
        }

        // Not cached yet, so we need to check the sites
        /**
         * Get all sites in the multisite network
         *
         * @var \WP_Site[]
         */
        $sites = get_sites();
        $main_site_id = get_main_site_id();
        $current_site_id = get_current_blog_id();

        $current_site_domain = null;
        $main_site_domain = null;
        foreach ($sites as $site) {
            if ((int)$site->blog_id === $current_site_id) {
                $current_site_domain = $site->domain;
            }
            if ((int)$site->blog_id === $main_site_id) {
                $main_site_domain = $site->domain;
            }
        }

        // If we are in subdomain multisite, we need to check if the current site domain is different from the main site domain
        if (!empty($current_site_domain) && !empty($main_site_domain) && $current_site_domain !== $main_site_domain) {
            set_transient('linguise_multisite_subdomain', '1', DAY_IN_SECONDS);
            return false;
        }

        set_transient('linguise_multisite_subdomain', '0', DAY_IN_SECONDS);
        return true;
    }

    return false;
}

/**
 * Switch Linguise to use main site information
 *
 * This will only switch if we are in subfolders multisite
 *
 * Remember to use linguiseRestoreMultisite() after
 *
 * @return void
 */
function linguiseSwitchMainSite()
{
    // Multisite compatible with subfolders install
    if (linguiseIsMultisiteFolder()) {
        $main_site = get_main_site_id(get_current_network_id());

        switch_to_blog($main_site);
    }
}

/**
 * Restore Multisite
 *
 * This will only restore if we are in subfolders multisite
 *
 * @return void
 */
function linguiseRestoreMultisite()
{
    if (linguiseIsMultisiteFolder()) {
        restore_current_blog();
    }
}
