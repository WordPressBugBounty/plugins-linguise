<?php

namespace Linguise\WordPress\Integrations;

defined('ABSPATH') || die('');

/**
 * Integration for MailOptin
 *
 * Include some lightbox data to be translated
 */
class MailOptinIntegration extends LinguiseBaseIntegrations
{
    /**
     * Plugin name
     *
     * @var string
     */
    public static $name = 'MailOptin';

    /**
     * A collection of fragment overrides that we want to translate
     *
     * @var array
     */
    protected static $fragment_overrides = [
        [
            'name' => 'mailoptin-lightbox',
            'match' => '<script type="text\/javascript">var (.*)_lightbox = (.*);<\/script>',
            'replacement' => '<script text="text/javascript">var $1_lightbox = $$JSON_DATA$$;</script>',
            'position' => 2,
        ],
    ];

    /**
     * Determines if the integration should be loaded.
     *
     * @return boolean
     */
    public function shouldLoad()
    {
        return is_plugin_active('mailoptin/mailoptin.php');
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
