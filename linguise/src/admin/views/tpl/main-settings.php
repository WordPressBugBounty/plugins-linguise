<?php
defined('ABSPATH') || die('');

$latestLinguiseError = null;
if (count($latestLinguiseErrors)) {
    $lastErrorTimeCompare = new \DateTime($latestLinguiseErrors[0]['time']);
    $lastErrorTimeCompare->add(new DateInterval('PT5M'));
    if ($lastErrorTimeCompare > new \DateTime()) {
        // It's been less than 5 minutes
        $latestLinguiseError = $latestLinguiseErrors[0];
    }
}

/**
 * Locale compare check with country code ignore check supported.
 *
 * @param string $locale      The current locale being checked
 * @param string $test_locale The locale to test against
 *
 * @return boolean
 */
function locale_compare($locale, $test_locale)
{
    if (strcasecmp($locale, $test_locale) === 0) {
        return true;
    }

    $test_substr = substr($test_locale, 0, 2);

    return strcasecmp($locale, $test_substr) === 0;
}


/**
 * Get the native name of a language code
 *
 * @param string $lang_code The language code to get the native name for
 *
 * @return string
 */
function get_wp_lang_native_name($lang_code)
{
    require_once ABSPATH . 'wp-admin/includes/translation-install.php';
    $translations = wp_get_available_translations();
    if (isset($translations[$lang_code])) {
        return $translations[$lang_code]['native_name'];
    }

    return 'Unknown';
}

$website_locale = get_locale();
// strip the country code since Linguise does not use it.
// but, do not strip CC if locale is zh_CN or zh_TW
if (strpos($website_locale, '_') !== false && substr($website_locale, 0, 2) !== 'zh') {
    $website_locale = substr($website_locale, 0, strpos($website_locale, '_'));
}
$website_locale = strtolower(str_replace('_', '-', $website_locale));
$website_lang_name = '';
$linguise_lang_code = isset($options['default_language']) ? $options['default_language'] : 'en';
$linguise_lang_name = 'Unknown';
$linguise_supported = false;
foreach ($languages_names as $language_code => $language) {
    $current = $linguise_lang_code === 'en' ? $language['name'] : $language['original_name'];
    if (locale_compare($language_code, $website_locale)) {
        $website_lang_name = $current;
        $linguise_supported = true;
    }
    if ($language_code === $linguise_lang_code) {
        $linguise_lang_name = $current;
    }
}

?>
<div class="content">

    <?php if ($latestLinguiseError) : ?>
    <div class="linguise-settings-option full-width">
        <label style="color: red;" class="linguise-setting-label label-bolder linguise-tippy">
            <?php esc_html_e('Linguise latest error', 'linguise'); ?><span class="material-icons">error_outline</span>
        </label>
        <div style="width: 100%;display: inline-block;margin: 10px 0;padding-left: 15px;">
            <?php esc_html_e('Linguise returned an error in the last past 5 minutes, click on the link to get more information', 'linguise'); ?> :
            <strong><a href="<?php echo esc_url('https://www.linguise.com/documentation/debug-support/wordpress-plugin-error-codes/#' . $latestLinguiseError['code']); ?>" target="_blank" title="<?php esc_attr('Get more information about this error on Linguise', 'linguise');?>">
                <?php echo esc_html($latestLinguiseError['message']); ?>
            </a>
            </strong>
        </div>
    </div>
    <?php endif; ?>

    <div class="linguise-settings-option full-width">
        <label for="token"
               class="linguise-setting-label label-bolder linguise-tippy"
               data-tippy="<?php esc_html_e('Register or login to your Linguise dashboard using the link here. Then copy the API key attached to the domain', 'linguise'); ?>"><?php esc_html_e('Linguise API key', 'linguise'); ?><span class="material-icons">help_outline</span></label>
        <p style="width: 100%;display: inline-block;margin: 10px 0;padding-left: 15px;">
            <?php
                echo sprintf(
                    esc_html__(
                        '%1$s and copy your domain API key to activate the translation, use the domain %2$s .',
                        'linguise'
                    ),
                    '<a href="https://dashboard.linguise.com/account/register" target="_blank">' . esc_html__('Register an account', 'linguise') . '</a>',
                    '<strong>' . esc_html(get_site_url()) . '</strong>'
                );
                ?>
        </p>
        <div style="padding: 10px">
            <input type="text" class="linguise-input custom-input" name="linguise_options[token]"
                   id="token"
                   value="<?php echo isset($options['token']) ? esc_html($options['token']) : '' ?>"/>
            <input type="submit"
                   class="linguise-button blue-button waves-effect waves-light small-radius small-button"
                   id="token_apply" value="<?php esc_html_e('Apply', 'linguise'); ?>"/>
        </div>
    </div>
    <div class="linguise-settings-option full-width">
        <label for="original_language"
               class="linguise-setting-label label-bolder linguise-tippy"
               data-tippy="<?php esc_html_e('Select the default language of your website. Make sure it\'s similar to your Linguise dashboard configuration', 'linguise'); ?>"><?php esc_html_e('Website original language', 'linguise'); ?><span class="material-icons">help_outline</span></label>
        <div class="items">
            <select id="original_language" name="linguise_options[default_language]"
                    class="linguise-select original-color">
                <?php foreach ($languages_names as $language_code => $language) : ?>
                    <option value="<?php echo esc_attr($language_code); ?>" <?php echo isset($options['default_language']) ? (selected($options['default_language'], $language_code, false)) : (''); ?>>
                        <?php // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- don't need to translate this
                        esc_html_e($language['name']); ?> (<?php esc_html_e($language_code); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if (!locale_compare($linguise_lang_code, $website_locale)) : ?>
            <div class="items-blocks">
                <p class="block linguise-message linguise-warning" style="margin: 10px 30px;">
                    <?php if ($linguise_supported) : ?>
                        <?php echo sprintf(esc_html__('Your WordPress installation language is set to %1$s while Linguise is set to %2$s. This will prevent Linguise from working correctly.', 'linguise'), esc_attr($website_lang_name), esc_attr($linguise_lang_name)); ?>
                    <?php else : ?>
                        <?php echo sprintf(esc_html__('Your WordPress installation language (%s) is unsupported by Linguise. This will prevent Linguise from working correctly.', 'linguise'), esc_attr(get_wp_lang_native_name(get_locale()))); ?>
                    <?php endif; ?>
                    <?php echo sprintf(esc_html__('You can change your WordPress installation language in the %1$s. You can also check %2$s', 'linguise'), '<a target="_blank" href="' . esc_attr(get_admin_url(null, '/options-general.php#WPLANG')) . '">'. esc_html__('main settings page', 'linguise') . '</a>', '<a target="_blank" href="https://www.linguise.com/documentation/linguise-installation/install-linguise-on-wordpress/">'. esc_html__('our documentation page', 'linguise') . '</a>'); ?>
                </p>
            </div>
        <?php endif ?>
    </div>
    <div class="linguise-settings-option full-width">
        <label for="translate_into"
               class="linguise-setting-label label-bolder linguise-tippy"
               data-tippy="<?php esc_html_e('Select the languages you want to translate your website into. Make sure it\'s similar to your Linguise dashboard configuration', 'linguise'); ?>"><?php esc_html_e('Translate your website into', 'linguise'); ?><span class="material-icons">help_outline</span></label>
        <div class="items-blocks">
            <div class="block" style="margin: 10px;">
                <select id="translate_into"
                        data-placeholder="<?php esc_html_e('Choose your language into', 'linguise'); ?>" multiple
                        class="chosen-select enabled_languages full-on-mobile chosen-sortable" name="linguise_options[enabled_languages][]">
                    <?php foreach ($languages_names as $language_code => $language) : ?>
                        <option value="<?php echo esc_attr($language_code); ?>" <?php echo isset($options['enabled_languages']) ? (selected(in_array($language_code, $options['enabled_languages']), true, false)) : (''); ?>>
                            <?php // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- don't need to translate this
                            esc_html_e($language['name']); ?> (<?php esc_html_e($language_code); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="enabled_languages_sortable" class="enabled_languages_sortable">
            </div>
        </div>
        <p class="linguise_note note_lang_choose"><?php echo sprintf(esc_html__('Note that adding or removing languages won\'t apply as the language configuration is made from the %s only.', 'linguise'), '<a target="_blank" href="https://dashboard.linguise.com/">'. esc_html__('Linguise dashboard', 'linguise') .'</a>'); ?></p>
        <p class="linguise_note note_lang_choose"><?php esc_html_e('Please update the domain configuration from the dashboard and save the plugin settings again, thanks! :)', 'linguise') ?></p>
    </div>
    <div class="linguise-settings-option full-width">
        <label for="id-add_flag_automatically"
               class="linguise-setting-label label-bolder linguise-label-inline"><?php esc_html_e('Add language switcher automatically', 'linguise'); ?></label>
        <div class="linguise-switch-button" style="float: left">
            <label class="switch">
                <input type="hidden" name="linguise_options[add_flag_automatically]" value="0">
                <input type="checkbox" id="id-add_flag_automatically" name="linguise_options[add_flag_automatically]"
                       value="1" <?php echo isset($options['add_flag_automatically']) ? (checked($options['add_flag_automatically'], 1)) : (''); ?> />
                <div class="slider"></div>
            </label>
        </div>

        <p class="description" style="width: 100%; display: inline-block; padding-left: 15px; margin: 2px 0 10px 0">
            <?php esc_html_e('The flag switcher will be added automatically to all front pages of your website ', 'linguise'); ?><br/>
            <a href="#" onclick="document.getElementById('help').click();"><?php esc_html_e('If you want to display the flag in a menu item through shortcode or php code, please look into the help section ', 'linguise'); ?></a>
        </p>
    </div>
    <div class="linguise-settings-option full-width">
        <label class="linguise-setting-label label-bolder linguise-tippy"
               data-tippy="<?php esc_html_e('Display flag and/or language names. Language switcher default position could be anywhere in the content or in a fixed position that stays fixed or moves on scroll', 'linguise'); ?>"><?php esc_html_e('Language list display', 'linguise'); ?><span class="material-icons">help_outline</span></label>
        <div class="items-blocks language-list-display">
            <div class="display-type">
                <ul>
                    <?php foreach (array('side_by_side' => esc_html__('Side By Side', 'linguise'), 'dropdown' => esc_html__('Dropdown', 'linguise'), 'popup' => esc_html__('Popup', 'linguise')) as $key => $value) : ?>
                        <li>
                            <input type="radio" class="flag_display_type" id="id-<?php echo esc_attr($key) ?>"
                                   name="linguise_options[flag_display_type]"
                                   value="<?php echo esc_attr($key) ?>" <?php checked($options['flag_display_type'], $key) ?>>
                            <label for="id-<?php echo esc_attr($key) ?>"><?php echo esc_html($value) ?></label>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="linguise_mt_20">
                    <?php esc_html_e('Position: '); ?>
                    <select name="linguise_options[display_position]" class="linguise-select full-on-mobile original-color">
                        <?php
                        $positions = array(
                            'no' => esc_html__('In place', 'linguise'),
                            'top_left' => esc_html__('Top left', 'linguise'),
                            'top_left_no_scroll' => esc_html__('Top left (no-scroll)', 'linguise'),
                            'top_right' => esc_html__('Top right', 'linguise'),
                            'top_right_no_scroll' => esc_html__('Top right (no-scroll)', 'linguise'),
                            'bottom_left' => esc_html__('Bottom left', 'linguise'),
                            'bottom_left_no_scroll' => esc_html__('Bottom left (no-scroll)', 'linguise'),
                            'bottom_right' => esc_html__('Bottom right', 'linguise'),
                            'bottom_right_no_scroll' => esc_html__('Bottom right (no-scroll)', 'linguise')
                        );
                        foreach ($positions as $key => $value) :
                            ?>
                            <option <?php selected($options['display_position'], $key) ?>
                                    value="<?php echo esc_attr($key) ?>">
                                <?php echo esc_html($value) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="flag-name full-width">
                <ul>
                    <li>
                        <div class="linguise-switch-button">
                            <label class="switch" style="margin: 2px  10px">
                                <input type="hidden" name="linguise_options[enable_flag]" value="0">
                                <input type="checkbox" id="id-enable_flag" name="linguise_options[enable_flag]"
                                       class="enable_flag"
                                       value="1" <?php echo isset($options['enable_flag']) ? (checked($options['enable_flag'], 1)) : (''); ?> />
                                <div class="slider"></div>
                            </label>
                            <label for="id-enable_flag"><?php esc_html_e('Flag', 'linguise'); ?></label>
                        </div>
                    </li>

                    <li>
                        <div class="linguise-switch-button">
                            <label class="switch" style="margin: 2px  10px">
                                <input type="hidden" name="linguise_options[enable_language_name]" value="0">
                                <input type="checkbox" id="id-enable_language_name"
                                       name="linguise_options[enable_language_name]" class="enable_language_name"
                                       value="1" <?php echo isset($options['enable_language_name']) ? (checked($options['enable_language_name'], 1)) : (''); ?> />
                                <div class="slider"></div>
                            </label>
                            <label for="id-enable_language_name"><?php esc_html_e('Language Name', 'linguise'); ?></label>
                        </div>
                    </li>

                    <li>
                        <div class="linguise-switch-button">
                            <label class="switch" style="margin: 2px  10px">
                                <input type="hidden" name="linguise_options[enable_language_short_name]" value="0">
                                <input type="checkbox" id="id-enable_language_short_name"
                                       name="linguise_options[enable_language_short_name]" class="enable_language_short_name"
                                       value="1" <?php echo isset($options['enable_language_short_name']) ? (checked($options['enable_language_short_name'], 1)) : (''); ?> />
                                <div class="slider"></div>
                            </label>
                            <label for="id-enable_language_short_name"><?php esc_html_e('Short names (EN, ES...)', 'linguise'); ?></label>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="linguise-settings-option width-50-15">
        <label for="language_name_display" class="linguise-setting-label label-bolder linguise-tippy"
               data-tippy="<?php esc_html_e('In the language switcher, display the language names in English or in the original language, i.e. French or Français', 'linguise'); ?>"><?php esc_html_e('Language names display', 'linguise'); ?><span class="material-icons">help_outline</span></label>
        <div class="items">
            <select name="linguise_options[language_name_display]"
                    class="linguise-select right-select original-color language_name_display">
                <option value="en" <?php echo isset($options['language_name_display']) ? (selected($options['language_name_display'], 'en', false)) : (''); ?>><?php esc_html_e('English', 'linguise'); ?></option>
                <option value="native" <?php echo isset($options['language_name_display']) ? (selected($options['language_name_display'], 'native', false)) : (''); ?>><?php esc_html_e('Native Language', 'linguise'); ?></option>
            </select>
        </div>
    </div>

    <div class="linguise-settings-option width-50-15">
        <label for="flag_shape" class="linguise-setting-label label-bolder linguise-label-inline linguise-tippy" data-tippy="<?php esc_html_e('Use round svg or rectangular svg flag icons', 'linguise'); ?>"><?php esc_html_e('Flag style', 'linguise'); ?><span class="material-icons">help_outline</span></label>
        <div class="items">
            <select name="linguise_options[flag_shape]"
                    class="linguise-select right-select original-color flag_shape">
                <option value="rounded" <?php echo isset($options['flag_shape']) ? (selected($options['flag_shape'], 'rounded', false)) : (''); ?>><?php esc_html_e('Round', 'linguise'); ?></option>
                <option value="rectangular" <?php echo isset($options['flag_shape']) ? (selected($options['flag_shape'], 'rectangular', false)) : (''); ?>><?php esc_html_e('Rectangular', 'linguise'); ?></option>
            </select>
        </div>
    </div>

    <div class="linguise-settings-option width-50-15">
        <label for="flag_en_type" class="linguise-setting-label label-bolder linguise-label-inline linguise-tippy" data-tippy="<?php esc_html_e('Use the Great Britain or USA Flag for english', 'linguise'); ?>"><?php esc_html_e('English flag type', 'linguise'); ?><span class="material-icons">help_outline</span></label>
        <div class="items">
            <select name="linguise_options[flag_en_type]"
                    class="linguise-select right-select original-color flag_en_type">
                <option value="en-us" <?php echo isset($options['flag_en_type']) ? (selected($options['flag_en_type'], 'en-us', false)) : (''); ?>><?php esc_html_e('USA flag', 'linguise'); ?></option>
                <option value="en-gb" <?php echo isset($options['flag_en_type']) ? (selected($options['flag_en_type'], 'en-gb', false)) : (''); ?>><?php esc_html_e('Great Britain Flag', 'linguise'); ?></option>
            </select>
        </div>
    </div>

    <div class="linguise-settings-option width-50-15">
        <label for="flag_de_type" class="linguise-setting-label label-bolder linguise-label-inline linguise-tippy" data-tippy="<?php esc_html_e('Use the Austria or German Flag for german', 'linguise'); ?>"><?php esc_html_e('German flag type', 'linguise'); ?><span class="material-icons">help_outline</span></label>
        <div class="items">
            <select name="linguise_options[flag_de_type]"
                    class="linguise-select right-select original-color flag_de_type">
                <option value="de" <?php echo isset($options['flag_de_type']) ? (selected($options['flag_de_type'], 'de', false)) : (''); ?>><?php esc_html_e('German Flag', 'linguise'); ?></option>
                <option value="de-at" <?php echo isset($options['flag_de_type']) ? (selected($options['flag_de_type'], 'de-at', false)) : (''); ?>><?php esc_html_e('Austria Flag', 'linguise'); ?></option>
            </select>
        </div>
    </div>

    <div class="linguise-settings-option width-50-15">
        <label for="flag_es_type" class="linguise-setting-label label-bolder linguise-label-inline linguise-tippy" data-tippy="<?php esc_html_e('Use the Mexico or Spanish Flag for spanish', 'linguise'); ?>"><?php esc_html_e('Spanish flag type', 'linguise'); ?><span class="material-icons">help_outline</span></label>
        <div class="items">
            <select name="linguise_options[flag_es_type]"
                    class="linguise-select right-select original-color flag_es_type">
                <option value="es" <?php echo isset($options['flag_es_type']) ? (selected($options['flag_es_type'], 'es', false)) : (''); ?>><?php esc_html_e('Spanish Flag', 'linguise'); ?></option>
                <option value="es-mx" <?php echo isset($options['flag_es_type']) ? (selected($options['flag_es_type'], 'es-mx', false)) : (''); ?>><?php esc_html_e('Mexico Flag', 'linguise'); ?></option>
                <option value="es-pu" <?php echo isset($options['flag_es_type']) ? (selected($options['flag_es_type'], 'es-pu', false)) : (''); ?>><?php esc_html_e('Peruvian Flag', 'linguise'); ?></option>
            </select>
        </div>
    </div>

    <div class="linguise-settings-option width-50-15">
        <label for="flag_pt_type" class="linguise-setting-label label-bolder linguise-label-inline linguise-tippy" data-tippy="<?php esc_html_e('Use the Portuguese or Brazilian Flag for Portuguese', 'linguise'); ?>"><?php esc_html_e('Portuguese flag type', 'linguise'); ?><span class="material-icons">help_outline</span></label>
        <div class="items">
            <select name="linguise_options[flag_pt_type]"
                    class="linguise-select right-select original-color flag_pt_type">
                <option value="pt" <?php echo isset($options['flag_pt_type']) ? (selected($options['flag_pt_type'], 'pt', false)) : (''); ?>><?php esc_html_e('Portuguese Flag', 'linguise'); ?></option>
                <option value="pt-br" <?php echo isset($options['flag_pt_type']) ? (selected($options['flag_pt_type'], 'pt-br', false)) : (''); ?>><?php esc_html_e('Brazilian Flag', 'linguise'); ?></option>
            </select>
        </div>
    </div>

    <div class="linguise-settings-option width-50-15">
        <label for="flag_tw_type" class="linguise-setting-label label-bolder linguise-label-inline linguise-tippy" data-tippy="<?php esc_html_e('Use the Taiwanese or Chinese Flag for Taiwanese', 'linguise'); ?>"><?php esc_html_e('Taiwanese flag type', 'linguise'); ?><span class="material-icons">help_outline</span></label>
        <div class="items">
            <select name="linguise_options[flag_tw_type]"
                    class="linguise-select right-select original-color flag_tw_type">
                <option value="zh-tw" <?php echo isset($options['flag_tw_type']) ? (selected($options['flag_tw_type'], 'zh-tw', false)) : (''); ?>><?php esc_html_e('Taiwanese Flag', 'linguise'); ?></option>
                <option value="zh-cn" <?php echo isset($options['flag_tw_type']) ? (selected($options['flag_tw_type'], 'zh-cn', false)) : (''); ?>><?php esc_html_e('Chinese Flag', 'linguise'); ?></option>
            </select>
        </div>
    </div>

    <div class="break"></div>

    <div class="linguise-settings-option width-50-15">
        <label for="id-flag_border_radius"
               class="linguise-setting-label label-bolder linguise-label-inline linguise-tippy" data-tippy="<?php esc_html_e('If you\'re using the rectangle flag shape you can apply a custom border radius in pixels', 'linguise'); ?>"><?php esc_html_e('Flag border radius (px)', 'linguise'); ?><span class="material-icons">help_outline</span></label>
        <input type="number" name="linguise_options[flag_border_radius]" class="flag_border_radius"
               value="<?php echo (int)$options['flag_border_radius'] ?>" style="margin: 10px 15px; width: 100px; float: right">
    </div>

    <div class="linguise-settings-option width-50-15">
        <label for="id-flag_width"
               class="linguise-setting-label label-bolder linguise-label-inline linguise-tippy" data-tippy="<?php esc_html_e('Select flags size in pixels. That doesn\'t change the image weight as it\'s a .svg format', 'linguise'); ?>"><?php esc_html_e('Flag size (px)', 'linguise'); ?><span class="material-icons">help_outline</span></label>
        <input type="number" name="linguise_options[flag_width]" class="flag_width"
               value="<?php echo (int)$options['flag_width'] ?>" style="margin: 10px 15px; width: 100px; float: right">
    </div>

    <div class="linguise-settings-option width-50-15">
        <label for="language_name_color" class="linguise-setting-label label-bolder linguise-label-inline linguise-tippy" data-tippy="<?php esc_html_e('Select a default text color for your language names', 'linguise'); ?>"><?php esc_html_e('Language name color', 'linguise'); ?><span class="material-icons">help_outline</span></label>
        <div class="items">
            <input type="text" name="linguise_options[language_name_color]" value="<?php echo esc_attr($options['language_name_color']) ?>" class="language_name_color linguise-color-field" data-default-color="#222" />
        </div>
    </div>

    <div class="linguise-settings-option width-50-15">
        <label for="language_name_hover_color" class="linguise-setting-label label-bolder linguise-label-inline linguise-tippy" data-tippy="<?php esc_html_e('Select a mouse hover text color for your language names', 'linguise'); ?>"><?php esc_html_e('Language name hover color', 'linguise'); ?><span class="material-icons">help_outline</span></label>
        <div class="items">
            <input type="text" name="linguise_options[language_name_hover_color]" value="<?php echo esc_attr($options['language_name_hover_color']) ?>" class="language_name_hover_color linguise-color-field" data-default-color="#222" />
        </div>
    </div>

    <div class="linguise-settings-option width-50-15">
        <label for="popup_language_name_color" class="linguise-setting-label label-bolder linguise-label-inline linguise-tippy" data-tippy="<?php esc_html_e('Color of the language title in the popup or in the dropdown areas', 'linguise'); ?>"><?php esc_html_e('Popup language color', 'linguise'); ?><span class="material-icons">help_outline</span></label>
        <div class="items">
            <input type="text" name="linguise_options[popup_language_name_color]" value="<?php echo esc_attr($options['popup_language_name_color'] ?? '#222') ?>" class="popup_language_name_color linguise-color-field" data-default-color="#222" />
        </div>
    </div>

    <div class="linguise-settings-option width-50-15">
        <label for="popup_language_name_hover_color" class="linguise-setting-label label-bolder linguise-label-inline linguise-tippy" data-tippy="<?php esc_html_e('Select a mouse hover text color of the language title in the popup or in the dropdown areas', 'linguise'); ?>"><?php esc_html_e('Popup language hover color', 'linguise'); ?><span class="material-icons">help_outline</span></label>
        <div class="items">
            <input type="text" name="linguise_options[popup_language_name_hover_color]" value="<?php echo esc_attr($options['popup_language_name_hover_color'] ?? '#222') ?>" class="popup_language_name_hover_color linguise-color-field" data-default-color="#222" />
        </div>
    </div>

    <div class="linguise-settings-option width-50-15">
        <label for="language_name_color" class="linguise-setting-label label-bolder full-width linguise-tippy" data-tippy="<?php esc_html_e('Color and shadow size for your language flags', 'linguise'); ?>"><?php esc_html_e('Flag box shadow', 'linguise'); ?><span class="material-icons">help_outline</span></label>
        <div class="flag_shadow_element full-width">
            <p>
                <label><?php esc_html_e('Shadow H offset (px)', 'linguise'); ?></label>
                <input type="range" min="-50" max="50" value="<?php echo esc_attr($options['flag_shadow_h']) ?>" step="1" class="flag_shadow_h" onchange="window.linguiseUpdateTextInput(this.value, 'flag_shadow_h');">
                <input type="number" id="flag_shadow_h" name="linguise_options[flag_shadow_h]" onchange="window.linguiseUpdateSliderInput(this.value, 'flag_shadow_h');" value="<?php echo esc_attr($options['flag_shadow_h']) ?>">
            </p>
            <p>
                <label><?php esc_html_e('Shadow V offset (px)', 'linguise'); ?></label>
                <input type="range" min="-50" max="50" value="<?php echo esc_attr($options['flag_shadow_v']) ?>" step="1" class="flag_shadow_v" onchange="window.linguiseUpdateTextInput(this.value, 'flag_shadow_v');">
                <input type="number" id="flag_shadow_v" name="linguise_options[flag_shadow_v]" onchange="window.linguiseUpdateSliderInput(this.value, 'flag_shadow_v');" value="<?php echo esc_attr($options['flag_shadow_v']) ?>">
            </p>
            <p>
                <label><?php esc_html_e('Shadow blur (px)', 'linguise'); ?></label>
                <input type="range" min="0" max="50" value="<?php echo esc_attr($options['flag_shadow_blur']) ?>" step="1" class="flag_shadow_blur" onchange="window.linguiseUpdateTextInput(this.value, 'flag_shadow_blur');">
                <input type="number" id="flag_shadow_blur" name="linguise_options[flag_shadow_blur]" onchange="window.linguiseUpdateSliderInput(this.value, 'flag_shadow_blur');" value="<?php echo esc_attr($options['flag_shadow_blur']) ?>">
            </p>
            <p>
                <label><?php esc_html_e('Shadow spread (px)', 'linguise'); ?></label>
                <input type="range" min="0" max="50" value="<?php echo esc_attr($options['flag_shadow_spread']) ?>" step="1" class="flag_shadow_spread" onchange="window.linguiseUpdateTextInput(this.value, 'flag_shadow_spread');">
                <input type="number" id="flag_shadow_spread" name="linguise_options[flag_shadow_spread]" onchange="window.linguiseUpdateSliderInput(this.value, 'flag_shadow_spread');" value="<?php echo esc_attr($options['flag_shadow_spread']) ?>">
            </p>
            <p>
                <label><?php esc_html_e('Shadow color', 'linguise'); ?></label>
                <input type="text" name="linguise_options[flag_shadow_color]" value="<?php echo esc_attr($options['flag_shadow_color']) ?>" class="flag_shadow_color linguise-color-field" data-default-color="#bfbfbf" />
            </p>
        </div>
    </div>

    <div class="linguise-settings-option width-50-15">
        <label for="language_name_color" class="linguise-setting-label label-bolder full-width linguise-tippy" data-tippy="<?php esc_html_e('Color and shadow size for your language flags on mouse hover', 'linguise'); ?>"><?php esc_html_e('Flag box shadow on hover', 'linguise'); ?><span class="material-icons">help_outline</span></label>
        <div class="flag_shadow_element full-width">
            <p>
                <label><?php esc_html_e('Shadow H offset (px)', 'linguise'); ?></label>
                <input type="range" min="-50" max="50" value="<?php echo esc_attr($options['flag_hover_shadow_h']) ?>" step="1" class="flag_hover_shadow_h" onchange="window.linguiseUpdateTextInput(this.value, 'flag_hover_shadow_h');">
                <input type="number" id="flag_hover_shadow_h" name="linguise_options[flag_hover_shadow_h]" onchange="window.linguiseUpdateSliderInput(this.value, 'flag_hover_shadow_h');" value="<?php echo esc_attr($options['flag_hover_shadow_h']) ?>">
            </p>
            <p>
                <label><?php esc_html_e('Shadow V offset (px)', 'linguise'); ?></label>
                <input type="range" min="-50" max="50" value="<?php echo esc_attr($options['flag_hover_shadow_v']) ?>" step="1" class="flag_hover_shadow_v" onchange="window.linguiseUpdateTextInput(this.value, 'flag_hover_shadow_v');">
                <input type="number" id="flag_hover_shadow_v" name="linguise_options[flag_hover_shadow_v]" onchange="window.linguiseUpdateSliderInput(this.value, 'flag_hover_shadow_v');" value="<?php echo esc_attr($options['flag_hover_shadow_v']) ?>">
            </p>
            <p>
                <label><?php esc_html_e('Shadow blur (px)', 'linguise'); ?></label>
                <input type="range" min="0" max="50" value="<?php echo esc_attr($options['flag_hover_shadow_blur']) ?>" step="1" class="flag_hover_shadow_blur" onchange="window.linguiseUpdateTextInput(this.value, 'flag_hover_shadow_blur');">
                <input type="number" id="flag_hover_shadow_blur" name="linguise_options[flag_hover_shadow_blur]" onchange="window.linguiseUpdateSliderInput(this.value, 'flag_hover_shadow_blur');" value="<?php echo esc_attr($options['flag_hover_shadow_blur']) ?>">
            </p>
            <p>
                <label><?php esc_html_e('Shadow spread (px)', 'linguise'); ?></label>
                <input type="range" min="0" max="50" value="<?php echo esc_attr($options['flag_hover_shadow_spread']) ?>" step="1" class="flag_hover_shadow_spread" onchange="window.linguiseUpdateTextInput(this.value, 'flag_hover_shadow_spread');">
                <input type="number" id="flag_hover_shadow_spread" name="linguise_options[flag_hover_shadow_spread]" onchange="window.linguiseUpdateSliderInput(this.value, 'flag_hover_shadow_spread');" value="<?php echo esc_attr($options['flag_hover_shadow_spread']) ?>">
            </p>
            <p>
                <label><?php esc_html_e('Shadow color', 'linguise'); ?></label>
                <input type="text" name="linguise_options[flag_hover_shadow_color]" value="<?php echo esc_attr($options['flag_hover_shadow_color']) ?>" class="flag_hover_shadow_color linguise-color-field" data-default-color="#bfbfbf" />
            </p>
        </div>
    </div>
</div>

<p class="submit" style="margin-top: 10px;margin-right: 10px;display: inline-block;float: right; width: 100%;'"><input
            type="submit"
            name="linguise_submit"
            id="submit"
            class="button button-primary"
            value="<?php esc_html_e('Save Settings', 'linguise'); ?>">
</p>
