<?php

use Linguise\Vendor\Linguise\Script\Core\Database;
use Linguise\Vendor\Linguise\Script\Core\Templates\Helper;

defined('LINGUISE_MANAGEMENT') or die('No access to this page.');
defined('LINGUISE_AUTHORIZED') or die('Access denied.');

$options = Database::getInstance()->retrieveOtherParam('linguise_options');
$has_api_key = !empty($options['token']);

$translation_strings = [
    'help_header' => __('How-to load the language switcher flags:', 'linguise'),
    'shortcode' => [
        'title' => __('Shortcode', 'linguise'),
        'description' => __('You can copy the following shortcode to use Linguise anywhere in your pages or posts.', 'linguise'),
    ],
    'html_snippet' => [
        'title' => __('HTML Snippet', 'linguise'),
        'description' => __('You can copy the following HTML snippet to use Linguise anywhere in your pages or posts.', 'linguise'),
        'variant' => __('Or you can use the HTML snippet:', 'linguise'),
        'variant_description' => __('The following version will not pin the switcher in-place but following the config', 'linguise'),
    ],
    'auto' => [
        'title' => __('Automatic', 'linguise'),
        'description' => __('You can set the switcher to be automatically displayed in your pages. Go to %1$s and make sure the %2$s is NOT set to "%3$s"', 'linguise'),
        'position' => __('Position', 'linguise'),
        'inplace' => __('In place', 'linguise'),
    ],

    'latest_error' => [
        'title' => __('Latest 20 errors returned by Linguise', 'linguise'),
        'no_error' => __('No errors', 'linguise'),
        'url_info' => __('Get more information about this error on Linguise', 'linguise'),
    ],
];

$latest_linguise_errors = Helper::getLastErrors();

function make_error_link($error_code) {
    return 'https://www.linguise.com/documentation/debug-support/wordpress-plugin-error-codes/#' . $error_code;
}

function make_error_message($error) {
    $base = '<span class="timestamp">' . esc_html($error['time']) . '</span>';
    if (empty($error['code'])) {
        return $base . '<span class="line">' . esc_html($error['message']) . '</span>';
    } else {
        return $base . '<a href="' . esc_url(make_error_link($error['code'])) . '" class="linguise-link line" target="_blank" rel="noreferrer noopener">' . esc_html($error['message']) . '</a>';
    }
}

$html_snippet = '<div class="linguise_switcher_root linguise_menu_root"></div>';
$html_snippet_variant = '<div class="linguise_switcher_root"></div>';

?>

<div class="tab-linguise-options">
    <!-- [BLOCK] Switcher loading -->
    <div class="linguise-options full-width<?php echo $has_api_key ? '' : ' is-disabled'; ?>">
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
            <h3 class="m-0 text-base text-neutral-deep font-semibold">
                <?php echo esc_html($translation_strings['auto']['title']); ?>
            </h3>
            <div class="mt-2 flex flex-col">
                <div class="text-neutral-deep" style="line-height: 1.5rem;">
                    <?php
                        echo sprintf(
                            esc_html($translation_strings['auto']['description']),
                            '<a href="' . esc_url('#main-settings') . '" class="linguise-link">' . esc_html($linguise_tabs['main_settings']['name']) . '</a>',
                            '<strong>' . esc_html($translation_strings['auto']['position']) . '</strong>',
                            '<strong>' . esc_html($translation_strings['auto']['inplace']) . '</strong>'
                        );
                    ?>
                </div>
            </div>
        </div>
        <div class="flex flex-col mt-4 linguise-inner-options">
            <h3 class="m-0 text-base text-neutral-deep font-semibold">
                <?php echo esc_html($translation_strings['shortcode']['title']); ?>
            </h3>
            <div class="mt-2 flex flex-col">
                <div>
                    <?php echo esc_html($translation_strings['shortcode']['description']); ?>
                </div>
                <div class="block-highlight inverted half-width with-fill-small flex flex-row justify-between mt-2">
                    <div class="domain-part font-bold" style="user-select: all;">[linguise]</div>
                    <div class="copy-part" data-clipboard-text="[linguise]">
                        <span class="material-icons copy-button align-bottom">content_copy</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex flex-col mt-4 linguise-inner-options">
            <h3 class="m-0 text-base text-neutral-deep font-semibold">
                <?php echo esc_html($translation_strings['html_snippet']['title']); ?>
            </h3>
            <div class="mt-2 flex flex-col">
                <div class="text-neutral-deep">
                    <?php echo esc_html($translation_strings['html_snippet']['description']); ?>
                </div>
                <div class="block-highlight inverted flex flex-row justify-between mt-2">
                    <div class="domain-part font-bold break-all-words" style="user-select: all;"><?php echo esc_html($html_snippet); ?></div>
                    <div class="copy-part" data-clipboard-text="<?php echo esc_attr($html_snippet); ?>">
                        <span class="material-icons copy-button align-bottom">content_copy</span>
                    </div>
                </div>
                <div class="mt-4 text-neutral-deep">
                    <?php echo esc_html($translation_strings['html_snippet']['variant']); ?>
                </div>
                <div class="block-highlight inverted flex flex-row justify-between mt-2">
                    <div class="domain-part font-bold break-all-words" style="user-select: all;"><?php echo esc_html($html_snippet_variant); ?></div>
                    <div class="copy-part" data-clipboard-text="<?php echo esc_attr($html_snippet_variant); ?>">
                        <span class="material-icons copy-button align-bottom">content_copy</span>
                    </div>
                </div>
                <div class="mt-4 text-muted">
                    <?php echo esc_html($translation_strings['html_snippet']['variant_description']); ?>
                </div>
            </div>
        </div>
    </div>
    <!-- [BLOCK] Latest errors -->
    <div class="linguise-options full-width<?php echo $has_api_key ? '' : ' is-disabled'; ?>">
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
            <pre class="m-0 box-border overflow-auto"><code class="latest-errors-list"><?php foreach ($latest_linguise_errors as $error) { ?><span class="latest-error-item"><?php echo make_error_message($error) ?></span><?php } ?></code></pre>
        <?php else : ?>
            <span class="text-neutral mb-2"><?php echo esc_html($translation_strings['latest_error']['no_error']); ?></span>
        <?php endif; ?>
        </div>
    </div>
</div>