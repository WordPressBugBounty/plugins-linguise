<?php

use Linguise\Vendor\Linguise\Script\Core\Session;

defined('LINGUISE_MANAGEMENT') or die('No access to this page.');

$translation_strings = [
    'login' => [
        'button' => __('Login', 'linguise'),
        'label' => __('Password', 'linguise'),
        'placeholder' => __('Enter your password', 'linguise'),
    ],
];

?>

<form id="login-page" action="" method="post" class="linguise-form-container">
    <input type="hidden" name="linguise_action" value="login" />
    <input type="hidden" name="_token" value="<?php echo esc_attr(Session::getInstance()->getCsrfToken('linguise_oobe_login')); ?>" />
    <div class="linguise-form-area flex flex-col gap-2">
        <div>
            <label for="login-box" class="text-base text-neutral">
                <?php echo esc_html($translation_strings['login']['label']); ?>
            </label>
            <input type="password" id="login-box" name="password" placeholder="<?php echo esc_attr($translation_strings['login']['placeholder']); ?>" class="linguise-input rounder mt-2 w-full" required autocomplete="current-password" />
        </div>
        <div class="submit-container">
            <input type="submit" class="linguise-btn rounder" value="<?php echo esc_attr($translation_strings['login']['button']); ?>" />
        </div>
    </div>
</form>
