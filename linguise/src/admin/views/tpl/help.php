<?php
defined('ABSPATH') || die('');
?>
<div class="content">
    <div class="linguise-settings-option transparent-option">
        <h2><?php esc_html_e('How-to load the language switcher flags:', 'linguise'); ?></h2>
        <div class="items-blocks">
            <div class="block">

                <span class="linguise-button blue-button waves-effect waves-light small-radius">
                    <?php esc_html_e('Wordpress menu', 'linguise'); ?>
                </span>

                <div class="switcher-as-content" id="wordpress_menu">
                    <span><?php echo sprintf(esc_html__('OK it’s ready to use, navigate to the %s and insert the menu element named "Linguise flags". Note that to display a menu as a popup you need to select the inline position (see below)', 'linguise'), '<a target="__blank" href="' . esc_url(admin_url('nav-menus.php')) . '">menu system</a>'); ?></span>
                </div>

                <span class="linguise-button blue-button waves-effect waves-light small-radius">
                    <?php esc_html_e('Shortcode', 'linguise'); ?>
                </span>

                <div class="switcher-as-content" id="shortcode">
                    <span><?php esc_html_e('Please copy the following shortcode anywhere in your WordPress content:', 'linguise'); ?> [linguise]</span>
                </div>

                <span class="linguise-button blue-button waves-effect waves-light small-radius">
                    <?php esc_html_e('PHP snippet', 'linguise'); ?>
                </span>

                <div class="switcher-as-content" id="php_snippet">
                    <span>echo do_shortcode('[linguise]');</span>
                </div>

                <span class="linguise-button blue-button waves-effect waves-light small-radius">
                    <?php esc_html_e('Latest 20 errors returned by Linguise', 'linguise'); ?>
                </span>

                <div class="switcher-as-content" id="php_snippet">
                    <?php if (count($latestLinguiseErrors)) : ?>
                    <ul>
                        <?php
                        foreach ($latestLinguiseErrors as $latestLinguiseError) { ?>
                            <li>
                                <?php echo esc_html($latestLinguiseError['time']); ?> :&nbsp;
                                <a href="<?php echo esc_url('https://www.linguise.com/documentation/debug-support/wordpress-plugin-error-codes/#' . $latestLinguiseError['code']); ?>" target="_blank" title="<?php esc_attr('Get more information about this error on Linguise', 'linguise');?>">
                                    <?php echo esc_html($latestLinguiseError['message']); ?>
                                </a>
                            </li>
                        <?php }
                        ?>
                    </ul>
                    <?php else : ?>
                        <?php esc_html_e('No errors', 'linguise'); ?>
                    <?php endif; ?>
                </div>

            </div>
            <div class="clearfix"></div>
        </div>
    </div>
</div>
