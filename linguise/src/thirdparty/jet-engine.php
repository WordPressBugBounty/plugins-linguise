<?php

namespace Linguise\WordPress\Integrations;

defined('ABSPATH') || die('');

/**
 * Integration for Jet Engine
 *
 * Include some URL to be translated
 */
class JetEngineIntegration extends LinguiseBaseIntegrations
{
    /**
     * Plugin name
     *
     * @var string
     */
    public static $name = 'JetEngine';

    /**
     * A collection of HTML attributes that you want to translate
     *
     * @var array
     */
    protected static $fragment_attributes = [
        [
            'name' => 'jeng-data-url',
            'key' => 'data-url',
            'mode' => 'string',
            'cast' => 'link',
            'matchers' => [
                [
                    'type' => 'class',
                    'value' => ['jet-engine-listing-overlay-wrap'],
                    'mode' => 'any'
                ]
            ]
        ]
    ];

    /**
     * Determines if the integration should be loaded.
     *
     * @return boolean
     */
    public function shouldLoad()
    {
        return is_plugin_active('jet-engine/jet-engine.php');
    }

    /**
     * Load the integration
     *
     * @return void
     */
    public function init()
    {
        // Stub
    }

    /**
     * Unload the integration
     *
     * @return void
     */
    public function destroy()
    {
        // Stub
    }
}
