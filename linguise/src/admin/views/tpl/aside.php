<?php
defined('ABSPATH') || die('');
$options = linguiseGetOptions();
$language_name_display = isset($options['language_name_display']) ? $options['language_name_display'] : 'en';
// Get from module parameters the enable languages
$languages_enabled_param = isset($options['enabled_languages']) ? $options['enabled_languages'] : array();
// Generate language list with default language as first item
if ($language_name_display === 'en') {
    $language_list = array($options['default_language'] => $languages_names[$options['default_language']]['name']);
} else {
    $language_list = array($options['default_language'] => $languages_names[$options['default_language']]['original_name']);
}

foreach ($languages_enabled_param as $language) {
    if ($language === $options['default_language']) {
        continue;
    }

    if (!isset($languages_names[$language])) {
        continue;
    }

    if ($language_name_display === 'en') {
        $language_list[$language] = $languages_names[$language]['name'];
    } else {
        $language_list[$language] = $languages_names[$language]['original_name'];
    }
}

$config = array_merge(array(
    'all_languages' => $languages_names,
    'languages' => $language_list,
), $options);
?>
<div class="content aside">
    <!--Start of Tawk.to Script-->
    <script type="text/javascript">
      var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
      (function(){
        var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
        s1.async=true;
        s1.src='https://embed.tawk.to/6107b589649e0a0a5ccf1114/1fur1fhvd';
        s1.charset='UTF-8';
        s1.setAttribute('crossorigin','*');
        s0.parentNode.insertBefore(s1,s0);
      })();
    </script>
    <!--End of Tawk.to Script-->
    <ul class="aside-list-wrapper">
        <li class="linguise-settings-option">
            <a id="linguise-chat-toggle" href="javascript:void(Tawk_API.toggle())">Need help ? Chat with us.<img class="slidecaption" src="<?php echo esc_url(LINGUISE_PLUGIN_URL . '/assets/images/chat.svg') ; ?>"/></a>
        </li>
        <li class="linguise-settings-option">
            <label class="linguise-setting-label label-bolder aside-label"
                   style="padding-left: 0"><?php esc_html_e('Edit your translations', 'linguise'); ?></label>
            <div class="items-blocks edit-translation-link">
                <ul>
                    <li><a href="https://www.linguise.com/documentation" target="_blank"
                           class="linguise-button aside-button waves-effect waves-light small-radius"><?php esc_html_e('Documentation', 'linguise'); ?></a>
                    </li>
                    <li><a href="https://dashboard.linguise.com" target="_blank"
                           class="linguise-button aside-button waves-effect waves-light small-radius"><?php esc_html_e('Linguise dashboard', 'linguise'); ?></a>
                    </li>
                </ul>
            </div>
        </li>
        <li class="linguise-settings-option">
            <label class="linguise-setting-label label-bolder aside-label"
                   style="padding-left: 0"><?php esc_html_e('Language List Preview', 'linguise'); ?></label>
            <div class="items-blocks linguise_preview"
                 data-config="<?php echo esc_attr(htmlspecialchars(json_encode($config), ENT_QUOTES, 'UTF-8')) ?>">

            </div>
        </li>
    </ul>
</div>
