<?php
defined('ABSPATH') || die('');
?>
<div class="content">
    <ul>
        <li class="linguise-settings-option full-width">
            <div class="full-width flex-vertical">
                <label for="id-cache_enabled"
                       class="linguise-setting-label label-bolder linguise-label-inline linguise-tippy" data-tippy="<?php esc_html_e('Store URLs and some translated content in a local cache to render the pages faster', 'linguise'); ?>"><?php esc_html_e('Use cache', 'linguise'); ?><span class="material-icons">help_outline</span></label>
                <div class="linguise-switch-button" style="float: left">
                    <label class="switch">
                        <input type="hidden" name="linguise_options[cache_enabled]" value="0">
                        <input type="checkbox" id="id-cache_enabled" name="linguise_options[cache_enabled]"
                               value="1" <?php echo isset($options['cache_enabled']) ? (checked($options['cache_enabled'], 1)) : (''); ?> />
                        <div class="slider"></div>
                    </label>
                </div>
                <?php $clearCacheUrl = wp_nonce_url(admin_url('admin-ajax.php') . '?action=linguise_clear_cache', '_linguise_nonce_'); ?>
                <button type="button" id="linguise_clear_cache" data-href="<?php echo esc_url($clearCacheUrl); ?>" class="button button-primary small-button"><?php esc_html_e('Clear Cache', 'linguise'); ?></button>
            </div>

            <div class="full-width" style="display: flex; align-items: center">
                <label for="id-cache_enabled"
                       class="linguise-setting-label label-bolder"><?php esc_html_e('Maximum cache disk space usage (in MB)', 'linguise'); ?></label>
                <input type="number" name="linguise_options[cache_max_size]"
                       value="<?php echo (int)$options['cache_max_size'] ?>" style="margin-left: 20px; width: 100px;">
            </div>
        </li>

        <li class="linguise-settings-option full-width">
            <label for="browser_redirect" class="linguise-setting-label label-bolder linguise-label-inline linguise-tippy"
                   data-tippy="<?php esc_html_e('Automatically redirect users based on the browser language. The user will still be able to change the language manually but this is NOT recommended as users may use various browser languages or speak several languages', 'linguise'); ?>"><?php esc_html_e('Browser Language Redirect', 'linguise'); ?><span class="material-icons">help_outline</span></label>
            <div class="linguise-switch-button">
                <label class="switch">
                    <input type="hidden" name="linguise_options[browser_redirect]" value="0">
                    <input type="checkbox" id="browser_redirect" name="linguise_options[browser_redirect]"
                           value="1" <?php echo isset($options['browser_redirect']) ? (checked($options['browser_redirect'], 1)) : (''); ?> />
                    <div class="slider"></div>
                </label>
            </div>
        </li>

        <li class="linguise-settings-option full-width">
            <label for="woocommerce_mail_translation" class="linguise-setting-label label-bolder linguise-label-inline linguise-tippy"
                   data-tippy="<?php esc_html_e('Enable WooCommerce email translation. Emails sent to customers will be translated to the language they made their order from. (This options can increase a lot your translation quota )', 'linguise'); ?>"><?php esc_html_e('Translate WooCommerce emails', 'linguise'); ?><span class="material-icons">help_outline</span></label>
            <div class="linguise-switch-button">
                <label class="switch">
                    <input type="hidden" name="linguise_options[woocommerce_emails_translation]" value="0">
                    <input type="checkbox" id="search_translation" name="linguise_options[woocommerce_emails_translation]"
                           value="1" <?php echo isset($options['woocommerce_emails_translation']) ? (checked($options['woocommerce_emails_translation'], 1)) : (''); ?> />
                    <div class="slider"></div>
                </label>
            </div>
        </li>

        <li class="linguise-settings-option full-width">
            <label for="search_translation" class="linguise-setting-label label-bolder linguise-label-inline linguise-tippy"
                   data-tippy="<?php esc_html_e('Enable search translation. Visitors will be able to search in their language. (This options can increase a lot your translation quota )', 'linguise'); ?>"><?php esc_html_e('Translate searches', 'linguise'); ?><span class="material-icons">help_outline</span></label>
            <div class="linguise-switch-button">
                <label class="switch">
                    <input type="hidden" name="linguise_options[search_translation]" value="0">
                    <input type="checkbox" id="search_translation" name="linguise_options[search_translation]"
                           value="1" <?php echo isset($options['search_translation']) ? (checked($options['search_translation'], 1)) : (''); ?> />
                    <div class="slider"></div>
                </label>
            </div>
        </li>

        <li class="linguise-settings-option full-width">
            <div class="full-width">
                <label for="dynamic_translations" class="linguise-setting-label label-bolder linguise-label-inline linguise-tippy"
                    data-tippy="<?php esc_html_e('Dynamic content generated by JavaScript or AJAX request will be translated (This option can increase a lot your translation quota)', 'linguise'); ?>"><?php esc_html_e('Dynamic translations', 'linguise'); ?><span class="material-icons">help_outline</span></label>
                <div class="linguise-switch-button">
                    <label class="switch">
                        <input type="hidden" name="linguise_options[dynamic_translations]" value="0">
                        <input type="checkbox" id="dynamic_translations" name="linguise_options[dynamic_translations]"
                            value="1" <?php echo isset($options['dynamic_translations']['enabled']) ? (checked($options['dynamic_translations']['enabled'], 1)) : (''); ?> />
                        <div class="slider"></div>
                    </label>
                </div>
            </div>

            <p class="linguise_note note_dynamic_tl"><?php echo sprintf(esc_html__('Note that this feature also need to be enabled in %s since it\'s not synchronized!', 'linguise'), '<a target="_blank" href="https://dashboard.linguise.com/">'. esc_html__('Linguise dashboard', 'linguise') .'</a>'); ?></p>
            <p class="linguise_note note_dynamic_tl"><?php esc_html_e('Please update the domain configuration from the dashboard and save the plugin settings again, thanks! :)', 'linguise') ?></p>
        </li>

        <li class="linguise-settings-option full-width">
            <label for="pre_text"
                   class="linguise-setting-label label-bolder linguise-tippy" data-tippy="<?php esc_html_e('Add some text before the language switcher content in the popup view. HTML is also OK', 'linguise'); ?>"><?php esc_html_e('Pre-text in language popup', 'linguise'); ?><span class="material-icons">help_outline</span></label>
            <div class="items-blocks" style="padding: 10px"><textarea name="linguise_options[pre_text]"
                                                id="pre_text"><?php echo esc_html($options['pre_text']) ?></textarea>
            </div>
        </li>
        <li class="linguise-settings-option full-width">
            <label for="post_text"
                   class="linguise-setting-label label-bolder linguise-tippy" data-tippy="<?php esc_html_e('Add some text after the language switcher content in the popup view. HTML is also OK', 'linguise'); ?>"><?php esc_html_e('Post-text in language popup', 'linguise'); ?><span class="material-icons">help_outline</span></label>
            <div class="items-blocks" style="padding: 10px"><textarea name="linguise_options[post_text]"
                                                id="post_text"><?php echo esc_html($options['post_text']) ?></textarea>
            </div>
        </li>
        <li class="linguise-settings-option full-width">
            <label for="custom_css"
                   class="linguise-setting-label label-bolder linguise-tippy" data-tippy="<?php esc_html_e('Add custom CSS to apply on the Linguise language switcher', 'linguise'); ?>"><?php esc_html_e('Custom CSS Field:', 'linguise'); ?><span class="material-icons">help_outline</span></label>
            <div class="items-blocks" style="padding: 10px"><textarea cols="100" rows="5" class="custom_css"
                                                name="linguise_options[custom_css]"
                                                id="custom_css"><?php echo esc_html($options['custom_css']) ?></textarea>
            </div>
        </li>

        <li class="linguise-settings-option full-width">
            <label for="debug" class="linguise-setting-label label-bolder linguise-label-inline linguise-tippy"
                   data-tippy="<?php esc_html_e('Use for debugging purpose only. It will create a file with a log of content. Only enable it if you need it and only for a limited time', 'linguise'); ?>"><?php esc_html_e('Enable debug', 'linguise'); ?><span class="material-icons">help_outline</span></label>
            <div class="linguise-switch-button">
                <label class="switch">
                    <input type="hidden" name="linguise_options[debug]" value="0">
                    <input type="checkbox" id="debug" name="linguise_options[debug]"
                           value="1" <?php echo isset($options['debug']) ? (checked($options['debug'], 1)) : (''); ?> />
                    <div class="slider"></div>
                </label>
                <?php
                // Full file log
                $log_path = LINGUISE_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'linguise' . DIRECTORY_SEPARATOR . 'script-php' . DIRECTORY_SEPARATOR;
                $full_debug_file =  $log_path . 'debug.php';
                $last_errors_file = $log_path . 'errors.php';
                if (file_exists($full_debug_file)) { ?>
                <div style="margin-right: 15px;">
                    <a href="<?php echo esc_url(admin_url('admin-ajax.php')); ?>?action=linguise_download_debug" target="_blank"><?php esc_html_e('Download debug file', 'linguise'); ?></a>
                </div>
                <div>
                    <?php $truncate_url = wp_nonce_url(admin_url('admin-ajax.php') . '?action=linguise_truncate_debug', '_linguise_nonce_'); ?>
                    <a href="<?php echo esc_url($truncate_url); ?>" id="linguise_truncate_debug"><?php esc_html_e('Clear debug file', 'linguise'); ?></a>
                </div>
                <?php } ?>
            </div>
            <?php
            if (file_exists($last_errors_file)) :
                $last_log = file_get_contents($last_errors_file);
                // Remote top line for php die
                $log_lines = explode("\n", $last_log);
                if (count($log_lines) >= 1) {
                    array_shift($log_lines);
                }
                $last_log = implode("\n", $log_lines);
                ?>

                <div class="items-blocks" style="padding: 10px">
                    <textarea rows="5" class="last_log"
                              id="last_log"><?php echo esc_html($last_log); ?>
                    </textarea>
                </div>
            <?php endif; ?>
        </li>

        <li class="linguise-settings-option full-width">
            <label for="id-alternate_link"
                   class="linguise-setting-label label-bolder linguise-label-inline"><?php esc_html_e('Insert alternate link tag', 'linguise'); ?></label>
            <div class="linguise-switch-button" style="float: left">
                <label class="switch">
                    <input type="hidden" name="linguise_options[alternate_link]" value="0">
                    <input type="checkbox" id="id-alternate_link" name="linguise_options[alternate_link]"
                           value="1" <?php echo isset($options['alternate_link']) ? (checked($options['alternate_link'], 1)) : (''); ?> />
                    <div class="slider"></div>
                </label>
            </div>

            <p class="description" style="width: 100%; display: inline-block; padding-left: 15px; margin: 2px 0 10px 0">
                <?php esc_html_e('It\'s highly recommended keeping this setting activated for SEO purpose', 'linguise'); ?>
            </p>
        </li>

        <li class="linguise-settings-option full-width">
            <label for="ukraine_redirect" class="linguise-setting-label label-bolder linguise-label-inline linguise-tippy"
                   data-tippy="<?php esc_html_e('🇺🇦 Automatically redirect all users to the ukrainian language. This options is incompatible with the browser redirect option.', 'linguise'); ?>"><?php esc_html_e('Ukrainian Language Redirect', 'linguise'); ?><span class="material-icons">help_outline</span></label>
            <div class="linguise-switch-button">
                <label class="switch">
                    <input type="hidden" name="linguise_options[ukraine_redirect]" value="0">
                    <input type="checkbox" id="ukraine_redirect" name="linguise_options[ukraine_redirect]"
                           value="1" <?php echo isset($options['ukraine_redirect']) ? (checked($options['ukraine_redirect'], 1)) : (''); ?> />
                    <div class="slider"></div>
                </label>
            </div>
            <div class="items-blocks" style="padding: 10px">
                <div>
                    <strong>
                        <?php esc_html_e('Disclaimer: Linguise is not responsible for any legal issues that may arise from the use of this feature.', 'linguise'); ?>
                    </strong>
                    <br />
                    <?php esc_html_e('Linguise endeavors to assist Ukrainian users in adhering to the law to the best of its ability, however, it cannot guarantee full compliance with legal regulations. It is strongly recommended that you consult with a lawyer to ensure your website is in compliance with all applicable laws. Linguise cannot be held liable for any legal action taken against your website in case of non-compliance.', 'linguise'); ?>
                    <br />
                    <strong>
                        <?php esc_html_e('Note on SEO impact', 'linguise'); ?>
                    </strong>
                    <br />
                    <?php esc_html_e('This option will redirect all requests to the ukrainian language. It could have a negative impact on your SEO.', 'linguise'); ?>
                </div>
            </div>
        </li>
    </ul>
</div>

<p class="submit" style="margin-top: 10px;display: inline-block;float: right; width: 100%"><input
            type="submit"
            name="linguise_submit"
            id="submit"
            class="button button-primary"
            value="<?php esc_html_e('Save Settings', 'linguise'); ?>">
</p>

<script type="text/javascript">
    (() => {
        const hideUnhide = () => {
            const checkboxDynTranslations = document.getElementById('dynamic_translations');

            checkboxDynTranslations.addEventListener('change', () => {
                jQuery('.note_dynamic_tl').fadeIn(1000);
            });
        };

        if (document.readyState !== 'loading') {
            hideUnhide()
        } else {
            document.addEventListener('DOMContentLoaded', hideUnhide)
        }
    })();
</script>
