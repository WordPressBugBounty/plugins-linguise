<?php
defined('ABSPATH') || die('');

$configuration = linguiseGetConfiguration();
$options = linguiseGetOptions();

// only do string/number/boolean attributes
$validConfiguration = [];
// This is keys that can be set from the main Dashboard.
$disallowed_keys = ['token', 'cache_enabled', 'cache_max_size', 'debug'];
foreach ($configuration as $key => $data) {
    if (in_array($key, $disallowed_keys)) {
        continue;
    }

    // skip key starting with _
    if (strpos($key, '_') === 0) {
        continue;
    }

    if ($data['value'] === null) {
        // assume it's a string
        $data['value'] = '';
    }

    if (is_string($data['value']) || is_numeric($data['value']) || is_bool($data['value'])) {
        $validConfiguration[$key] = $data;
    }
}

$expert_mode = isset($options['expert_mode']) ? $options['expert_mode'] : [];
$api_host = isset($expert_mode['api_host']) ? $expert_mode['api_host'] : 'api.linguise.com';
$api_port = isset($expert_mode['api_port']) ? $expert_mode['api_port'] : '443';

$validConfiguration['api_host'] = [
    'value' => $api_host,
    'doc' => 'The host of the Linguise API server. Default is api.linguise.com',
    'key' => 'api_host'
];
$validConfiguration['api_port'] = [
    'value' => $api_port,
    'doc' => 'The port of the Linguise API server. Default is 443',
    'key' => 'api_port'
];

/**
 * Convert key to "word"-like
 *
 * @param string $key Key to convert
 *
 * @return string Converted key
 */
function keyToWord($key)
{
    return ucwords(str_replace('_', ' ', $key));
}

?>

<div class="linguise-main-wrapper" style="visibility: hidden">
    <div class="linguise-left-panel-toggle">
        <i class="dashicons dashicons-leftright linguise-left-panel-toggle-icon"></i>
    </div>
    <div class="linguise-left-panel linguise-full-panel">
        <div class="linguise-top-tabs-wrapper">
            <div class="mdc-tab-bar" role="tablist">
                <div class="mdc-tab-scroller">
                    <div class="mdc-tab-scroller__scroll-area">
                        <div class="mdc-tab-scroller__scroll-content">
                            <button class="mdc-tab mdc-tab--active"
                                    role="tab"
                                    aria-selected="false"
                                    data-index="1"
                                    id="expert_mode">
                                    <span class="mdc-tab__content">
                                        <span class="mdc-tab__text-label">
                                            <i class="material-icons mi wpsol-icon-menu menu-tab-icon">code</i>
                                            Expert Mode
                                        </span>
                                    </span>
                                <span class="mdc-tab-indicator mdc-tab-indicator--active">
                                    <span class="mdc-tab-indicator__content mdc-tab-indicator__content--underline"></span>
                                </span>
                                <span class="mdc-tab__ripple mdc-ripple-upgraded"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <form action="" method="post">
            <div class="linguise-content-wrapper">
                <div class="linguise-tab-content linguise-content-active" id="expert_mode">
                    <div class="content">
                        <ul>
                            <?php foreach ($validConfiguration as $key => $data) { ?>
                                <li class="linguise-settings-option full-width">
                                    <label for="<?php echo esc_attr($key); ?>"
                                           class="linguise-setting-label label-bolder linguise-tippy"
                                           <?php if ($data['doc'] !== null) { ?>
                                           data-tippy="<?php echo esc_attr($data['doc']); ?>"
                                           <?php } ?>>
                                           <?php echo esc_html(keyToWord($key)); ?>
                                           <?php if ($data['doc'] !== null) { ?>
                                           <span class="material-icons">help_outline</span>
                                           <?php } ?>
                                    </label>
                                    <div style="padding: 10px;">
                                    <!-- Input type depends on the type of the value -->
                                    <?php if (is_bool($data['value'])) { ?>
                                        <input type="checkbox"
                                               name="expert_linguise[<?php echo esc_attr($key); ?>]"
                                               id="<?php echo esc_attr($key); ?>"
                                               value="<?php echo $data['value'] ? '1' : '0'; ?>"
                                               class="linguise-checkbox custom-checkbox"
                                               style="margin-left: 0.5rem; margin-right: 0.5rem;"
                                               <?php checked($data['value'], true); ?>>
                                    <?php } else { ?>
                                        <input type="text"
                                               name="expert_linguise[<?php echo esc_attr($key); ?>]"
                                               id="<?php echo esc_attr($key); ?>"
                                               class="linguise-input custom-input"
                                               style="margin-left: 0.5rem; margin-right: 0.5rem;"
                                               value="<?php echo esc_attr($data['value']); ?>">
                                    <?php } ?>
                                    </div>
                                </li>
                            <?php } ?>
                        </ul>
                    </div>
                    <p class="submit" style="margin-top: 10px; margin-right: 10px;display: inline-block; float: right; width: 100%;">
                        <input type="submit"
                               name="linguise_submit"
                               id="submit"
                               class="button button-primary"
                               value="<?php esc_html_e('Save Settings', 'linguise'); ?>">
                    </p>
                </div>
            </div>
        </form>
    </div>
</div>

<script type="text/javascript">
    (function () {
        function checkboxValueInterceptor(event) {
            const checkbox = event.target;
            if (checkbox.checked) {
                checkbox.value = "1";
            } else {
                checkbox.value = "0";
            }
        }

        function init() {
            const allElements = document.querySelectorAll('.linguise-checkbox');
            allElements.forEach((element) => {
                element.addEventListener('change', checkboxValueInterceptor);
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    })();
</script>
