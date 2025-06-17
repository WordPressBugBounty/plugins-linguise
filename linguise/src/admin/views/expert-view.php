<?php

defined('ABSPATH') || die('');

use Linguise\WordPress\Admin\Helper as AdminHelper;

// Admin helper
include_once(LINGUISE_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'Helper.php');

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

$configuration = linguiseGetConfiguration();
$options = linguiseGetOptions();

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

$expert_mode = isset($options['expert_mode']) ? $options['expert_mode'] : [];
$api_host = isset($expert_mode['api_host']) ? $expert_mode['api_host'] : 'api.linguise.com';
$api_port = isset($expert_mode['api_port']) ? $expert_mode['api_port'] : '443';

$validConfiguration['api_host'] = [
    'value' => $api_host,
    'doc' => 'The host of the Linguise API server. Default is api.linguise.com',
    'key' => 'api_host'
];
$validConfiguration['api_port'] = [
    'value' => $api_port,
    'doc' => 'The port of the Linguise API server. Default is 443',
    'key' => 'api_port'
];

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

$multisite_data = AdminHelper::getMultisiteInfo('expert');

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

$main_root = admin_url('admin.php?page=linguise');

?>

<div class="linguise-config-wrapper only-single">
<?php if ($multisite_data['multisite']) : ?>
    <div class="multisite-warning">
        <!-- Multisite warning area -->
        <div class="backdrop"></div>
        <div class="multisite-wrapper">
            <div class="flex flex-col items-center">
                <h2 class="m-0 text-2xl font-bold text-black">
                    <?php echo esc_html($translation_strings_root['multisite']['header']); ?>
                </h2>
                <div class="my-2 mb-4">
                    <?php echo esc_html($translation_strings_root['multisite']['warning']); ?>
                </div>
                <div class="mt-4">
                    <a href="<?php echo esc_url($multisite_data['main_site']); ?>" class="linguise-btn rounder" target="_blank" rel="noreferrer noopener">
                        <?php echo esc_html($translation_strings_root['multisite']['button']); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
    <form action="" method="post" class="content-area">
        <?php wp_nonce_field('linguise-expert-settings', 'linguise_nonce'); ?>
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
                            <input id="<?php echo esc_attr($key); ?>" type="checkbox" class="linguise-input !m-0 align-middle" name="expert_linguise[<?php echo esc_attr($key); ?>]" value="1" <?php checked($data['value'], true); ?>>
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
