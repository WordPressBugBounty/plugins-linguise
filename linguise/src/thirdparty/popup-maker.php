<?php

namespace Linguise\WordPress\Integrations;

defined('ABSPATH') || die('');

/**
 * Integration for Popup Maker
 */
class PopupMakerIntegration extends LinguiseBaseIntegrations
{
    /**
     * Plugin name
     *
     * @var string
     */
    public static $name = 'Popup Maker';

    /**
     * A collection of fragment overrides that we want to translate
     *
     * @var array
     */
    protected static $fragment_overrides = [
        [
            'name' => 'popup-maker-bart',
            'match' => 'var pumAdminBarText = (.*?);',
            'replacement' => 'var pumAdminBarText = $$JSON_DATA$$;',
        ],
    ];

    /**
     * Determines if the integration should be loaded.
     *
     * @return boolean
     */
    public function shouldLoad()
    {
        return is_plugin_active('popup-maker/popup-maker.php');
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
