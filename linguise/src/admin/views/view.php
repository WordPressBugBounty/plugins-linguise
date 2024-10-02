<?php
defined('ABSPATH') || die('');

include_once(LINGUISE_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'Helper.php');

$linguiseTabs = array(
    'main_settings' => array(
        'menu_name' => esc_html__('Main settings', 'linguise'),
        'content' => 'main-settings',
        'icon' => 'translate'
    ),
    'advanced' => array(
        'menu_name' => esc_html__('Advanced', 'linguise'),
        'content' => 'advanced',
        'icon' => 'code'
    ),
    'help' => array(
        'menu_name' => esc_html__('Help', 'linguise'),
        'content' => 'help',
        'icon' => 'help'
    )
);
$options = linguiseGetOptions();
$languages_content = file_get_contents(LINGUISE_PLUGIN_PATH . '/assets/languages.json');
$languages_names = json_decode($languages_content, true);
$languages_enabled_param = isset($options['enabled_languages']) ? $options['enabled_languages'] : array();
$sort_languages = array();
foreach ($languages_enabled_param as $language) {
    if ($language === $options['default_language']) {
        continue;
    }

    if (!isset($languages_names[$language])) {
        continue;
    }

    $sort_languages[$language] = $languages_names[$language];
}

$is_debug = !empty($options['debug']);

foreach ($languages_names as $lang_code => $language_value) {
    if (isset($sort_languages[$lang_code])) {
        continue;
    }
    $sort_languages[$lang_code] = $language_value;
}
$languages_names = $sort_languages;

$latestLinguiseErrors = \Linguise\WordPress\Admin\Helper::getLastErrors();
?>
<div class="linguise-main-wrapper" style="visibility: hidden">
    <div class="linguise-left-panel-toggle">
        <i class="dashicons dashicons-leftright linguise-left-panel-toggle-icon"></i>
    </div>
    <div class="linguise-left-panel">
        <div class="linguise-top-tabs-wrapper">
            <div class="mdc-tab-bar" role="tablist">
                <div class="mdc-tab-scroller">
                    <div class="mdc-tab-scroller__scroll-area">
                        <div class="mdc-tab-scroller__scroll-content">
                            <?php
                            $i = 0;
                            foreach ($linguiseTabs as $k => $linguiseTab) :
                                ?>
                                <button class="mdc-tab <?php echo($k === 'main_settings' ? 'mdc-tab--active' : '') ?>"
                                        role="tab" aria-selected="false" data-index="<?php echo esc_attr($i) ?>"
                                        id="<?php echo esc_attr($k) ?>"><span
                                            class="mdc-tab__content"><span class="mdc-tab__text-label"><i
                                                    class="material-icons mi wpsol-icon-menu menu-tab-icon"><?php echo esc_html($linguiseTab['icon']); ?></i><?php echo esc_html($linguiseTab['menu_name']) ?></span></span><span
                                            class="mdc-tab-indicator <?php echo($k === 'main_settings' ? 'mdc-tab-indicator--active' : '') ?>"><span
                                                class="mdc-tab-indicator__content mdc-tab-indicator__content--underline"
                                                style=""></span></span><span
                                            class="mdc-tab__ripple mdc-ripple-upgraded"></span>
                                </button>
                                <?php
                                $i++;
                            endforeach;
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- debug banner -->
        <?php if ($is_debug) : ?>
        <div class="linguise-warn-banner">
            <form action="" method="post" class="linguise-warn-banner">
                <?php wp_nonce_field('linguise-settings', 'debug_banner_nonce'); ?>
                <input type="hidden" name="linguise_debug_disable" value="1">
                <div class="debug-text">
                    <?php echo esc_html__('Debug mode is enabled', 'linguise'); ?>
                </div>
                <div class="debug-button-wrapper">
                    <button type="submit" class="linguise-button small-button blue-button smaller-btn" name="disable_debug">
                        <?php echo esc_html__('Disable now!', 'linguise'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>
        <form action="" method="post">
            <?php wp_nonce_field('linguise-settings', 'main_settings'); ?>
            <?php if (!empty($errors)) : ?>
                <?php foreach ($errors as $err) { ?>
                    <div class="linguise-message <?php echo esc_attr('linguise-' . $err['type']); ?>">
                        <?php echo wp_kses($err['message'], ['a' => ['href' => [], 'target' => []]]); ?>
                    </div>
                <?php } ?>
            <?php endif; ?>
            <div class="linguise-content-wrapper">
                <?php foreach ($linguiseTabs as $k => $linguiseTab) : ?>
                    <div class="linguise-tab-content <?php echo($k === 'main_settings' ? 'linguise-content-active' : '') ?>"
                         id="<?php echo esc_attr($k); ?>">
                        <?php require_once(LINGUISE_PLUGIN_PATH . 'src/admin/views/tpl/' . $linguiseTab['content'] . '.php'); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </form>
    </div>
    <div class="linguise-right-panel">
        <?php require_once(LINGUISE_PLUGIN_PATH . 'src/admin/views/tpl/aside.php'); ?>
    </div>
</div>



