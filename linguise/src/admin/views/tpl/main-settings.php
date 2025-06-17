<?php

defined('ABSPATH') || die('');

$options = linguiseGetOptions();
$has_api_key = !empty($options['token']);

// Collection of Translation strings, for easy translation and modification
$translation_strings = [
    'token' => [
        'title' => __('Linguise API key', 'linguise'),
        'help' => __('Register or login to your Linguise dashboard using the link here. Then copy the API key attached to the domain', 'linguise'),
        'description' => __('To activate the translation, you need to log in to your account or register.', 'linguise'),
    ],
    'register' => __('Register', 'linguise'),
    'login' => __('Login', 'linguise'),
    'apply' => __('Apply', 'linguise'),
    'clipboard' => __('Copy to clipboard', 'linguise'),

    'translation' => __('Translation', 'linguise'),
    'default_language' => [
        'title' => __('Website original language', 'linguise'),
        'help' => __('Select the default language of your website. Make sure it\'s similar to your Linguise dashboard configuration', 'linguise'),
        'compat_support' => __('Your WordPress installation language is set to %1$s while Linguise is set to %2$s. This will prevent Linguise from working correctly.', 'linguise'),
        'compat_unsupported' => __('Your WordPress installation language (%s) is unsupported by Linguise. This will prevent Linguise from working correctly.', 'linguise'),
        'compat_suggest' => __('You can change your WordPress installation language in the %1$s. You can also check %2$s'),
        'compat_suggest_wp' => __('main settings page', 'linguise'),
        'compat_suggest_docs' => __('our documentation page', 'linguise'),
    ],
    'translate_into' => [
        'title' => __('Translate your website into', 'linguise'),
        'help' => __('Select the languages you want to translate your website into. Make sure it\'s similar to your Linguise dashboard configuration', 'linguise'),
        'placeholder' => __('Choose your language into', 'linguise'),
    ],
    'auto_switcher' => [
        'title' => __('Add language switcher automatically', 'linguise'),
        'description' => __('The flag switcher will be added automatically to all front pages of your website.', 'linguise'),
        'description_2' => __('If you want to display the flag in a menu item through shortcode or php code, please look into the %1$s', 'linguise'),
        'target' => __('help section.', 'linguise'),
    ],

    'display' => __('Display options', 'linguise'),
    'language_display' => [
        'title' => __('Language list display', 'linguise'),
        'help' => __('Display flag and/or language names. Language switcher default position could be anywhere in the content or in a fixed position that stays fixed or moves on scroll', 'linguise'),
        'mode-sbs' => __('Side by side', 'linguise'),
        'mode-dropdown' => __('Dropdown', 'linguise'),
        'mode-popup' => __('Popup', 'linguise'),
    ],
    'position' => [
        'title' => __('Position', 'linguise'),
        'pos-topl' => __('Top left', 'linguise'),
        'pos-topr' => __('Top right', 'linguise'),
        'pos-botl' => __('Bottom left', 'linguise'),
        'pos-botr' => __('Bottom right', 'linguise'),
        'pos-topl-ns' => __('Top left (no-scroll)', 'linguise'),
        'pos-topr-ns' => __('Top right (no-scroll)', 'linguise'),
        'pos-botl-ns' => __('Bottom left (no-scroll)', 'linguise'),
        'pos-botr-ns' => __('Bottom right (no-scroll)', 'linguise'),
        'pos-inplace' => __('In place', 'linguise'),
    ],
    'show_flag' => __('Show flag', 'linguise'),
    'language_names' => [
        'label' => __('Show language names', 'linguise'),
        'full' => __('Full names (English, Spanish...)', 'linguise'),
        'short' => __('Short names (EN, ES...)', 'linguise'),
    ],
    'language_prefer' => [
        'label' => __('Language names display', 'linguise'),
        'help' => __('In the language switcher, display the language names in English or in the original language, i.e. French or Français', 'linguise'),
        'en' => __('English', 'linguise'),
        'native' => __('Native language', 'linguise'),
    ],
    'flag_style' => [
        'label' => __('Flag style', 'linguise'),
        'help' => __('Select the flag style you want to use for the language switcher', 'linguise'),
        'round' => __('Round', 'linguise'),
        'rectangle' => __('Rectangular', 'linguise'),
    ],

    'flag_type' => __('Flag type settings', 'linguise'),
    'flag_type_en' => [
        'title' => __('English flag type', 'linguise'),
        'help' => __('Select the preferred flags for the English languages', 'linguise'),
        'usa' => __('USA', 'linguise'),
        'gb' => __('Great Britain', 'linguise'),
    ],
    'flag_type_de' => [
        'title' => __('German flag type', 'linguise'),
        'help' => __('Select the preferred flags for the German languages', 'linguise'),
        'de' => __('Germany', 'linguise'),
        'at' => __('Austria', 'linguise'),
    ],
    'flag_type_pt' => [
        'title' => __('Portuguese flag type', 'linguise'),
        'help' => __('Select the preferred flags for the Portuguese languages', 'linguise'),
        'pt' => __('Portugal', 'linguise'),
        'br' => __('Brazil', 'linguise'),
    ],
    'flag_type_tw' => [
        'title' => __('Taiwanese flag type', 'linguise'),
        'help' => __('Select the preferred flags for the Taiwanese languages', 'linguise'),
        'cn' => __('China', 'linguise'),
        'tw' => __('Taiwan', 'linguise'),
    ],
    'flag_type_es' => [
        'title' => __('Spanish flag type', 'linguise'),
        'help' => __('Select the preferred flags for the Spanish languages', 'linguise'),
        'es' => __('Spain', 'linguise'),
        'mx' => __('Mexico', 'linguise'),
        'pu' => __('Peru', 'linguise'),
    ],

    'appearance' => __('Appearance', 'linguise'),
    'flag_border' => [
        'title' => __('Flag border radius', 'linguise'),
        'help' => __('If you\'re using the rectangle flag shape you can apply a custom border radius in pixels', 'linguise'),
    ],
    'flag_size' => [
        'title' => __('Flag size', 'linguise'),
        'help' => __('Adjust flag size in pixels. That doesn\'t change the image weight as it\'s a .svg format', 'linguise'),
    ],
    'text_color' => [
        'title' => __('Text color', 'linguise'),
        'name_title' => __('Language name color', 'linguise'),
        'name_help' => __('Select the default text color for your language names', 'linguise'),
        'hover_title' => __('Language name hover color', 'linguise'),
        'hover_help' => __('Select the mouse hover text color for your language names', 'linguise'),
        // unused currently
        'inherit' => __('Inherit from parent', 'linguise'),
        'inherit_help' => __('Inherit the text color from the parent element rather than selecting here', 'linguise'),
    ],
    'popup' => __('Popup settings', 'linguise'),
    'popup_text_color' => [
        'title' => __('Popup text color', 'linguise'),
        'name_title' => __('Popup language color', 'linguise'),
        'name_help' => __('Select the default text color for your language names in popup box', 'linguise'),
        'hover_title' => __('Popup language hover color', 'linguise'),
        'hover_help' => __('Select the mouse hover text color for your language names in popup box', 'linguise'),
        // unused currently
        'inherit' => __('Inherit from parent', 'linguise'),
        'inherit_help' => __('Inherit the text color from the parent element rather than selecting here', 'linguise'),
    ],
    'flag_shadow' => [
        'title' => __('Flag box shadow', 'linguise'),
        'help' => __('Adjust the color and shadow size of the language flags', 'linguise'),
        'x' => __('X offset', 'linguise'),
        'y' => __('Y offset', 'linguise'),
        'blur' => __('Blur', 'linguise'),
        'spread' => __('Spread', 'linguise'),
        'color' => __('Shadow color', 'linguise'),
    ],
    'flag_hover_shadow' => [
        'title' => __('Flag box shadow on hover', 'linguise'),
        'help' => __('Adjust the color and shadow size when hovering the language flags', 'linguise'),
        'x' => __('X offset', 'linguise'),
        'y' => __('Y offset', 'linguise'),
        'blur' => __('Blur', 'linguise'),
        'spread' => __('Spread', 'linguise'),
        'color' => __('Shadow color', 'linguise'),
    ],
];

$language_display_mode = [
    [
        'value' => 'side_by_side',
        'label' => $translation_strings['language_display']['mode-sbs'],
    ],
    [
        'value' => 'dropdown',
        'label' => $translation_strings['language_display']['mode-dropdown'],
    ],
    [
        'value' => 'popup',
        'label' => $translation_strings['language_display']['mode-popup'],
    ],
];

$position_display_mode = [
    [
        'value' => 'no',
        'label' => $translation_strings['position']['pos-inplace'],
    ],
    [
        'value' => 'top_left',
        'label' => $translation_strings['position']['pos-topl'],
    ],
    [
        'value' => 'top_left_no_scroll',
        'label' => $translation_strings['position']['pos-topl-ns'],
    ],
    [
        'value' => 'top_right',
        'label' => $translation_strings['position']['pos-topr'],
    ],
    [
        'value' => 'top_right_no_scroll',
        'label' => $translation_strings['position']['pos-topr-ns'],
    ],
    [
        'value' => 'bottom_left',
        'label' => $translation_strings['position']['pos-botl'],
    ],
    [
        'value' => 'bottom_left_no_scroll',
        'label' => $translation_strings['position']['pos-botl-ns'],
    ],
    [
        'value' => 'bottom_right',
        'label' => $translation_strings['position']['pos-botr'],
    ],
    [
        'value' => 'bottom_right_no_scroll',
        'label' => $translation_strings['position']['pos-botr-ns'],
    ],
];

$language_names_display_mode = [
    [
        'value' => 'short',
        'label' => $translation_strings['language_names']['short'],
    ],
    [
        'value' => 'full',
        'label' => $translation_strings['language_names']['full'],
    ],
];

$language_lang_mode = [
    [
        'value' => 'en',
        'label' => $translation_strings['language_prefer']['en'],
    ],
    [
        'value' => 'native',
        'label' => $translation_strings['language_prefer']['native'],
    ],
];

$flag_style_mode = [
    [
        'value' => 'rounded',
        'label' => $translation_strings['flag_style']['round'],
    ],
    [
        'value' => 'rectangular',
        'label' => $translation_strings['flag_style']['rectangle'],
    ],
];

$flag_en_mode = [
    [
        'value' => 'en-us',
        'label' => $translation_strings['flag_type_en']['usa'],
    ],
    [
        'value' => 'en-gb',
        'label' => $translation_strings['flag_type_en']['gb'],
    ],
];
$flag_de_mode = [
    [
        'value' => 'de',
        'label' => $translation_strings['flag_type_de']['de'],
    ],
    [
        'value' => 'de-at',
        'label' => $translation_strings['flag_type_de']['at'],
    ],
];
$flag_pt_mode = [
    [
        'value' => 'pt',
        'label' => $translation_strings['flag_type_pt']['pt'],
    ],
    [
        'value' => 'pt-br',
        'label' => $translation_strings['flag_type_pt']['br'],
    ],
];
$flag_tw_mode = [
    [
        'value' => 'zh-tw',
        'label' => $translation_strings['flag_type_tw']['tw'],
    ],
    [
        'value' => 'zh-cn',
        'label' => $translation_strings['flag_type_tw']['cn'],
    ],
];
$flag_es_mode = [
    [
        'value' => 'es',
        'label' => $translation_strings['flag_type_es']['es'],
    ],
    [
        'value' => 'es-mx',
        'label' => $translation_strings['flag_type_es']['mx'],
    ],
    [
        'value' => 'es-pu',
        'label' => $translation_strings['flag_type_es']['pu'],
    ],
];

/**
 * Render or create HTML for a color toggles
 *
 * @param string $attr     The attribute name
 * @param string $current  The current color
 * @param string $title    The color title or what it is for (label)
 * @param string $help     The help tooltip messages
 * @param string $fallback The default color format
 *
 * @return string The rendered color toggle
 */
function renderColorToggle($attr, $current, $title, $help, $fallback = '#ffffff')
{
    $color = $current ? $current : $fallback;
    return (
        '<div class="m-0 text-base text-neutral phone-only-flex mb-2">'
            . esc_html($title) .
            '<span class="material-icons help-tooltip align-bottom ml-1" data-tippy="' . esc_attr($help) . '">help_outline</span>' .
        '</div>' .
        '<div class="flex flex-row gap-2 items-center">'.
            '<div class="flex flex-row gap-0 linguise-color-group">' .
                '<span class="color-block" data-colorama-target="' . esc_attr($attr) . '" style="background-color: ' . esc_attr($color) . '"></span>' .
                '<input type="text" pattern="^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$" value="' . esc_attr($color) . '" class="linguise-input rounder" name="linguise_options[' . esc_attr($attr) . ']" data-colorama="' . esc_attr($attr) . '" data-validate-target="' . esc_attr($attr) . '" />' .
            '</div>' .
            '<div class="m-0 text-base text-neutral large-only">'
                . esc_html($title) .
                '<span class="material-icons help-tooltip align-bottom ml-1" data-tippy="' . esc_attr($help) . '">help_outline</span>' .
            '</div>' .
        '</div>' .
        '<label ' .
            'data-validate-warn="' . esc_attr($attr) . '"' .
            'data-prefix="[' . esc_attr($title) . ']">' .
        '</label>'
    );
};

/**
 * Render or create HTML for a color toggles with alpha input
 *
 * @param string $attr     The attribute name
 * @param string $current  The current color
 * @param float  $alpha    The alpha number
 * @param string $title    The color title or what it is for (label)
 * @param string $help     The help tooltip messages
 * @param string $fallback The default color format
 *
 * @return string The rendered color toggle with alpha input
 */
function renderColorTranslucentToggle($attr, $current, $alpha, $title, $help, $fallback = '#ffffff')
{
    $color = $current ? $current : $fallback;
    $attr_alpha = $attr . '_alpha';
    return (
        '<div class="m-0 text-base text-neutral phone-only-flex mb-2">'
            . esc_html($title) .
            '<span class="material-icons help-tooltip align-bottom ml-1" data-tippy="' . esc_attr($help) . '">help_outline</span>' .
        '</div>' .
        '<div class="flex flex-row gap-2 items-center">'.
            '<div class="flex flex-row gap-0 linguise-color-group with-transparency">' .
                '<span class="color-block" data-colorama-target="' . esc_attr($attr) . '" style="background-color: ' . esc_attr($color) . '"></span>' .
                '<span class="color-block alpha-block" data-alpharama-target="' . esc_attr($attr_alpha) . '"></span>' .
                '<input type="text" pattern="^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$" value="' . esc_attr($color) . '" class="linguise-input rounder" name="linguise_options[' . esc_attr($attr) . ']" data-colorama="' . esc_attr($attr) . '" data-validate-target="' . esc_attr($attr) . '" />' .
                '<input type="number" min="0.0" max="1.0" step="0.1" value="' . esc_attr($alpha) . '" class="linguise-input rounder alpha-input" name="linguise_options[' . esc_attr($attr_alpha) . ']" data-alpharama="' . esc_attr($attr_alpha) . '" data-validate-target="' . esc_attr($attr_alpha) . '" />' .
            '</div>' .
            '<div class="m-0 text-base text-neutral large-only">'
                . esc_html($title) .
                '<span class="material-icons help-tooltip align-bottom ml-1" data-tippy="' . esc_attr($help) . '">help_outline</span>' .
            '</div>' .
        '</div>' .
        '<div class="flex flex-col">' .
            '<label ' .
                'data-validate-warn="' . esc_attr($attr) . '"' .
                'data-prefix="[' . esc_attr($title) . ']">' .
            '</label>' .
            '<label ' .
                'data-validate-warn="' . esc_attr($attr_alpha) . '"' .
                'data-prefix="[' . esc_attr($title) . ' alpha]">' .
            '</label>' .
        '</div>'
    );
};

$website_locale = get_locale();
$website_lang_name = '';
$linguise_default = isset($options['default_language']) ? $options['default_language'] : 'en';
$linguise_default_wp = \Linguise\WordPress\Helper::mapLanguageToWordPressLocale($linguise_default);
$linguise_lang_name = 'Unknown';
$linguise_supported = false;
foreach ($language_contents as $language_code => $language) {
    $current = $linguise_default === 'en' ? $language->name : $language->original_name;
    $lang_code_as_wp = \Linguise\WordPress\Helper::mapLanguageToWordPressLocale($language_code);
    if (\Linguise\WordPress\Helper::localeCompare($lang_code_as_wp, $website_locale)) {
        $website_lang_name = $current;
        $linguise_supported = true;
    }
    if ($language_code === $linguise_default) {
        $linguise_lang_name = $current;
    }
}

$compatibility_error_text = null;
if (!\Linguise\WordPress\Helper::localeCompare($linguise_default_wp, $website_locale)) {
    if ($linguise_supported) {
        $compatibility_error_text = sprintf(
            esc_html($translation_strings['default_language']['compat_support']),
            '<strong>' . esc_html($website_lang_name) . '</strong>',
            '<strong>' . esc_html($linguise_lang_name) . '</strong>'
        );
    } else {
        $compatibility_error_text = sprintf(
            esc_html($translation_strings['default_language']['compat_unsupported']),
            '<strong>' . esc_html(\Linguise\WordPress\Admin\Helper::getWPLangNativeName($website_locale)) . '</strong>'
        );
    }
    $compatibility_error_text .= ' ' . sprintf(
        esc_html($translation_strings['default_language']['compat_suggest']),
        '<a href="' . esc_url(get_admin_url(null, '/options-general.php#WPLANG')) . '" class="linguise-link">' . esc_html($translation_strings['default_language']['compat_suggest_wp']) . '</a>',
        '<a href="https://www.linguise.com/documentation/linguise-installation/install-linguise-on-wordpress/" class="linguise-link" target="_blank" rel="noopener noreferrer">' . esc_html($translation_strings['default_language']['compat_suggest_docs']) . '</a>'
    );
}
?>

<div class="tab-linguise-options">
    <!-- [BLOCK] API key -->
    <div class="linguise-options full-width">
        <h2 class="m-0 text-2xl font-bold text-black">
            <?php echo esc_html($translation_strings['token']['title']); ?>
            <span class="material-icons help-tooltip" data-tippy="<?php echo esc_attr($translation_strings['token']['help']); ?>" data-tippy-direction="right">
                help_outline
            </span>
        </h2>
        <div class="text-neutral mt-3">
            <?php echo esc_html($translation_strings['token']['description']); ?>
        </div>
        <div id="login-register-btn-area" class="flex flex-row gap-2 mt-3<?php echo $has_api_key ? ' hidden' : ''; ?>">
            <button type="button" class="linguise-btn rounder btn-sm" data-linguise-register-action="register" disabled>
                <?php echo esc_html($translation_strings['register']); ?>
            </button>
            <button type="button" class="linguise-btn rounder outlined btn-sm" data-linguise-register-action="login" disabled>
                <?php echo esc_html($translation_strings['login']); ?>
            </button>
        </div>
        <div class="flex flex-col halfish-width with-fill-small gap-2 mt-4">
            <div class="block-highlight flex flex-row justify-between">
                <div class="domain-part font-bold" style="user-select: all;"><?php echo esc_html(linguiseGetSite()); ?></div>
                <div class="copy-part" data-clipboard-text="<?php echo esc_attr(linguiseGetSite()); ?>" data-tippy="<?php echo esc_attr($translation_strings['clipboard']); ?>">
                    <span class="material-icons copy-button align-bottom">content_copy</span>
                </div>
            </div>
            <div class="flex flex-row gap-2 mt-2">
                <input type="text" class="linguise-input rounder" name="linguise_options[token]" value="<?php echo esc_attr($options['token']); ?>">
                <input type="submit" class="linguise-btn rounder btn-sm success" value="<?php echo esc_attr($translation_strings['apply']); ?>">
            </div>
        </div>
    </div>
    <!-- [BLOCK] Translation -->
    <div class="linguise-options full-width<?php echo $has_api_key ? '' : ' is-disabled'; ?>">
        <div class="disabled-warning-inset"></div>
        <div class="disabled-warning">
            <h2 class="disabled-warning-text">
                <?php echo esc_html($translation_strings_root['settings-hidden']['banner']); ?>
            </h2>
        </div>
        <h2 class="m-0 text-2xl font-bold text-black">
            <?php echo esc_html($translation_strings['translation']); ?>
        </h2>
        <div class="flex flex-col mt-4 linguise-inner-options">
            <div>
                <h3 class="m-0 text-base text-neutral-deep font-semibold">
                    <?php echo esc_html($translation_strings['default_language']['title']); ?>
                    <span
                        class="material-icons help-tooltip"
                        data-tippy="<?php echo esc_attr($translation_strings['default_language']['help']) ?>"
                    >
                        help_outline
                    </span>
                </h3>
                <select class="linguise-input rounder mt-2" name="linguise_options[default_language]">
                <?php foreach ($language_contents as $language_code => $language) : ?>
                    <option value="<?php echo esc_attr($language_code); ?>" <?php echo isset($options['default_language']) ? (selected($options['default_language'], $language_code, false)) : (''); ?>>
                        <?php echo esc_html($language->name); ?> (<?php echo esc_html($language_code); ?>)
                    </option>
                <?php endforeach; ?>
                </select>
            </div>
            <?php if (!empty($compatibility_error_text)) : ?>
                <div class="mt-4">
                    <?php echo \Linguise\WordPress\Admin\Helper::renderAdmonition($compatibility_error_text, 'error'); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-sanitized */ ?>
                </div>
            <?php endif; ?>
            <div class="mt-4">
                <h3 class="m-0 text-base text-neutral-deep">
                    <?php echo esc_html($translation_strings['translate_into']['title']); ?>
                    <span
                        class="material-icons help-tooltip"
                        data-tippy="<?php echo esc_attr($translation_strings['translate_into']['help']) ?>"
                    >
                        help_outline
                    </span>
                </h3>
                <div class="mw-full w-full">
                    <select id="ms-translate-into" class="chosen-select chosen-sortable mt-2 w-full mw-full" name="linguise_options[enabled_languages][]" multiple data-placeholder="<?php echo esc_attr($translation_strings['translate_into']['placeholder']); ?>">
                    <?php foreach ($language_contents as $language_code => $language) : ?>
                        <option value="<?php echo esc_attr($language_code); ?>" <?php echo isset($options['enabled_languages']) ? (selected(in_array($language_code, $options['enabled_languages']), true, false)) : (''); ?>>
                            <?php echo esc_html($language->name); ?> (<?php echo esc_html($language_code); ?>)
                        </option>
                    <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="enabled_languages_sortable">
                </div>
            </div>
        </div>
        <div class="flex flex-col linguise-inner-options mt-4">
            <div class="m-0 text-base text-neutral-deep">
                <input
                    id="add_flag_automatically"
                    type="checkbox"
                    name="linguise_options[add_flag_automatically]"
                    value="1"
                    class="!m-0 align-middle"
                    data-int-checkbox="add_flag_automatically"
                    <?php echo isset($options['add_flag_automatically']) ? (checked($options['add_flag_automatically'], 1)) : (''); ?> />
                <label for="add_flag_automatically" class="ml-1 font-semibold align-middle">
                    <?php echo esc_html($translation_strings['auto_switcher']['title']); ?>
                </label>
            </div>
            <div class="mt-1 text-neutral">
                <div>
                    <?php echo esc_html($translation_strings['auto_switcher']['description']); ?>
                </div>
                <div>
                    <?php echo sprintf(
                        esc_html($translation_strings['auto_switcher']['description_2']),
                        '<a href="#help" class="linguise-link">' . esc_html($translation_strings['auto_switcher']['target']) . '</a>'
                    ); ?>
                </div>
            </div>
        </div>
    </div>
    <!-- [BLOCK] Display -->
    <div class="linguise-options full-width<?php echo $has_api_key ? '' : ' is-disabled'; ?>">
        <div class="disabled-warning-inset"></div>
        <div class="disabled-warning">
            <h2 class="disabled-warning-text">
                <?php echo esc_html($translation_strings_root['settings-hidden']['banner']); ?>
            </h2>
        </div>
        <h2 class="m-0 text-2xl font-bold text-black">
            <?php echo esc_html($translation_strings['display']); ?>
        </h2>
        <div class="flex flex-col mt-4 linguise-inner-options">
            <div>
                <h3 class="m-0 text-base text-neutral-deep font-semibold">
                    <?php echo esc_html($translation_strings['language_display']['title']); ?>
                    <span
                        class="material-icons help-tooltip"
                        data-tippy="<?php echo esc_attr($translation_strings['language_display']['help']) ?>"
                    >
                        help_outline
                    </span>
                </h3>
                <div class="mt-4 flex flex-row gap-2 flex-wrap">
                    <?php foreach ($language_display_mode as $disp_mode) : ?>
                        <label class="linguise-radio rounder">
                            <input 
                                type="radio"
                                name="linguise_options[flag_display_type]"
                                value="<?php echo esc_attr($disp_mode['value']); ?>"
                                data-linguise-radio="flag_display_type"
                                <?php checked($options['flag_display_type'], $disp_mode['value']) ?> />
                            <span class="material-icons no-select">check</span>
                            <span class="text-label font-semibold"><?php echo esc_html($disp_mode['label']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="mt-2">
                <h3 class="m-0 text-base text-neutral">
                    <?php echo esc_html($translation_strings['position']['title']); ?>
                </h3>
                <select class="linguise-input rounder mt-2" name="linguise_options[display_position]">
                <?php foreach ($position_display_mode as $positron) : ?>
                    <option value="<?php echo esc_attr($positron['value']); ?>" <?php selected($options['display_position'], $positron['value']) ?> />
                        <?php echo esc_html($positron['label']); ?>
                    </option>
                <?php endforeach; ?>
                </select>
            </div>
            <div class="mt-4">
                <div>
                    <label class="linguise-slider-checkbox">
                        <input type="checkbox" class="slider-input" data-int-checkbox="enable_flag" name="linguise_options[enable_flag]" value="1" <?php echo isset($options['enable_flag']) ? (checked($options['enable_flag'], 1)) : (''); ?> />
                        <span class="slider"></span>
                        <span class="slider-label font-semibold"><?php echo esc_html($translation_strings['show_flag']); ?></span>
                    </label>
                </div>
                <div class="mt-2">
                    <label class="linguise-slider-checkbox">
                        <input type="checkbox" class="slider-input" data-int-checkbox="enable_language_name" name="linguise_options[enable_language_name]" value="1" <?php echo isset($options['enable_language_name']) ? (checked($options['enable_language_name'], 1)) : (''); ?> />
                        <span class="slider"></span>
                        <span class="slider-label font-semibold"><?php echo esc_html($translation_strings['language_names']['label']); ?></span>
                    </label>
                </div>
            </div>
            <div class="mt-4">
                <div class="flex flex-row gap-2 flex-wrap">
                    <?php foreach ($language_names_display_mode as $name_mode) : ?>
                        <label class="linguise-radio rounder">
                            <input 
                                type="radio"
                                name="linguise_options[enable_language_short_name]"
                                value="<?php echo esc_attr($name_mode['value']); ?>"
                                data-linguise-radio-int="enable_language_short_name"
                                data-linguise-radio-int-correct="short"
                                <?php echo isset($options['enable_language_short_name']) ? (checked($options['enable_language_short_name'], $name_mode['value'] === 'short' ? 1 : 0)) : (''); ?> />
                            <span class="material-icons no-select">check</span>
                            <span class="text-label font-semibold"><?php echo esc_html($name_mode['label']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="flex flex-phone-dir mt-4 gap-2 w-full">
            <div class="flex flex-col linguise-inner-options w-full">
                <h3 class="m-0 text-base text-neutral-deep font-semibold">
                    <?php echo esc_html($translation_strings['language_prefer']['label']); ?>
                    <span
                        class="material-icons help-tooltip"
                        data-tippy="<?php echo esc_attr($translation_strings['language_prefer']['help']) ?>"
                    >
                        help_outline
                    </span>
                </h3>
                <div class="flex flex-row gap-2 mt-2 flex-wrap">
                    <?php foreach ($language_lang_mode as $lang_mode) : ?>
                        <label class="linguise-radio rounder">
                            <input 
                                type="radio"
                                name="linguise_options[language_name_display]"
                                value="<?php echo esc_attr($lang_mode['value']); ?>"
                                data-linguise-radio="language_name_display"
                                <?php echo isset($options['language_name_display']) ? (checked($options['language_name_display'], $lang_mode['value'])) : (''); ?> />
                            <span class="material-icons no-select">check</span>
                            <span class="text-label font-semibold"><?php echo esc_html($lang_mode['label']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="flex flex-col linguise-inner-options w-full">
                <h3 class="m-0 text-base text-neutral-deep font-semibold">
                    <?php echo esc_html($translation_strings['flag_style']['label']); ?>
                    <span
                        class="material-icons help-tooltip"
                        data-tippy="<?php echo esc_attr($translation_strings['flag_style']['help']) ?>"
                    >
                        help_outline
                    </span>
                </h3>
                <div class="flex flex-row gap-2 mt-2 flex-wrap">
                    <?php foreach ($flag_style_mode as $flag_mode) : ?>
                        <label class="linguise-radio rounder">
                            <input 
                                type="radio"
                                name="linguise_options[flag_shape]"
                                value="<?php echo esc_attr($flag_mode['value']); ?>"
                                data-linguise-radio="flag_shape"
                                <?php echo isset($options['flag_shape']) ? (checked($options['flag_shape'], $flag_mode['value'])) : (''); ?> />
                            <span class="material-icons no-select">check</span>
                            <span class="text-label font-semibold"><?php echo esc_html($flag_mode['label']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <!-- [BLOCK] Flag display -->
    <div class="linguise-options full-widt<?php echo $has_api_key ? '' : ' is-disabled'; ?>">
        <div class="disabled-warning-inset"></div>
        <div class="disabled-warning">
            <h2 class="disabled-warning-text">
                <?php echo esc_html($translation_strings_root['settings-hidden']['banner']); ?>
            </h2>
        </div>
        <h2 class="m-0 text-2xl font-bold text-black">
            <?php echo esc_html($translation_strings['flag_type']); ?>
        </h2>
        <div class="flex flex-row w-full gap-2 mt-4 flex-auto-wrap">
            <div class="flex flex-col linguise-inner-options w-full">
                <h3 class="m-0 text-base text-neutral-deep font-semibold">
                    <?php echo esc_html($translation_strings['flag_type_en']['title']); ?>
                    <span
                        class="material-icons help-tooltip"
                        data-tippy="<?php echo esc_attr($translation_strings['flag_type_en']['help']) ?>"
                    >
                        help_outline
                    </span>
                </h3>
                <div class="flex flex-row gap-2 mt-3 flex-wrap">
                    <?php foreach ($flag_en_mode as $en_mode) : ?>
                        <label class="linguise-radio rounder">
                            <input 
                                type="radio"
                                name="linguise_options[flag_en_type]"
                                value="<?php echo esc_attr($en_mode['value']); ?>"
                                data-linguise-radio="flag_en_type"
                                <?php echo isset($options['flag_en_type']) ? (checked($options['flag_en_type'], $en_mode['value'])) : (''); ?> />
                            <span class="material-icons no-select">check</span>
                            <span class="text-label font-semibold"><?php echo esc_html($en_mode['label']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="flex flex-col linguise-inner-options w-full">
                <h3 class="m-0 text-base text-neutral-deep font-semibold">
                    <?php echo esc_html($translation_strings['flag_type_de']['title']); ?>
                    <span
                        class="material-icons help-tooltip"
                        data-tippy="<?php echo esc_attr($translation_strings['flag_type_de']['help']) ?>"
                    >
                        help_outline
                    </span>
                </h3>
                <div class="flex flex-row gap-2 mt-3 flex-wrap">
                    <?php foreach ($flag_de_mode as $de_mode) : ?>
                        <label class="linguise-radio rounder">
                            <input 
                                type="radio"
                                name="linguise_options[flag_de_type]"
                                value="<?php echo esc_attr($de_mode['value']); ?>"
                                data-linguise-radio="flag_de_type"
                                <?php echo isset($options['flag_de_type']) ? (checked($options['flag_de_type'], $de_mode['value'])) : (''); ?> />
                            <span class="material-icons no-select">check</span>
                            <span class="text-label font-semibold"><?php echo esc_html($de_mode['label']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="flex flex-row w-full gap-2 mt-2 flex-auto-wrap">
            <div class="flex flex-col linguise-inner-options w-full">
                <h3 class="m-0 text-base text-neutral-deep font-semibold">
                    <?php echo esc_html($translation_strings['flag_type_tw']['title']); ?>
                    <span
                        class="material-icons help-tooltip"
                        data-tippy="<?php echo esc_attr($translation_strings['flag_type_tw']['help']) ?>"
                    >
                        help_outline
                    </span>
                </h3>
                <div class="flex flex-row gap-2 mt-2 flex-wrap">
                    <?php foreach ($flag_tw_mode as $tw_mode) : ?>
                        <label class="linguise-radio rounder">
                            <input 
                                type="radio"
                                name="linguise_options[flag_tw_type]"
                                value="<?php echo esc_attr($tw_mode['value']); ?>"
                                data-linguise-radio="flag_tw_type"
                                <?php echo isset($options['flag_tw_type']) ? (checked($options['flag_tw_type'], $tw_mode['value'])) : (''); ?> />
                            <span class="material-icons no-select">check</span>
                            <span class="text-label font-semibold"><?php echo esc_html($tw_mode['label']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="flex flex-col linguise-inner-options w-full">
                <h3 class="m-0 text-base text-neutral-deep font-semibold">
                    <?php echo esc_html($translation_strings['flag_type_pt']['title']); ?>
                    <span
                        class="material-icons help-tooltip"
                        data-tippy="<?php echo esc_attr($translation_strings['flag_type_pt']['help']) ?>"
                    >
                        help_outline
                    </span>
                </h3>
                <div class="flex flex-row gap-2 mt-2 flex-wrap">
                    <?php foreach ($flag_pt_mode as $pt_mode) : ?>
                        <label class="linguise-radio rounder">
                            <input 
                                type="radio"
                                name="linguise_options[flag_pt_type]"
                                value="<?php echo esc_attr($pt_mode['value']); ?>"
                                data-linguise-radio="flag_pt_type"
                                <?php echo isset($options['flag_pt_type']) ? (checked($options['flag_pt_type'], $pt_mode['value'])) : (''); ?> />
                            <span class="material-icons no-select">check</span>
                            <span class="text-label font-semibold"><?php echo esc_html($pt_mode['label']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="flex flex-col linguise-inner-options mt-2 w-full">
            <h3 class="m-0 text-base text-neutral-deep font-semibold">
                <?php echo esc_html($translation_strings['flag_type_es']['title']); ?>
                <span
                    class="material-icons help-tooltip"
                    data-tippy="<?php echo esc_attr($translation_strings['flag_type_es']['help']) ?>"
                >
                    help_outline
                </span>
            </h3>
            <div class="flex flex-row mt-2 gap-2 flex-wrap">
                <?php foreach ($flag_es_mode as $es_mode) : ?>
                    <label class="linguise-radio rounder">
                        <input 
                            type="radio"
                            name="linguise_options[flag_es_type]"
                            value="<?php echo esc_attr($es_mode['value']); ?>"
                            data-linguise-radio="flag_es_type"
                            <?php echo isset($options['flag_es_type']) ? (checked($options['flag_es_type'], $es_mode['value'])) : (''); ?> />
                        <span class="material-icons no-select">check</span>
                        <span class="text-label font-semibold"><?php echo esc_html($es_mode['label']); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <!-- [BLOCK] Appearance -->
    <div class="linguise-options full-width<?php echo $has_api_key ? '' : ' is-disabled'; ?>">
        <div class="disabled-warning-inset"></div>
        <div class="disabled-warning">
            <h2 class="disabled-warning-text">
                <?php echo esc_html($translation_strings_root['settings-hidden']['banner']); ?>
            </h2>
        </div>
        <h2 class="m-0 text-2xl font-bold text-black">
            <?php echo esc_html($translation_strings['appearance']); ?>
        </h2>
        <div class="flex flex-col mt-4 linguise-inner-options">
            <div class="flex flex-row gap-2">
                <div>
                    <h3 class="m-0 text-base text-neutral-deep font-semibold">
                        <?php echo esc_html($translation_strings['flag_border']['title']); ?>
                        <small class="text-muted">(px)</small>
                        <span
                            class="material-icons help-tooltip"
                            data-tippy="<?php echo esc_attr($translation_strings['flag_border']['help']) ?>"
                        >
                            help_outline
                        </span>
                    </h3>
                    <input
                        type="number"
                        class="linguise-input rounder mt-2"
                        name="linguise_options[flag_border_radius]"
                        value="<?php echo esc_attr((int)$options['flag_border_radius']); ?>"
                        min="0" step="1"
                        data-linguise-int="flag_border_radius"
                        data-validate-target="flag-border-radius">
                </div>
                <div>
                    <h3 class="m-0 text-base text-neutral-deep font-semibold">
                        <?php echo esc_html($translation_strings['flag_size']['title']); ?>
                        <small class="text-muted">(px)</small>
                        <span
                            class="material-icons help-tooltip"
                            data-tippy="<?php echo esc_attr($translation_strings['flag_size']['help']) ?>"
                        >
                            help_outline
                        </span>
                    </h3>
                    <input
                        type="number"
                        class="linguise-input rounder mt-2"
                        name="linguise_options[flag_width]"
                        value="<?php echo esc_attr((int)$options['flag_width']); ?>"
                        min="0"
                        step="1"
                        data-linguise-int="flag_width"
                        data-validate-target="flag-width">
                </div>
            </div>
            <label
                data-validate-warn="flag-border-radius"
                data-prefix="[<?php echo esc_attr($translation_strings['flag_border']['title']); ?>]">
            </label>
            <label
                data-validate-warn="flag-width"
                data-prefix="[<?php echo esc_attr($translation_strings['flag_size']['title']); ?>]">
            </label>
            <hr class="w-full mt-4 mb-3" />
            <div class="flex flex-col gap-2">
                <h3 class="m-0 text-base text-neutral-deep font-semibold">
                    <?php echo esc_html($translation_strings['text_color']['title']); ?>
                </h3>
                <?php echo renderColorToggle('language_name_color', $options['language_name_color'], $translation_strings['text_color']['name_title'], $translation_strings['text_color']['name_help']); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-sanitized */ ?>
                <?php echo renderColorToggle('language_name_hover_color', $options['language_name_hover_color'], $translation_strings['text_color']['hover_title'], $translation_strings['text_color']['hover_help']); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-sanitized */ ?>
            </div>
            <div class="flex flex-col gap-2 mt-4">
                <h3 class="m-0 text-base text-neutral-deep font-semibold">
                    <?php echo esc_html($translation_strings['popup_text_color']['title']); ?>
                </h3>
                <?php echo renderColorToggle('popup_language_name_color', $options['popup_language_name_color'], $translation_strings['popup_text_color']['name_title'], $translation_strings['popup_text_color']['name_help']); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-sanitized */ ?>
                <?php echo renderColorToggle('popup_language_name_hover_color', $options['popup_language_name_hover_color'], $translation_strings['popup_text_color']['hover_title'], $translation_strings['popup_text_color']['hover_help']); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-sanitized */ ?>
            </div>
        </div>
        <div class="flex flex-col mt-4 linguise-inner-options">
            <!-- TARGET: FLAG_SHADOW -->
            <h3 class="m-0 text-base text-neutral-deep font-semibold">
                <?php echo esc_html($translation_strings['flag_shadow']['title']); ?>
                <span
                    class="material-icons help-tooltip"
                    data-tippy="<?php echo esc_attr($translation_strings['flag_shadow']['help']) ?>"
                >
                    help_outline
                </span>
            </h3>
            <div class="flex flex-row flex-wrap gap-3 mt-2">
                <div class="flag-shadow-wrapper">
                    <h3 class="m-0 text-base text-neutral">
                        <?php echo esc_html($translation_strings['flag_shadow']['x']); ?>
                        <small class="text-muted">(px)</small>
                    </h3>
                    <input type="number" class="linguise-input rounder mt-2" name="linguise_options[flag_shadow_h]" value="<?php echo esc_attr((int)$options['flag_shadow_h']); ?>" min="-50" max="50" step="1" data-linguise-int="flag_shadow_h" data-validate-target="flag_shadow_h">
                </div>
                <div class="flag-shadow-wrapper">
                    <h3 class="m-0 text-base text-neutral">
                        <?php echo esc_html($translation_strings['flag_shadow']['y']); ?>
                        <small class="text-muted">(px)</small>
                    </h3>
                    <input type="number" class="linguise-input rounder mt-2" name="linguise_options[flag_shadow_v]" value="<?php echo esc_attr((int)$options['flag_shadow_v']); ?>" min="-50" max="50" step="1" data-linguise-int="flag_shadow_v" data-validate-target="flag_shadow_v">
                </div>
                <div class="flag-shadow-wrapper">
                    <h3 class="m-0 text-base text-neutral">
                        <?php echo esc_html($translation_strings['flag_shadow']['blur']); ?>
                        <small class="text-muted">(px)</small>
                    </h3>
                    <input type="number" class="linguise-input rounder mt-2" name="linguise_options[flag_shadow_blur]" value="<?php echo esc_attr((int)$options['flag_shadow_blur']); ?>" min="0" max="50" step="1" data-linguise-int="flag_shadow_blur" data-validate-target="flag_shadow_blur">
                </div>
                <div class="flag-shadow-wrapper">
                    <h3 class="m-0 text-base text-neutral">
                        <?php echo esc_html($translation_strings['flag_shadow']['spread']); ?>
                        <small class="text-muted">(px)</small>
                    </h3>
                    <input type="number" class="linguise-input rounder mt-2" name="linguise_options[flag_shadow_spread]" value="<?php echo esc_attr((int)$options['flag_shadow_spread']); ?>" min="0" max="50" step="1" data-linguise-int="flag_shadow_spread" data-validate-target="flag_shadow_spread">
                </div>
            </div>
            <div class="flex flex-col">
                <label
                    data-validate-warn="flag_shadow_h"
                    data-prefix="[<?php echo esc_attr($translation_strings['flag_shadow']['x']); ?>]"></label>
                <label
                    data-validate-warn="flag_shadow_v"
                    data-prefix="[<?php echo esc_attr($translation_strings['flag_shadow']['y']); ?>]"></label>
                <label
                    data-validate-warn="flag_shadow_blur"
                    data-prefix="[<?php echo esc_attr($translation_strings['flag_shadow']['blur']); ?>]"></label>
                <label
                    data-validate-warn="flag_shadow_spread"
                    data-prefix="[<?php echo esc_attr($translation_strings['flag_shadow']['spread']); ?>]"></label>
            </div>
            <div class="mt-4">
                <?php echo renderColorTranslucentToggle('flag_shadow_color', $options['flag_shadow_color'], $options['flag_shadow_color_alpha'], $translation_strings['flag_shadow']['color'], $translation_strings['flag_shadow']['help']); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-sanitized */ ?>
            </div>
            <hr class="w-full mt-4 mb-3">
            <!-- TARGET: FLAG_SHADOW_HOVER -->
            <h3 class="m-0 text-base text-neutral-deep font-semibold">
                <?php echo esc_html($translation_strings['flag_hover_shadow']['title']); ?>
                <span
                    class="material-icons help-tooltip"
                    data-tippy="<?php echo esc_attr($translation_strings['flag_hover_shadow']['help']) ?>"
                >
                    help_outline
                </span>
            </h3>
            <div class="flex flex-row flex-wrap gap-3 mt-2">
                <div class="flag-shadow-wrapper">
                    <h3 class="m-0 text-base text-neutral">
                        <?php echo esc_html($translation_strings['flag_hover_shadow']['x']); ?>
                        <small class="text-muted">(px)</small>
                    </h3>
                    <input type="number" class="linguise-input rounder mt-2" name="linguise_options[flag_hover_shadow_h]" value="<?php echo esc_attr((int)$options['flag_hover_shadow_h']); ?>" min="-50" max="50" step="1" data-linguise-int="flag_hover_shadow_h" data-validate-target="flag_hover_shadow_h">
                </div>
                <div class="flag-shadow-wrapper">
                    <h3 class="m-0 text-base text-neutral">
                        <?php echo esc_html($translation_strings['flag_hover_shadow']['y']); ?>
                        <small class="text-muted">(px)</small>
                    </h3>
                    <input type="number" class="linguise-input rounder mt-2" name="linguise_options[flag_hover_shadow_v]" value="<?php echo esc_attr((int)$options['flag_hover_shadow_v']); ?>" min="-50" max="50" step="1" data-linguise-int="flag_hover_shadow_v" data-validate-target="flag_hover_shadow_v">
                </div>
                <div class="flag-shadow-wrapper">
                    <h3 class="m-0 text-base text-neutral">
                        <?php echo esc_html($translation_strings['flag_hover_shadow']['blur']); ?>
                        <small class="text-muted">(px)</small>
                    </h3>
                    <input type="number" class="linguise-input rounder mt-2" name="linguise_options[flag_hover_shadow_blur]" value="<?php echo esc_attr((int)$options['flag_hover_shadow_blur']); ?>" min="0" max="50" step="1" data-linguise-int="flag_hover_shadow_blur" data-validate-target="flag_hover_shadow_blur">
                </div>
                <div class="flag-shadow-wrapper">
                    <h3 class="m-0 text-base text-neutral">
                        <?php echo esc_html($translation_strings['flag_hover_shadow']['spread']); ?>
                        <small class="text-muted">(px)</small>
                    </h3>
                    <input type="number" class="linguise-input rounder mt-2" name="linguise_options[flag_hover_shadow_spread]" value="<?php echo esc_attr((int)$options['flag_hover_shadow_spread']); ?>" min="0" max="50" step="1" data-linguise-int="flag_hover_shadow_spread" data-validate-target="flag_hover_shadow_spread">
                </div>
            </div>
            <div class="flex flex-col">
                <label
                    data-validate-warn="flag_hover_shadow_h"
                    data-prefix="[<?php echo esc_attr($translation_strings['flag_shadow']['x']); ?>]"></label>
                <label
                    data-validate-warn="flag_hover_shadow_v"
                    data-prefix="[<?php echo esc_attr($translation_strings['flag_shadow']['y']); ?>]"></label>
                <label
                    data-validate-warn="flag_hover_shadow_blur"
                    data-prefix="[<?php echo esc_attr($translation_strings['flag_shadow']['blur']); ?>]"></label>
                <label
                    data-validate-warn="flag_hover_shadow_spread"
                    data-prefix="[<?php echo esc_attr($translation_strings['flag_shadow']['spread']); ?>]"></label>
            </div>
            <div class="mt-4">
                <?php echo renderColorTranslucentToggle('flag_hover_shadow_color', $options['flag_hover_shadow_color'], $options['flag_hover_shadow_color_alpha'], $translation_strings['flag_hover_shadow']['color'], $translation_strings['flag_hover_shadow']['help']); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-sanitized */ ?>
            </div>
        </div>
    </div>
</div>
