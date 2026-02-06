<?php

defined('ABSPATH') || die('');

$options = linguiseGetOptions();
$has_api_key = !empty($options['token']);

$translation_strings = [
    'help_header' => __('How-to load the language switcher flags:', 'linguise'),
    'wp_menu' => [
        'title' => __('WordPress menu', 'linguise'),
        /* translators: 1: Link to WordPress menu system, 2: Menu item name, 3: Display mode setting */
        'description' => __('If your theme support navigation menus, you can go to your %1$s and insert a menu element called "%2$s". It is recommended to set the %3$s to in-place to avoid duplicates switchers.', 'linguise'),
        'link_name' => __('menu system', 'linguise'),
        'menu_name' => __('Linguise Languages', 'linguise'),
    ],
    'shortcode' => [
        'title' => __('Shortcode', 'linguise'),
        'description' => __('You can copy the following shortcode to use Linguise anywhere in your pages or posts.', 'linguise'),
    ],
    'php_snippet' => [
        'title' => __('PHP snippet', 'linguise'),
        'description' => __('You can copy the following PHP snippet to use Linguise anywhere in your theme', 'linguise'),
    ],
    'auto' => [
        'title' => __('Automatic', 'linguise'),
        /* translators: 1: Settings page name, 2: Setting option name */
        'description' => __('You can set the switcher to be automatically displayed in your pages. Go to %1$s and enable "%2$s."', 'linguise'),
        'add_switcher' => __('Add language switcher automatically', 'linguise'),
    ],

    'latest_error' => [
        'title' => __('Latest 20 errors returned by Linguise', 'linguise'),
        'no_error' => __('No errors', 'linguise'),
        'url_info' => __('Get more information about this error on Linguise', 'linguise'),
    ],
];

$latest_linguise_errors = \Linguise\WordPress\Admin\Helper::getLastErrors();

/**
 * Create a link to the error page
 *
 * @param string|integer $error_code The error code extracted
 *
 * @return string the full link to the error code
 */
function make_error_link($error_code)
{
    return 'https://www.linguise.com/documentation/debug-support/wordpress-plugin-error-codes/#' . $error_code;
}

/**
 * Make and generate an error message HTML
 *
 * @param array $error The error extracted from the debug/errors file
 *
 * @return string the formatted HTML data
 */
function make_error_message($error)
{
    $base = '<span class="timestamp">' . esc_html($error['time']) . '</span>';
    if (empty($error['code'])) {
        return $base . '<span class="line">' . esc_html($error['message']) . '</span>';
    } else {
        return $base . '<a href="' . esc_url(make_error_link($error['code'])) . '" class="linguise-link line" target="_blank" rel="noreferrer noopener">' . esc_html($error['message']) . '</a>';
    }
}

?>

<div class="tab-linguise-options">
    <!-- [BLOCK] Switcher loading -->
    <div class="linguise-options full-width<?php echo esc_attr($has_api_key ? '' : ' is-disabled'); ?>">
        <div class="disabled-warning-inset"></div>
        <div class="disabled-warning">
            <h2 class="disabled-warning-text">
                <?php echo esc_html($translation_strings_root['settings-hidden']['banner']); ?>
            </h2>
        </div>
        <h2 class="m-0 text-2xl font-bold text-black">
            <?php echo esc_html($translation_strings['help_header']); ?>
        </h2>
        <div class="flex flex-col mt-4 linguise-inner-options">
            <h3 class="m-0 text-base text-neutral-deep">
                <?php echo esc_html($translation_strings['wp_menu']['title']); ?>
            </h3>
            <div class="mt-2">
                <?php
                    echo sprintf(
                        esc_html($translation_strings['wp_menu']['description']),
                        '<a href="' . esc_url(admin_url('nav-menus.php')) . '" class="linguise-link" target="_blank" rel="noreferrer noopener">' . esc_html($translation_strings['wp_menu']['link_name']) . '</a>',
                        '<strong>' . esc_html($translation_strings['wp_menu']['menu_name']) . '</strong>',
                        esc_html($translation_strings['wp_menu']['link_name'])
                    );
                    ?>
            </div>
        </div>
        <div class="flex flex-col mt-4 linguise-inner-options">
            <h3 class="m-0 text-base text-neutral-deep">
                <?php echo esc_html($translation_strings['shortcode']['title']); ?>
            </h3>
            <div class="mt-2 flex flex-col">
                <div>
                    <?php echo esc_html($translation_strings['shortcode']['description']); ?>
                </div>
                <div class="block-highlight inverted half-width with-fill-small flex flex-row justify-between mt-2">
                    <div class="domain-part font-bold break-all-words" style="user-select: all;">[linguise]</div>
                    <div class="copy-part" data-clipboard-text="[linguise]">
                        <span class="material-icons copy-button align-bottom">content_copy</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex flex-col mt-4 linguise-inner-options">
            <h3 class="m-0 text-base text-neutral-deep">
                <?php echo esc_html($translation_strings['php_snippet']['title']); ?>
            </h3>
            <div class="mt-2 flex flex-col">
                <div>
                    <?php echo esc_html($translation_strings['php_snippet']['description']); ?>
                </div>
                <div class="block-highlight inverted half-width with-fill-small flex flex-row justify-between mt-2">
                    <div class="domain-part font-bold break-all-words" style="user-select: all;">echo do_shortcode('[linguise]');</div>
                    <div class="copy-part" data-clipboard-text="echo do_shortcode('[linguise]');">
                        <span class="material-icons copy-button align-bottom">content_copy</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex flex-col mt-4 linguise-inner-options">
            <h3 class="m-0 text-base text-neutral-deep">
                <?php echo esc_html($translation_strings['auto']['title']); ?>
            </h3>
            <div class="mt-2">
                <?php
                    echo sprintf(
                        esc_html($translation_strings['auto']['description']),
                        '<a href="' . esc_url('#' . $linguise_tabs['main_settings']['content']) . '" class="linguise-link">' . esc_html($linguise_tabs['main_settings']['name']) . '</a>',
                        '<strong>' . esc_html($translation_strings['auto']['add_switcher']) . '</strong>'
                    );
                    ?>
            </div>
        </div>
    </div>
    <!-- [BLOCK] Latest errors -->
    <div class="linguise-options full-width<?php echo esc_attr($has_api_key ? '' : ' is-disabled'); ?>">
        <div class="disabled-warning-inset"></div>
        <div class="disabled-warning">
            <h2 class="disabled-warning-text">
                <?php echo esc_html($translation_strings_root['settings-hidden']['banner']); ?>
            </h2>
        </div>
        <h2 class="m-0 text-2xl font-bold text-black">
            <?php echo esc_html($translation_strings['latest_error']['title']); ?>
        </h2>
        <div class="flex flex-col mt-4">
        <?php if (count($latest_linguise_errors)) : ?>
            <!-- code one liner to avoid whitespace -->
            <pre class="m-0 box-border overflow-auto"><code class="latest-errors-list"><?php foreach ($latest_linguise_errors as $ling_err) {
                ?><span class="latest-error-item"><?php echo make_error_message($ling_err); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-sanitized */ ?></span><?php
                                                                                       } ?></code></pre>
        <?php else : ?>
            <?php echo esc_html($translation_strings['latest_error']['no_error']); ?>
        <?php endif; ?>
        </div>
    </div>
</div>
