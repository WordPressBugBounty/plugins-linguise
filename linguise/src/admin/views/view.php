<?php

defined('ABSPATH') || die('');

use Linguise\WordPress\Admin\Helper as AdminHelper;
use Linguise\WordPress\Helper as MainHelper;

// Admin helper
require_once(LINGUISE_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'Helper.php');
// Main helper
require_once(LINGUISE_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR  . 'Helper.php');

$translation_strings_root = [
    'tabs' => [
        'main-settings' => __('Main settings', 'linguise'),
        'advanced' => __('Advanced', 'linguise'),
        'help' => __('Help', 'linguise'),
    ],

    'chat-with-us' => __('Chat with us', 'linguise'),
    'linguise' => [
        'edit' => __('Edit your translations', 'linguise'),
        'docs' => __('Documentation', 'linguise'),
        'dashboard' => __('Linguise dashboard', 'linguise'),
    ],

    'preview' => __('Language List Preview', 'linguise'),
    'preview_btn' => __('Preview', 'linguise'),
    'save-btn' => __('Save settings', 'linguise'),

    'modal-save' => [
        'save' => __('Activate translation', 'linguise'),
    ],
    'modal-saving' => [
        'title' => __('Activating the translation.', 'linguise'),
        'message' => __('Please wait.', 'linguise'),
    ],
    'modal-saved' => [
        'title' => __('Activation was successful!', 'linguise'),
        'button' => __('Continue plugin setup', 'linguise'),
    ],
    'modal-abort' => [
        'title' => __('Are you sure you want to leave?', 'linguise'),
        'message' => __('All progress will be reset.', 'linguise'),
        'yes' => __('Yes, leave', 'linguise'),
        'no' => __('Cancel', 'linguise'),
    ],
    'modal-error' => [
        'title' => __('Something went wrong.', 'linguise'),
        'message' => __('An unexpected error has occured, please try again.', 'linguise'),
        'yes' => __('Try again', 'linguise'),
        'no' => __('Back', 'linguise'),
    ],

    'settings-hidden' => [
        'warning' => __('Registration is required to access all settings.', 'linguise'),
        'banner' => __('Settings will be available after registration.', 'linguise'),
    ],

    'multisite' => [
        'header' => __('You are using a multisite installation.', 'linguise'),
        'warning' => __('Please modify Linguise settings from your main site.', 'linguise'),
        'button' => __('Go to main site', 'linguise'),
    ]
];

$linguise_tabs = [
    'main_settings' => [
        'name' => $translation_strings_root['tabs']['main-settings'],
        'content' => 'main-settings',
        'icon' => 'translate',
    ],
    'advanced' => [
        'name' => $translation_strings_root['tabs']['advanced'],
        'content' => 'advanced',
        'icon' => 'code',
    ],
    'help' => [
        'name' => $translation_strings_root['tabs']['help'],
        'content' => 'help',
        'icon' => 'help',
        'no-save' => true,
    ],
];

$options = linguiseGetOptions();

$has_api_key = !empty($options['token']);

$languages_contents = MainHelper::getLanguagesInfos();
$language_enabled_param = isset($options['enabled_languages']) ? $options['enabled_languages'] : array();
$sorted_languages = [];

foreach ($language_enabled_param as $language) {
    if ($language === $options['default_language']) {
        continue;
    }

    if (!isset($languages_contents->$language)) {
        continue;
    }

    $sorted_languages[$language] = $languages_contents->$language;
}

$is_debug = !empty($options['debug']);

// $language_contents is an object
foreach ($languages_contents as $lang_code => $language_value) {
    if (isset($sorted_languages[$lang_code])) {
        continue;
    }
    $sorted_languages[$lang_code] = $language_value;
}

$language_contents = $sorted_languages;

$multisite_data = AdminHelper::getMultisiteInfo();

$language_name_display = isset($options['language_name_display']) ? $options['language_name_display'] : 'en';
// Get from module parameters the enable languages
$languages_enabled_param = isset($options['enabled_languages']) ? $options['enabled_languages'] : array();
// Generate language list with default language as first item
if ($language_name_display === 'en') {
    $language_list = array($options['default_language'] => $sorted_languages[$options['default_language']]->name);
} else {
    $language_list = array($options['default_language'] => $sorted_languages[$options['default_language']]->original_name);
}

$expert_mode = isset($options['expert_mode']) ? $options['expert_mode'] : [];
$dashboard_host = isset($expert_mode['dashboard_host']) ? $expert_mode['dashboard_host'] : '';
$dashboard_port = isset($expert_mode['dashboard_port']) ? (int)$expert_mode['dashboard_port'] : 443;

$config = array_merge(array(
    'all_languages' => $sorted_languages,
    'languages' => $language_list,
    'demo_mode' => true,
    'dashboard_url' => [
        'host' => $dashboard_host,
        'port' => $dashboard_port,
    ]
), $options);

$original_web_locale = get_locale();
$original_web_locale_map = MainHelper::getLinguiseCodeFromWPLocale($original_web_locale);
if (!empty($original_web_locale_map)) {
    $config['original_default'] = $original_web_locale_map;
} else {
    $config['original_default'] = $options['default_language'];
}

$config_array = array('vars' => array('configs' => $config));

?>

<div class="linguise-config-wrapper">
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
    <div class="drawer-area">
        <div class="drawer-header">
            <svg class="linguise-logo" width="95" height="24" viewBox="0 0 95 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <g>
                    <path d="M28.3935 16.8338C28.6146 16.8338 28.7376 16.9565 28.7376 17.1772V18.5754C28.7376 18.7712 28.6146 18.894 28.3935 18.894H25.9085C23.3999 18.894 21.8506 17.0296 21.8506 14.5752V1.96223C21.8506 1.7664 21.9736 1.64368 22.1699 1.64368H23.7441C23.9652 1.64368 24.0883 1.7664 24.0883 1.96223V14.5021C24.0883 15.9003 24.8996 16.8338 26.0315 16.8338H28.3935Z" fill="currentColor" />
                    <path d="M31.3208 3.75343C31.3208 3.0171 31.8377 2.47791 32.5757 2.47791C33.3138 2.47791 33.8555 3.0184 33.8555 3.75343C33.8555 4.41665 33.2902 5.00546 32.5757 5.00546C31.8612 5.00546 31.3208 4.46496 31.3208 3.75343ZM31.5171 18.4775V6.86979C31.5171 6.67396 31.6401 6.55123 31.8612 6.55123H33.2876C33.5087 6.55123 33.6317 6.67396 33.6317 6.86979V18.4775C33.6317 18.6981 33.5087 18.8208 33.2876 18.8208H31.8612C31.6401 18.8208 31.5171 18.6981 31.5171 18.4775Z" fill="currentColor" />
                    <path d="M37.2734 18.4788V10.2825C37.2734 7.95079 38.8228 6.28229 41.1102 6.28229C43.3976 6.28229 44.9469 7.95079 44.9469 10.2825V18.4788C44.9469 18.6994 44.8239 18.8221 44.6028 18.8221H43.1764C42.9553 18.8221 42.8323 18.6994 42.8323 18.4788V10.2081C42.8323 9.05528 42.1924 8.26934 41.1102 8.26934C40.028 8.26934 39.3881 9.05528 39.3881 10.2081V18.4788C39.3881 18.6994 39.2651 18.8221 39.0439 18.8221H37.6176C37.3964 18.8221 37.2734 18.6994 37.2734 18.4788Z" fill="currentColor" />
                    <path d="M56.1379 10.2812V19.9997C56.1379 22.4294 54.6134 24 52.2266 24C49.8397 24 48.4147 22.4542 48.3152 20.0494C48.2904 19.8287 48.4134 19.706 48.6345 19.706H50.0857C50.282 19.706 50.405 19.8039 50.4299 20.0494C50.5032 21.2766 51.1679 21.9881 52.2253 21.9881C53.3572 21.9881 54.0206 21.2766 54.0206 20.0742V17.6445C53.5037 18.185 52.8154 18.5036 52.0525 18.5036C49.8633 18.5036 48.2891 16.8351 48.2891 14.5034V10.2825C48.2891 7.95079 49.8633 6.28229 52.2253 6.28229C54.5872 6.28229 56.1366 7.95079 56.1366 10.2825L56.1379 10.2812ZM54.0232 14.5765V10.2081C54.0232 9.05528 53.3598 8.29415 52.2279 8.29415C51.096 8.29415 50.4076 9.05528 50.4076 10.2081V14.5765C50.4076 15.7293 51.096 16.5152 52.2279 16.5152C53.3598 16.5152 54.0232 15.7293 54.0232 14.5765Z" fill="currentColor" />
                    <path d="M61.3278 6.55127C61.5489 6.55127 61.6719 6.67399 61.6719 6.89463V15.1653C61.6719 16.3181 62.3105 17.1041 63.394 17.1041C64.4775 17.1041 65.1161 16.3181 65.1161 15.1653V6.89463C65.1161 6.67399 65.2391 6.55127 65.4602 6.55127H66.8866C67.1077 6.55127 67.2307 6.67399 67.2307 6.89463V15.0909C67.2307 17.4226 65.6814 19.0911 63.394 19.0911C61.1066 19.0911 59.5573 17.4226 59.5573 15.0909V6.89463C59.5573 6.67399 59.6803 6.55127 59.9014 6.55127H61.3278Z" fill="currentColor" />
                    <path d="M70.8712 3.75343C70.8712 3.0171 71.3881 2.47791 72.1261 2.47791C72.8642 2.47791 73.4046 3.0184 73.4046 3.75343C73.4046 4.41665 72.8393 5.00546 72.1261 5.00546C71.413 5.00546 70.8712 4.46496 70.8712 3.75343ZM71.0688 18.4775V6.86979C71.0688 6.67396 71.1918 6.55123 71.413 6.55123H72.8393C73.0618 6.55123 73.1835 6.67396 73.1835 6.86979V18.4775C73.1835 18.6981 73.0605 18.8208 72.8393 18.8208H71.413C71.1918 18.8208 71.0688 18.6981 71.0688 18.4775Z" fill="currentColor" />
                    <path d="M76.5282 15.1901C76.5282 14.993 76.6028 14.8219 76.8723 14.8219H78.2987C78.4714 14.8219 78.6193 14.8951 78.6428 15.1405C78.741 16.3925 79.3311 17.0792 80.6109 17.0792C81.7428 17.0792 82.333 16.4408 82.333 15.5583C82.333 13.0803 76.6747 13.7422 76.6747 9.71722C76.6747 7.75367 78.1757 6.28101 80.3885 6.28101C83.02 6.28101 84.1768 7.97431 84.2501 9.88825C84.2501 10.0606 84.1755 10.2316 83.9295 10.2316H82.4783C82.282 10.2316 82.159 10.1089 82.1341 9.91306C82.0608 8.95609 81.4955 8.26806 80.4369 8.26806C79.3782 8.26806 78.7645 8.88167 78.7645 9.76553C78.7645 12.1221 84.4464 11.4588 84.4464 15.5818C84.4464 17.5701 82.9219 19.0911 80.6345 19.0911C78.0762 19.0911 76.6001 17.3482 76.5269 15.1888L76.5282 15.1901Z" fill="currentColor" />
                    <path d="M87.1538 15.0674V10.2577C87.1787 7.92599 88.7032 6.3071 91.0651 6.3071C93.3774 6.2823 95.0013 7.92729 95.0013 10.1846C95.0013 12.4419 93.4271 13.8898 91.09 13.8898H89.2698V15.1653C89.2698 16.2946 89.9332 17.0792 91.0651 17.0792C92.074 17.0792 92.7388 16.3429 92.8121 15.2632C92.837 15.0426 92.96 14.9446 93.1562 14.9446H94.6075C94.805 14.9446 94.9281 15.0674 94.9281 15.2632C94.805 17.5453 93.2544 19.0663 91.0416 19.0663C88.8288 19.0663 87.1551 17.4709 87.1551 15.0661L87.1538 15.0674ZM91.09 11.9745C92.2219 11.9745 92.8854 11.263 92.8854 10.2316C92.8854 9.05399 92.1971 8.29285 91.0651 8.29285C89.9332 8.29285 89.2698 9.05399 89.2698 10.2316V11.9745H91.09Z" fill="currentColor" />
                    <path d="M11.8361 13.9015C11.505 13.4981 11.0156 13.267 10.4922 13.267H7.50729L8.82241 6.73144C9.1561 5.07208 8.73212 3.36963 7.65647 2.06016C6.58344 0.750694 4.99352 0 3.2976 0C3.07122 0 2.87493 0.160583 2.83044 0.381222L0.167488 13.613C0.163562 13.6248 0.159636 13.6378 0.157019 13.6483L0.108602 13.8898C-0.218542 15.5178 0.198893 17.1876 1.25361 18.4723C2.30832 19.7556 3.86814 20.4933 5.53265 20.4933H7.26913L6.76664 22.9921C6.72607 23.1906 6.81767 23.3929 6.99564 23.4974C7.06761 23.5391 7.15005 23.5613 7.2338 23.5613C7.34895 23.5613 7.45887 23.5196 7.54655 23.4438L11.0823 20.3784C11.0928 20.3693 11.1019 20.3601 11.1111 20.3484C11.1635 20.2948 11.2014 20.2309 11.2145 20.18C11.2171 20.1734 11.221 20.1656 11.2236 20.1578C11.2302 20.1434 11.2341 20.1264 11.238 20.1121L12.1959 15.3403C12.2993 14.8285 12.1671 14.305 11.8361 13.9015ZM9.49763 20.4933L7.97576 21.8132L8.2414 20.4933H9.49763ZM7.8907 6.54344L6.53764 13.2657H1.20781L3.68494 0.96611C4.9451 1.06925 6.11627 1.68025 6.92105 2.66202C7.8135 3.74955 8.16681 5.16477 7.88939 6.54344H7.8907ZM5.43581 19.5376C5.42273 19.535 5.40833 19.5324 5.39394 19.5311C4.08929 19.4567 2.86708 18.8378 2.04006 17.8313C1.20781 16.8181 0.84533 15.51 1.02853 14.2162H6.58867C6.60437 14.2188 6.62008 14.2214 6.63578 14.2214H7.12126C7.13696 14.2214 7.15267 14.2201 7.16706 14.2162H10.4935C10.729 14.2162 10.9515 14.3206 11.1006 14.5034C11.2498 14.6862 11.31 14.9238 11.2629 15.1536L10.3809 19.5441H5.99588C5.9828 19.5415 5.9684 19.5402 5.95401 19.5402H5.43451V19.5389L5.43581 19.5376Z" fill="currentColor" />
                </g>
                <defs>
                    <clipPath id="clip0_101_1396">
                        <rect width="95" height="24" fill="white"/>
                    </clipPath>
                </defs>
            </svg>
        </div>
        <div class="drawer-navigation">
            <?php foreach ($linguise_tabs as $tab_key => $tab_d) : ?>
                <div class="nav-tabs <?php echo esc_attr($tab_key === 'main_settings' ? 'active' : ''); ?>" data-toggle="tab" data-target="<?php echo esc_attr($tab_d['content']); ?>" data-save-hide="<?php echo isset($tab_d['no-save']) && $tab_d['no-save'] ? '1' : '0'; ?>">
                    <i class="material-icons tab-icon"><?php echo esc_html($tab_d['icon']); ?></i>
                    <span class="tab-name"><?php echo esc_html($tab_d['name']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <hr class="w-full" />
        <?php require(LINGUISE_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'tpl' . DIRECTORY_SEPARATOR . 'footer.php'); ?>
    </div>

    <form action="" method="post" class="content-area linguise-config-form">
        <?php wp_nonce_field('linguise-settings', 'linguise_nonce'); ?>
        <?php if (!empty($api_web_errors)) : ?>
            <?php foreach ($api_web_errors as $web_err) : ?>
            <div class="linguise-regist-warn">
                <?php echo AdminHelper::renderAdmonition(esc_html($web_err['message']), $web_err['type'], ['class' => 'small items-center']); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-sanitized */ ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <?php if (!$has_api_key) : ?>
            <div class="linguise-regist-warn" data-id="linguise-register-warn">
                <?php echo AdminHelper::renderAdmonition(esc_html($translation_strings_root['settings-hidden']['warning']), 'warning', ['class' => 'small items-center']); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-sanitized */ ?>
            </div>
        <?php endif; ?>
        <?php foreach ($linguise_tabs as $tab_key => $tab_d) : ?>
            <div class="tab-content <?php echo esc_attr($tab_key === 'main_settings' ? 'active' : ''); ?>" data-id="<?php echo esc_attr($tab_d['content']); ?>">
                <?php include_once(LINGUISE_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'tpl' . DIRECTORY_SEPARATOR . $tab_d['content'] . '.php'); ?>
            </div>
        <?php endforeach; ?>
        <div class="flex flex-row w-full justify-end box-border px-4 save-settings-btn">
            <input type="submit" class="linguise-btn rounder save-settings-input" value="<?php echo esc_attr($translation_strings_root['save-btn']); ?>" <?php echo esc_attr($has_api_key ? '' : 'disabled'); ?> />
        </div>
    </form>

    <div class="preview-area">
        <!-- Preview Toggle Button -->
        <button type="button" class="preview-toggle" aria-label="Toggle Preview">
            <svg class="preview-toggle-chevron" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M9.33325 12L5.33325 8L9.33325 4L10.2666 4.93333L7.19992 8L10.2666 11.0667L9.33325 12Z" fill="currentColor"/>
            </svg>
            <span class="align-middle"><?php echo esc_html($translation_strings_root['preview_btn']); ?></span>
        </button>
        <div class="flex flex-col w-full gap-4">
            <h2 class="m-0 text-2xl font-bold text-black">
                <?php echo esc_html($translation_strings_root['preview']); ?>
            </h2>
            <div>
                <div class="w-full" id="dashboard-live-preview"></div>
            </div>
        </div>
        <div class="phone-only">
            <hr class="w-full my-4" />
            <?php require(LINGUISE_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'tpl' . DIRECTORY_SEPARATOR . 'footer.php'); ?>
        </div>
    </div>
</div>

<!-- templating -->
<template data-template="linguise-register-frame">
    <div class="linguise-register-area" style="display: none;">
        <div class="backdrop"></div>
        <div class="content-area">
            <div class="content-wrapper">
                <!-- flex box
                    - TOP [Linguise logo + close button]
                    - IFRAME [Injected Linguise dashboard] [Full height]
                    - BOTTOM [Hidden until login is successful]
                        - [Translate button]
                -->
                <div class="frame-header">
                    <div class="linguise-logo">
                        <svg width="95" height="24" viewBox="0 0 95 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <g>
                                <path d="M28.3935 16.8338C28.6146 16.8338 28.7376 16.9565 28.7376 17.1772V18.5754C28.7376 18.7712 28.6146 18.894 28.3935 18.894H25.9085C23.3999 18.894 21.8506 17.0296 21.8506 14.5752V1.96223C21.8506 1.7664 21.9736 1.64368 22.1699 1.64368H23.7441C23.9652 1.64368 24.0883 1.7664 24.0883 1.96223V14.5021C24.0883 15.9003 24.8996 16.8338 26.0315 16.8338H28.3935Z" fill="currentColor" />
                                <path d="M31.3208 3.75343C31.3208 3.0171 31.8377 2.47791 32.5757 2.47791C33.3138 2.47791 33.8555 3.0184 33.8555 3.75343C33.8555 4.41665 33.2902 5.00546 32.5757 5.00546C31.8612 5.00546 31.3208 4.46496 31.3208 3.75343ZM31.5171 18.4775V6.86979C31.5171 6.67396 31.6401 6.55123 31.8612 6.55123H33.2876C33.5087 6.55123 33.6317 6.67396 33.6317 6.86979V18.4775C33.6317 18.6981 33.5087 18.8208 33.2876 18.8208H31.8612C31.6401 18.8208 31.5171 18.6981 31.5171 18.4775Z" fill="currentColor" />
                                <path d="M37.2734 18.4788V10.2825C37.2734 7.95079 38.8228 6.28229 41.1102 6.28229C43.3976 6.28229 44.9469 7.95079 44.9469 10.2825V18.4788C44.9469 18.6994 44.8239 18.8221 44.6028 18.8221H43.1764C42.9553 18.8221 42.8323 18.6994 42.8323 18.4788V10.2081C42.8323 9.05528 42.1924 8.26934 41.1102 8.26934C40.028 8.26934 39.3881 9.05528 39.3881 10.2081V18.4788C39.3881 18.6994 39.2651 18.8221 39.0439 18.8221H37.6176C37.3964 18.8221 37.2734 18.6994 37.2734 18.4788Z" fill="currentColor" />
                                <path d="M56.1379 10.2812V19.9997C56.1379 22.4294 54.6134 24 52.2266 24C49.8397 24 48.4147 22.4542 48.3152 20.0494C48.2904 19.8287 48.4134 19.706 48.6345 19.706H50.0857C50.282 19.706 50.405 19.8039 50.4299 20.0494C50.5032 21.2766 51.1679 21.9881 52.2253 21.9881C53.3572 21.9881 54.0206 21.2766 54.0206 20.0742V17.6445C53.5037 18.185 52.8154 18.5036 52.0525 18.5036C49.8633 18.5036 48.2891 16.8351 48.2891 14.5034V10.2825C48.2891 7.95079 49.8633 6.28229 52.2253 6.28229C54.5872 6.28229 56.1366 7.95079 56.1366 10.2825L56.1379 10.2812ZM54.0232 14.5765V10.2081C54.0232 9.05528 53.3598 8.29415 52.2279 8.29415C51.096 8.29415 50.4076 9.05528 50.4076 10.2081V14.5765C50.4076 15.7293 51.096 16.5152 52.2279 16.5152C53.3598 16.5152 54.0232 15.7293 54.0232 14.5765Z" fill="currentColor" />
                                <path d="M61.3278 6.55127C61.5489 6.55127 61.6719 6.67399 61.6719 6.89463V15.1653C61.6719 16.3181 62.3105 17.1041 63.394 17.1041C64.4775 17.1041 65.1161 16.3181 65.1161 15.1653V6.89463C65.1161 6.67399 65.2391 6.55127 65.4602 6.55127H66.8866C67.1077 6.55127 67.2307 6.67399 67.2307 6.89463V15.0909C67.2307 17.4226 65.6814 19.0911 63.394 19.0911C61.1066 19.0911 59.5573 17.4226 59.5573 15.0909V6.89463C59.5573 6.67399 59.6803 6.55127 59.9014 6.55127H61.3278Z" fill="currentColor" />
                                <path d="M70.8712 3.75343C70.8712 3.0171 71.3881 2.47791 72.1261 2.47791C72.8642 2.47791 73.4046 3.0184 73.4046 3.75343C73.4046 4.41665 72.8393 5.00546 72.1261 5.00546C71.413 5.00546 70.8712 4.46496 70.8712 3.75343ZM71.0688 18.4775V6.86979C71.0688 6.67396 71.1918 6.55123 71.413 6.55123H72.8393C73.0618 6.55123 73.1835 6.67396 73.1835 6.86979V18.4775C73.1835 18.6981 73.0605 18.8208 72.8393 18.8208H71.413C71.1918 18.8208 71.0688 18.6981 71.0688 18.4775Z" fill="currentColor" />
                                <path d="M76.5282 15.1901C76.5282 14.993 76.6028 14.8219 76.8723 14.8219H78.2987C78.4714 14.8219 78.6193 14.8951 78.6428 15.1405C78.741 16.3925 79.3311 17.0792 80.6109 17.0792C81.7428 17.0792 82.333 16.4408 82.333 15.5583C82.333 13.0803 76.6747 13.7422 76.6747 9.71722C76.6747 7.75367 78.1757 6.28101 80.3885 6.28101C83.02 6.28101 84.1768 7.97431 84.2501 9.88825C84.2501 10.0606 84.1755 10.2316 83.9295 10.2316H82.4783C82.282 10.2316 82.159 10.1089 82.1341 9.91306C82.0608 8.95609 81.4955 8.26806 80.4369 8.26806C79.3782 8.26806 78.7645 8.88167 78.7645 9.76553C78.7645 12.1221 84.4464 11.4588 84.4464 15.5818C84.4464 17.5701 82.9219 19.0911 80.6345 19.0911C78.0762 19.0911 76.6001 17.3482 76.5269 15.1888L76.5282 15.1901Z" fill="currentColor" />
                                <path d="M87.1538 15.0674V10.2577C87.1787 7.92599 88.7032 6.3071 91.0651 6.3071C93.3774 6.2823 95.0013 7.92729 95.0013 10.1846C95.0013 12.4419 93.4271 13.8898 91.09 13.8898H89.2698V15.1653C89.2698 16.2946 89.9332 17.0792 91.0651 17.0792C92.074 17.0792 92.7388 16.3429 92.8121 15.2632C92.837 15.0426 92.96 14.9446 93.1562 14.9446H94.6075C94.805 14.9446 94.9281 15.0674 94.9281 15.2632C94.805 17.5453 93.2544 19.0663 91.0416 19.0663C88.8288 19.0663 87.1551 17.4709 87.1551 15.0661L87.1538 15.0674ZM91.09 11.9745C92.2219 11.9745 92.8854 11.263 92.8854 10.2316C92.8854 9.05399 92.1971 8.29285 91.0651 8.29285C89.9332 8.29285 89.2698 9.05399 89.2698 10.2316V11.9745H91.09Z" fill="currentColor" />
                                <path d="M11.8361 13.9015C11.505 13.4981 11.0156 13.267 10.4922 13.267H7.50729L8.82241 6.73144C9.1561 5.07208 8.73212 3.36963 7.65647 2.06016C6.58344 0.750694 4.99352 0 3.2976 0C3.07122 0 2.87493 0.160583 2.83044 0.381222L0.167488 13.613C0.163562 13.6248 0.159636 13.6378 0.157019 13.6483L0.108602 13.8898C-0.218542 15.5178 0.198893 17.1876 1.25361 18.4723C2.30832 19.7556 3.86814 20.4933 5.53265 20.4933H7.26913L6.76664 22.9921C6.72607 23.1906 6.81767 23.3929 6.99564 23.4974C7.06761 23.5391 7.15005 23.5613 7.2338 23.5613C7.34895 23.5613 7.45887 23.5196 7.54655 23.4438L11.0823 20.3784C11.0928 20.3693 11.1019 20.3601 11.1111 20.3484C11.1635 20.2948 11.2014 20.2309 11.2145 20.18C11.2171 20.1734 11.221 20.1656 11.2236 20.1578C11.2302 20.1434 11.2341 20.1264 11.238 20.1121L12.1959 15.3403C12.2993 14.8285 12.1671 14.305 11.8361 13.9015ZM9.49763 20.4933L7.97576 21.8132L8.2414 20.4933H9.49763ZM7.8907 6.54344L6.53764 13.2657H1.20781L3.68494 0.96611C4.9451 1.06925 6.11627 1.68025 6.92105 2.66202C7.8135 3.74955 8.16681 5.16477 7.88939 6.54344H7.8907ZM5.43581 19.5376C5.42273 19.535 5.40833 19.5324 5.39394 19.5311C4.08929 19.4567 2.86708 18.8378 2.04006 17.8313C1.20781 16.8181 0.84533 15.51 1.02853 14.2162H6.58867C6.60437 14.2188 6.62008 14.2214 6.63578 14.2214H7.12126C7.13696 14.2214 7.15267 14.2201 7.16706 14.2162H10.4935C10.729 14.2162 10.9515 14.3206 11.1006 14.5034C11.2498 14.6862 11.31 14.9238 11.2629 15.1536L10.3809 19.5441H5.99588C5.9828 19.5415 5.9684 19.5402 5.95401 19.5402H5.43451V19.5389L5.43581 19.5376Z" fill="currentColor" />
                            </g>
                            <defs>
                                <clipPath id="clip0_101_1396">
                                    <rect width="95" height="24" fill="white"/>
                                </clipPath>
                            </defs>
                        </svg>
                    </div>
                    <button type="button" class="close-button" data-linguise-action="close-modal">
                        <span class="material-icons">close</span>
                    </button>
                </div>
                <div class="frame-content">
                    <!-- MANUALLY INJECT HERE LATER -->
                </div>
                <div class="frame-footer">
                    <button type="button" class="linguise-btn rounder translate-btn" data-linguise-action="translate-save">
                        <?php echo esc_html($translation_strings_root['modal-save']['save']); ?>
                        <svg width="22" height="20" viewBox="0 0 22 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M11.87 13.07L9.33 10.56L9.36 10.53C11.1 8.59 12.34 6.36 13.07 4H16V2H9V0H7V2H0V3.99H11.17C10.5 5.92 9.44 7.75 8 9.35C7.07 8.32 6.3 7.19 5.69 6H3.69C4.42 7.63 5.42 9.17 6.67 10.56L1.58 15.58L3 17L8 12L11.11 15.11L11.87 13.07ZM17.5 8H15.5L11 20H13L14.12 17H18.87L20 20H22L17.5 8ZM14.88 15L16.5 10.67L18.12 15H14.88Z" fill="currentColor" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<template data-template="linguise-modal-saving" data-linguise="modal-iframe">
    <div class="linguise-modal-warn-area">
        <div class="backdrop"></div>
        <div class="content-area">
            <div class="content-wrapper justify-center items-center">
                <div class="flex flex-row gap-1 items-center justify-center w-full">
                    <div class="loading-blob"></div>
                    <div class="loading-blob is-middle"></div>
                    <div class="loading-blob is-last"></div>
                </div>
                <h2 class="text-2xl font-bold text-center text-black m-0">
                    <?php echo esc_html($translation_strings_root['modal-saving']['title']); ?>
                </h2>
                <h2 class="text-2xl font-bold text-center text-black m-0">
                    <?php echo esc_html($translation_strings_root['modal-saving']['message']); ?>
                </h2>
            </div>
        </div>
    </div>
</template>

<template data-template="linguise-modal-saved" data-linguise="modal-iframe">
    <div class="linguise-modal-warn-area">
        <div class="backdrop"></div>
        <div class="content-area">
            <div class="content-wrapper justify-center items-center">
                <div class="flex flex-col items-center justify-center w-full">
                    <svg width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M60.2336 38.1499C58.9757 36.1931 57.7177 34.3062 56.4598 32.3494C56.1803 31.9301 56.1803 31.6506 56.4598 31.2312C57.7177 29.3444 58.9058 27.4575 60.1637 25.5706C61.6313 23.3342 60.8626 21.1678 58.3467 20.2593C56.2501 19.4905 54.1536 18.6519 52.057 17.8832C51.6377 17.7434 51.4281 17.4639 51.4281 16.9747C51.3582 14.6685 51.2184 12.3623 51.0786 10.126C50.9389 7.67998 49.052 6.28228 46.6759 6.91124C44.4396 7.47032 42.2033 8.09929 40.0368 8.72826C39.5476 8.86802 39.2681 8.72826 38.9186 8.37883C37.5209 6.56182 36.0534 4.81469 34.6557 3.06756C33.1182 1.11078 30.7421 1.11078 29.1347 3.06756C27.737 4.81469 26.2694 6.56182 24.9416 8.30894C24.5922 8.79813 24.2428 8.86802 23.6837 8.72826C21.5173 8.09929 19.3508 7.54021 17.8134 7.1209C14.8083 6.42205 12.9913 7.61009 12.8515 10.126C12.7118 12.4322 12.572 14.7384 12.5021 17.1145C12.5021 17.6037 12.2924 17.8133 11.8731 18.023C9.7067 18.8616 7.54027 19.7002 5.37383 20.5388C3.13751 21.4473 2.43866 23.6138 3.76647 25.6404C5.0244 27.5972 6.28233 29.4841 7.54026 31.4409C7.81979 31.8602 7.81979 32.1397 7.54026 32.629C6.21244 34.5857 4.95451 36.5425 3.69658 38.5692C2.50853 40.4561 3.27726 42.6924 5.37382 43.531C7.54026 44.3697 9.77658 45.2083 11.943 46.0469C12.4322 46.1867 12.572 46.4662 12.572 46.9554C12.6419 49.1917 12.9214 51.3581 12.9214 53.5945C12.9214 55.8308 14.8782 57.8575 17.6037 57.0188C19.7701 56.32 21.9366 55.8308 24.103 55.2018C24.5223 55.062 24.8018 55.1319 25.0814 55.5512C26.549 57.3683 27.9467 59.1154 29.4143 60.9324C31.0216 62.8892 33.3278 62.8892 34.8653 60.9324C36.3329 59.1154 37.7306 57.3683 39.1982 55.5512C39.4777 55.2018 39.6874 55.062 40.1765 55.2018C42.4129 55.8308 44.6492 56.3899 46.8855 57.0188C49.1917 57.6478 51.1485 56.2501 51.2184 53.874C51.3582 51.5678 51.4979 49.2616 51.5678 46.8855C51.5678 46.3264 51.8474 46.1168 52.2667 45.977C54.3632 45.2083 56.5297 44.3697 58.6262 43.531C60.8626 42.4129 61.5614 40.1765 60.2336 38.1499ZM43.5311 26.6887L29.5541 40.6657C29.2046 41.0152 28.7154 41.2947 28.2262 41.3646C28.0865 41.3646 27.8768 41.4345 27.7371 41.4345C27.1081 41.4345 26.4092 41.1549 25.92 40.6657L20.1895 34.9351C19.2111 33.9568 19.2111 32.3494 20.1895 31.371C21.1679 30.3926 22.7752 30.3926 23.7536 31.371L27.6672 35.2846L39.8272 23.1246C40.8056 22.1462 42.4129 22.1462 43.3913 23.1246C44.5095 24.103 44.5095 25.7103 43.5311 26.6887Z" fill="#01AB6A"/>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-center text-black m-0 mt-2">
                    <?php echo esc_html($translation_strings_root['modal-saved']['title']); ?>
                </h2>
                <div class="flex flex-row justify-center items-center gap-2 mt-4 w-full">
                    <button type="button" class="linguise-btn rounder outlined w-full" data-linguise-action="close-modal-force" data-linguise-action-target="saved">
                        <?php echo esc_html($translation_strings_root['modal-saved']['button']); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<template data-template="linguise-modal-abort" data-linguise="modal-iframe">
    <div class="linguise-modal-warn-area">
        <div class="backdrop"></div>
        <div class="content-area">
            <div class="content-wrapper justify-center items-center">
                <div class="flex flex-col items-center justify-center w-full">
                    <div class="fail-mark-modal">
                        <span class="material-icons">close</span>
                    </div>
                </div>
                <h2 class="text-xl font-bold text-black m-0 mt-2 text-center">
                    <?php echo esc_html($translation_strings_root['modal-abort']['title']); ?>
                </h2>
                <div class="mt-2 text-center text-neutral text-base">
                    <?php echo esc_html($translation_strings_root['modal-abort']['message']); ?>
                </div>
                <div class="flex flex-row justify-center items-center gap-2 mt-4 w-full">
                    <button type="button" class="linguise-btn rounder danger w-full" data-linguise-action="close-modal-force">
                        <?php echo esc_html($translation_strings_root['modal-abort']['yes']); ?>
                    </button>
                    <button type="button" class="linguise-btn rounder outlined w-full" data-linguise-action="popup-cancel-modal">
                        <?php echo esc_html($translation_strings_root['modal-abort']['no']); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<template data-template="linguise-modal-error" data-linguise="modal-iframe">
    <div class="linguise-modal-warn-area">
        <div class="backdrop"></div>
        <div class="content-area">
            <div class="content-wrapper justify-center items-center">
                <div class="flex flex-col items-center justify-center w-full">
                    <div class="fail-mark-modal">
                        <span class="material-icons">close</span>
                    </div>
                </div>
                <h2 class="text-xl font-bold text-black m-0 mt-2 text-center">
                    <?php echo esc_html($translation_strings_root['modal-error']['title']); ?>
                </h2>
                <div class="mt-2 text-center text-neutral text-base">
                    <?php echo esc_html($translation_strings_root['modal-error']['message']); ?>
                </div>
                <div class="flex flex-col justify-center items-center gap-2 mt-4 w-full">
                    <button type="button" class="linguise-btn rounder w-full" data-linguise-action="submit-try-again">
                        <?php echo esc_html($translation_strings_root['modal-error']['yes']); ?>
                    </button>
                    <button type="button" class="linguise-btn rounder outlined w-full" data-linguise-action="popup-cancel-modal">
                        <?php echo esc_html($translation_strings_root['modal-error']['no']); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<!-- some data -->
<span id="linguise-site-url" data-url="<?php echo esc_attr(linguiseGetSite()); ?>" style="display: none;"></span>

<!-- start of linguise config script -->
<script id="config-script" type="text/javascript">
    var linguise_configs = <?php echo wp_json_encode($config_array); ?>;
    window.linguise_configs = linguise_configs;
</script>
<script type="text/javascript">
    var linguise_site_url = "<?php echo esc_attr(linguiseGetSite()); ?>";
    window.linguise_site_url = linguise_site_url;
</script>
<!-- end of linguise config script -->

<!--Start of Tawk.to Script-->
<script type="text/javascript">
    var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
    window.Tawk_API = Tawk_API;
    window.Tawk_LoadStart = Tawk_LoadStart;
    (function(){
        var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
        s1.async=true;
        s1.src='https://embed.tawk.to/6107b589649e0a0a5ccf1114/1fur1fhvd';
        s1.charset='UTF-8';
        s1.setAttribute('crossorigin','*');
        s0.parentNode.insertBefore(s1,s0);
    })();
</script>
<!--End of Tawk.to Script-->
