<?php

namespace Linguise\WordPress\Integrations;

defined('ABSPATH') || die('');

/**
 * Integration for Mailpoet
 */
class MailPoetIntegration extends LinguiseBaseIntegrations
{
    /**
     * Plugin name
     *
     * @var string
     */
    public static $name = 'MailPoet';

    /**
     * A collection of fragment overrides that we want to translate
     *
     * @var array
     */
    protected static $fragment_overrides = [
        [
            'name' => 'mailpoet-form',
            'match' => 'var MailPoetForm = (.*?);',
            'replacement' => 'var MailPoetForm = $$JSON_DATA$$;',
        ],
    ];

    /**
     * Determines if the integration should be loaded.
     *
     * @return boolean
     */
    public function shouldLoad()
    {
        return is_plugin_active('mailpoet/mailpoet.php');
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
