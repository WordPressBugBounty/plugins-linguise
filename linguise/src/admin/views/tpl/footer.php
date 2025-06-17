<?php

defined('ABSPATH') || die('');

$translation_strings = [
    'chat-with-us' => __('Chat with us', 'linguise'),
    'linguise' => [
        'edit' => __('Edit your translations', 'linguise'),
        'docs' => __('Documentation', 'linguise'),
        'dashboard' => __('Linguise dashboard', 'linguise'),
    ],
];

?>

<div class="drawer-footer mt-1">
    <div class="pb-3">
        <!-- chat with us -->
        <button type="button" class="linguise-btn rounder chat-with-us w-full">
            <img class="align-bottom" width="20" height="20" src="<?php echo esc_url(LINGUISE_PLUGIN_URL . '/assets/images/chat.svg') ; ?>" style="margin-right: 0.25rem;" />
            <span><?php echo esc_html($translation_strings['chat-with-us']); ?></span>
        </button>
    </div>
    <div class="linguise-dashboard-info mt-2">
        <h4 class="text-neutral text-base font-medium m-0">
            <?php echo esc_html($translation_strings['linguise']['edit']); ?>
        </h4>
        <a href="https://www.linguise.com/documentation" target="_blank" rel="noreferrer noopener" class="linguise-btn rounder outlined">
            <?php echo esc_html($translation_strings['linguise']['docs']); ?>
        </a>
        <a href="https://dashboard.linguise.com" target="_blank" rel="noreferrer noopener" class="linguise-btn rounder outlined">
            <?php echo esc_html($translation_strings['linguise']['dashboard']); ?>
        </a>
    </div>
</div>
