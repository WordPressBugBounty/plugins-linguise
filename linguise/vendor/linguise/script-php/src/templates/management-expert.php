<?php

use Linguise\Vendor\Linguise\Script\Core\Configuration;
use Linguise\Vendor\Linguise\Script\Core\Database;
use Linguise\Vendor\Linguise\Script\Core\Request;
use Linguise\Vendor\Linguise\Script\Core\Session;
use Linguise\Vendor\Linguise\Script\Core\Templates\Helper as AdminHelper;

defined('LINGUISE_MANAGEMENT') or die('No access to this page.');
defined('LINGUISE_AUTHORIZED') or die('Access denied.');

$translation_strings_root = [
    'expert' => [
        'title' => __('Expert mode', 'linguise'),
        'help' => __('You can modify additional Linguise configuration here. Please be careful, as this is an advanced feature.', 'linguise'),
    ],

    'save' => __('Save settings', 'linguise'),
    'go-back' => __('Go back', 'linguise'),

    'multisite' => [
        'header' => __('You are using a multisite installation.', 'linguise'),
        'warning' => __('Please modify Linguise settings from your main site.', 'linguise'),
        'button' => __('Go to main site', 'linguise'),
    ],
];

$configuration = Configuration::getInstance()->toArray();
$options = Database::getInstance()->retrieveOtherParam('linguise_options');

// only do string/number/boolean attributes
$validConfiguration = [];
// This is keys that can be set from the main Dashboard.
$disallowed_keys = ['token', 'cache_enabled', 'cache_max_size', 'debug'];
foreach ($configuration as $key => $data) {
    if (in_array($key, $disallowed_keys)) {
        continue;
    }

    // skip key starting with _
    if (strpos($key, '_') === 0) {
        continue;
    }

    if ($data['value'] === null) {
        // assume it's a string
        $data['value'] = '';
    }

    if (is_string($data['value']) || is_numeric($data['value']) || is_bool($data['value'])) {
        $validConfiguration[$key] = $data;
    }
}

if (isset($options['expert_mode'])) {
    foreach ($options['expert_mode'] as $key => $value) {
        if (isset($validConfiguration[$key])) {
            $validConfiguration[$key]['value'] = $value;
        }
    }
}

$validConfiguration['dashboard_host'] = [
    'value' => isset($expert_mode['dashboard_host']) ? $expert_mode['dashboard_host'] : '',
    'doc' => 'The host of the Linguise Dashboard. Default is dashboard.linguise.com',
    'key' => 'dashboard_host'
];
$validConfiguration['dashboard_port'] = [
    'value' => isset($expert_mode['dashboard_port']) ? (int)$expert_mode['dashboard_port'] : '443',
    'doc' => 'The port of the Linguise Dashboard. Default is 443',
    'key' => 'dashboard_port'
];

/**
 * Convert key to "word"-like
 *
 * @param string $key Key to convert
 *
 * @return string Converted key
 */
function keyToWord($key)
{
    return ucwords(str_replace('_', ' ', $key));
}

function make_action_url($action) {
    $root = rtrim(Request::getInstance(true)->getBaseUrl(), '/');
    if (empty($action)) {
        return $root . '/' . AdminHelper::getManagementBase();
    }
    return $root . '/' . AdminHelper::getManagementBase() . '?linguise_action=' . $action;
}

$main_root = Request::getInstance()->getBaseUrl() . '/' . AdminHelper::getManagementBase();

?>

<div class="linguise-config-wrapper only-single">
    <form action="<?php echo esc_url(make_action_url('') . '?ling_mode=expert'); ?>" method="post" class="content-area">
        <input type="hidden" name="_token" value="<?php echo esc_attr(Session::getInstance()->getCsrfToken('linguise_config')); ?>" />
        <input type="hidden" name="linguise_action" value="update-config" />
        <div class="tab-content active">
            <div class="tab-linguise-options">
                <div class="linguise-options full-width">
                    <h2 class="m-0 text-2xl font-bold text-black">
                        <?php echo esc_html($translation_strings_root['expert']['title']); ?>
                        <span class="material-icons help-tooltip" data-tippy="<?php echo esc_attr($translation_strings_root['expert']['help']); ?>" data-tippy-direction="right">
                            help_outline
                        </span>
                    </h2>
                    <div class="flex flex-col w-full gap-2 mt-4 flex-auto-wrap">
                        <?php foreach ($validConfiguration as $key => $data) { ?>
                        <div class="flex flex-row items-center gap-4 mt-2">
                            <label for="<?php echo esc_attr($key); ?>" class="m-0 text-base text-neutral linguise-expert">
                                <?php echo esc_html(keyToWord($key)) ?>
                                <?php if ($data['doc'] !== null) { ?>
                                <span class="material-icons help-tooltip" data-tippy="<?php echo esc_attr($data['doc']); ?>">
                                    help_outline
                                </span>
                                <?php } ?>
                            </label>
                            <?php if (is_bool($data['value'])) { ?>
                            <input id="<?php echo esc_attr($key); ?>" type="checkbox" class="linguise-input checkbox-mode !m-0 align-middle" name="expert_linguise[<?php echo esc_attr($key); ?>]" value="1" <?php AdminHelper::checked($data['value'], true); ?>>
                            <?php } else { ?>
                            <input id="<?php echo esc_attr($key); ?>" type="text" class="linguise-input rounder" name="expert_linguise[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($data['value']); ?>">
                            <?php } ?>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex flex-row w-full justify-end box-border gap-4 px-4 save-settings-btn">
            <a href="<?php echo esc_url($main_root); ?>" class="linguise-btn rounder outlined">
                <?php echo esc_html($translation_strings_root['go-back']); ?>
            </a>
            <input type="submit" class="linguise-btn rounder save-settings-input" value="<?php echo esc_attr($translation_strings_root['save']); ?>" />
        </div>
    </form>
</div>
