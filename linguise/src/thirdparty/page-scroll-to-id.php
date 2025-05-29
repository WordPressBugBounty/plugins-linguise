<?php

namespace Linguise\WordPress\Integrations;

defined('ABSPATH') || die('');

/**
 * Integration for Page scroll to id
 *
 * Exclude some js vars
 */
class PageScrollToIdIntegration extends LinguiseBaseIntegrations
{
    /**
     * Plugin name
     *
     * @var string
     */
    public static $name = 'Page scroll to id';

    /**
     * A collection of fragment keys that will be translated
     *
     * @var array{key:string, mode:'exact'|'path'|'wildcard'|'regex'|'regex_full',kind:'allow'|'deny'}
     */
    protected static $fragment_keys = [
        [
            'key' => 'excludeSelector',
            'mode' => 'regex_full',
            'kind' => 'deny',
        ],
    ];

    /**
     * Load the integration
     *
     * @return void
     */
    public function init()
    {
        // Do nothing
    }

    /**
     * Decides if the Page scroll to id integration should be loaded.
     *
     * @return boolean
     */
    public function shouldLoad()
    {
        return is_plugin_active('page-scroll-to-id/malihu-pagescroll2id.php');
    }

    /**
     * Unload the integration
     *
     * @return void
     */
    public function destroy()
    {
        // Do nothing
    }
}
