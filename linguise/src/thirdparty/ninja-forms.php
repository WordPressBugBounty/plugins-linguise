<?php

namespace Linguise\WordPress\Integrations;

defined('ABSPATH') || die('');

/**
 * Integration for Ninja Forms
 *
 * Include some data to be translated
 */
class NinjaFormsIntegration extends LinguiseBaseIntegrations
{
    /**
     * Plugin name
     *
     * @var string
     */
    public static $name = 'Ninja Form';

    /**
     * A collection of fragment overrides that we want to translate
     *
     * @var array
     */
    protected static $fragment_overrides = [
        [
            'name' => 'ninjaforms_fields',
            'match' => 'form.fields=(.*?);nfForms',
            'replacement' => 'form.fields=$1;nfForms',
        ],
        [
            'name' => 'ninjaforms_i18n',
            'match' => 'nfi18n = (.*?);',
            'replacement' => 'nfi18n = $$JSON_DATA$$;',
        ],
        [
            'name' => 'ninjaforms_settings',
            'match' => 'form.settings=(.*?);form',
            'replacement' => 'form.settings=$$JSON_DATA$$;form',
        ],
    ];

    /**
     * Determines if the integration should be loaded.
     *
     * @return boolean
     */
    public function shouldLoad()
    {
        return is_plugin_active('ninja-forms/ninja-forms.php');
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
