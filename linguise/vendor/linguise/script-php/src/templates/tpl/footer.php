<?php

defined('LINGUISE_MANAGEMENT') or die('No access to this page.');
defined('LINGUISE_AUTHORIZED') or die('Access denied.');

use Linguise\Vendor\Linguise\Script\Core\Templates\Helper as AdminHelper;

$translation_strings = [
    'chat-with-us' => __('Chat with us', 'linguise'),
    'linguise' => [
        'edit' => __('Edit your translations', 'linguise'),
        'docs' => __('Documentation', 'linguise'),
        'dashboard' => __('Linguise dashboard', 'linguise'),
    ],
    'logout-btn' => __('Logout', 'linguise'),
];

?>

<div class="drawer-footer mt-1">
    <div class="pb-3">
        <!-- chat with us -->
        <button type="button" class="linguise-btn rounder chat-with-us w-full">
            <img class="align-bottom" width="20" height="20" src="<?php echo esc_url(LINGUISE_BASE_URL . '/assets/images/chat.svg') ; ?>" style="margin-right: 0.25rem;" />
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
        <button type="button" class="linguise-btn danger rounder w-full flex flex-row justify-center items-center gap-2" data-href="<?php echo esc_url($global_site_url . '/' . AdminHelper::getManagementBase()); ?>" data-linguise-action="logout">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-log-out-icon lucide-log-out">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" x2="9" y1="12" y2="12"/>
            </svg>
            <span><?php echo esc_html($translation_strings['logout-btn']); ?></span>
        </button>
    </div>
</div>
