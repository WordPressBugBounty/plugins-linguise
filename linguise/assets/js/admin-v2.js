import $ from 'jquery';

import '../css/admin-main-v2.scss';
import 'material-design-icons/iconfont/material-icons.css';

import CodeMirror from 'codemirror/lib/codemirror.js';
import 'codemirror/addon/hint/show-hint.js';
import 'codemirror/addon/hint/show-hint.css';
import 'codemirror/addon/hint/css-hint.js';
import 'codemirror/lib/codemirror.css';

import tippy from 'tippy.js';
import 'tippy.js/dist/tippy.css';

import './vendor/chosen.jquery.js';
import '../css/vendor/chosen.scss';
import './vendor/jquery-chosen-sortable.min.js';
import registerIframe from './register-iframe';
import formValidator from './form-validator';

import checkerBadgeUrl from '../images/transparent-checker.png';

import { LinguiseSwitcher } from 'script-js';

const sourceScript = document.currentScript;
const scriptUrl = new URL(sourceScript.src);
const checkerImageLocation = new URL(checkerBadgeUrl, scriptUrl);

/**
 * Hide the notification after a delay
 * @param {HTMLElement} [elm] the element to hide
 */
function linguiseDelayHideNotification(elm) {
    if (elm) {
        setTimeout(function () {
            elm.fadeOut(2000);
            setTimeout(() => {
                elm.remove();
            }, 2100);
        }, 3000);
    } else {
        const $elements = $('.linguise-notification-popup');
        if ($elements.length) {
            setTimeout(() => {
                $elements.fadeOut(2000);
                setTimeout(() => {
                    $elements.remove();
                }, 2100);
            }, 3000);
        }
    }
}

jQuery(document).ready(($) => {
    const globalState = {
        isSaving: false,
        isNoAPIKey: true,
        storedToken: null,
        activeTab: null,
        /** @type {string[]} */
        tabs: [],
        /** @type {string[]} */
        tabsHideSave: [],
        /** @type {HTMLInputElement[]} */
        coloramaSupported: [],
    };

    if (!window.linguise_configs) {
        // No linguise configs found, return early
        console.warn('No linguise configs found, exiting early.');
        return;
    }

    const globalConfig = window.linguise_configs.vars.configs;
    globalConfig.current_language = globalConfig.default_language;


    const customCssEditor = document.querySelector('[name="linguise_options[custom_css]"]');
    // Codemirror
    let cssEditor;
    if (customCssEditor) {
        cssEditor = CodeMirror.fromTextArea(customCssEditor, {
            theme: 'linguise-day',
            lineNumbers: true,
            lineWrapping : true,
            autoRefresh: true,
            styleActiveLine: true,
            fixedGutter: true,
            coverGutterNextToScrollbar: false,
            gutters: ['CodeMirror-lint-markers'],
            extraKeys: {"Ctrl-Space": "autocomplete"},
            mode: 'css',
        });
    }

    function updateConfigDemoScript(newConfig) {
        const previewContent = document.getElementById('dashboard-live-preview');
        previewContent.innerHTML = '';
    
        const existingScript = document.querySelector('script#config-script');
        existingScript.textContent = `var linguise_configs = {vars: {configs: ${JSON.stringify(newConfig)}}}`;

        const instance = new LinguiseSwitcher();
        instance.demo_mode = true;
        instance.initialize();
    }

    // Inject custom CSS
    const styleSheet = document.createElement('style');
    styleSheet.setAttribute('data-linguise-custom-css', '1');
    document.head.appendChild(styleSheet);
    if (globalConfig.custom_css) {
        styleSheet.innerHTML = globalConfig.custom_css;
    }
    cssEditor && cssEditor.on('blur', (instance, ev) => {
        const value = instance.getValue();
        if (customCssEditor) {
            customCssEditor.value = value;
        }
        globalConfig.custom_css = value;
        styleSheet.innerHTML = value;
    });

    // Get all the tabs
    const tabs = $('.nav-tabs[data-toggle="tab"]');
    const $saveButton = $('.save-settings-btn');
    tabs.each((_, tab) => {
        const { target, saveHide } = tab.dataset;
        globalState.tabs.push(target);
        if (tab.classList.contains('active') && !globalState.activeTab) {
            globalState.activeTab = target;
            const $target = document.querySelector(`#${target}`);
            if ($target) {
                $target.classList.add('active');
                $target.setAttribute('data-linguise-fieldset', target);
            }
        } else if (tab.classList.contains('active') && globalState.activeTab && globalState.activeTab !== target) {
            // Remove active class from other tabs
            tab.classList.remove('active');
            const $target = document.querySelector(`#${target}`);
            if ($target) {
                $target.classList.remove('active');
                $target.setAttribute('data-linguise-fieldset', target);
            }
        }

        if (saveHide && saveHide === '1') {
            globalState.tabsHideSave.push(target);
        }
    });

    function toggleTab(targetTab) {
        // Toggle tabs
        tabs.each((index, tab) => {
            const tabTarget = $(tab).data('target');
            const $target = document.querySelector(`[data-id="${tabTarget}"]`);
            if (targetTab === tabTarget) {
                tab.classList.add('active');
                $target?.classList.add('active');
                globalState.activeTab = targetTab;
            } else {
                tab.classList.remove('active');
                $target?.classList.remove('active');
            }

            if (globalState.tabsHideSave.includes(targetTab)) {
                $saveButton.hide();
            } else {
                $saveButton.show();
            }
        });
    }

    // Listen for hash change
    window.addEventListener('hashchange', (ev) => {
        const hash = window.location.hash.replace('#', '');
        // Check if hash exists in the tabs
        const tab = globalState.tabs.find((tab) => tab === hash);
        if (tab && tab !== globalState.activeTab) {
            // Toggle the tab
            toggleTab(tab);
            if (tab === 'advanced') {
                setTimeout(() => {
                    // we refresh editor
                    cssEditor?.refresh();
                }, 1);
            }
        }
    });

    tabs.on('click', (ev) => {
        ev.stopPropagation();
        ev.preventDefault();

        // Get dataset
        const { target } = ev.currentTarget.dataset;

        // Modify hash instead
        window.location.hash = target;
    });

    // Onload, check current hash
    const hash = window.location.hash.replace('#', '');
    if (hash && globalState.tabs.includes(hash)) {
        // Toggle the tab
        toggleTab(hash);
        if (hash === 'advanced') {
            setTimeout(() => {
                // we refresh editor
                cssEditor?.refresh();
            }, 1);
        }
    }

    const hasToken = Boolean(globalConfig.token?.trim());
    if (!hasToken) {
        // Check if we have set a hash, if not we ignore it.
        if (hash) {
            // If we have hash, we change it to the first tab
            window.location.hash = globalState.tabs[0];
        }
    }

    $('.chat-with-us').on('click', (ev) => {
        window.Tawk_API.toggle();
    });

    $('.chosen-select').chosen().chosenSortable();

    /**
     * Get language name from the code
     * @param {string} code the language code
     * @returns {string}
     */
    function getLanguageName(code) {
        const name = globalConfig.all_languages[code]?.name;
        const originalName = globalConfig.all_languages[code]?.original_name;
        const displayedName = globalConfig.language_name_display === 'en' ? name : originalName;
        return displayedName || code;
    }

    const languageCodeRegex = /\(([\w-]{2,5})\)$/;

    $('#ms-translate-into').on('chosen_sortabled', (ev) => {
        const languages = {};
        const sortedLists = [];
        languages[globalConfig.current_language] = getLanguageName(globalConfig.current_language);

        $('#ms_translate_into_chosen .search-choice').each((_, elem) => {
            const names = $(elem).find('span').text().trim();
            // strip out the language code
            // names formatted like this: "English (en)"
            const code = names.match(languageCodeRegex);
            languages[code[1]] = getLanguageName(code[1]);
            sortedLists.push(code[1]);
        });

        globalConfig.enabled_languages = sortedLists;
        globalConfig.languages = languages;
        $('[name="enabled_languages_sortable"]').val(sortedLists.join()).trigger('change');
        updateConfigDemoScript(globalConfig);
    });

    function rerenderLanguagesList() {
        const original = document.querySelector('select[name="linguise_options[default_language]"]');
        globalConfig.default_language = original.value;
        globalConfig.current_language = original.value;

        const languages = {};
        languages[globalConfig.current_language] = getLanguageName(globalConfig.current_language);
        /** @type {string[]} */
        const enabledLanguages = [];
        const translateInto = document.querySelector('#ms-translate-into');
        const selectedLanguages = translateInto.querySelectorAll('option');
        selectedLanguages.forEach((option) => {
            if (!option.selected) {
                return;
            }
            if (option.value === globalConfig.current_language) {
                option.setAttribute('disabled', 'disabled');
            } else {
                option.removeAttribute('disabled');
            }
            enabledLanguages.push(option.value);
        });

        globalConfig.enabled_languages = enabledLanguages.filter((lang) => lang !== globalConfig.current_language);
        // remove languages that is default language
        selectedLanguages.forEach((option) => {
            if (option.value === globalConfig.current_language && option.selected) {
                option.selected = false;
                // trigger for removal
                $(translateInto).trigger('chosen:updated');
            }
        });

        enabledLanguages.forEach((lang) => {
            languages[lang] = getLanguageName(lang);
        });
        globalConfig.languages = languages;
    }

    $('select[name="linguise_options[default_language]"]').on('change', (ev) => {
        rerenderLanguagesList();
        updateConfigDemoScript(globalConfig);
    });
    $('#ms-translate-into').on('change', (ev) => {
        rerenderLanguagesList();
        updateConfigDemoScript(globalConfig);
    });

    /**
     * A list of radio inputs that need to be rerendered
     * @type {string[]}
     */
    const radioInputRerenderLanguages = [
        'language_name_display',
    ];

    // Radio buttons
    $("[data-linguise-radio]").on('change', (ev) => {
        const targetName = ev.currentTarget.dataset.linguiseRadio;
        if (targetName) {
            globalConfig[targetName] = ev.currentTarget.value;
            if (radioInputRerenderLanguages.includes(targetName)) {
                rerenderLanguagesList();
            }
            updateConfigDemoScript(globalConfig);
        }
    });
    // Radio buttons with number casting
    $("[data-linguise-radio-int]").on('change', (ev) => {
        const targetName = ev.currentTarget.dataset.linguiseRadioInt;
        const correctValue = ev.currentTarget.dataset.linguiseRadioIntCorrect;
        if (targetName && correctValue) {
            globalConfig[targetName] = ev.currentTarget.value === correctValue ? 1 : 0;
            updateConfigDemoScript(globalConfig);
        }
    });
    // Input checkbox with number
    $("[data-int-checkbox]").on('change', (ev) => {
        const targetName = ev.currentTarget.dataset.intCheckbox;
        const checked = ev.currentTarget.checked;
        if (targetName) {
            globalConfig[targetName] = checked ? 1 : 0;
            updateConfigDemoScript(globalConfig);
        }
    });
    // Numbers input
    $("[data-linguise-int]").on('change', (ev) => {
        const targetName = ev.currentTarget.dataset.linguiseInt;
        if (targetName) {
            const number = parseInt(ev.currentTarget.value, 10);
            if (!Number.isNaN(number)) {
                globalConfig[targetName] = number;
                updateConfigDemoScript(globalConfig);
            }
        }
    })

    /**
     * Parse hex color string into RGB object
     * 
     * @param {string} hex hex color string
     * @returns {object} RGB object
     */
    function parseHexColor(hex) {
        // Remove the hash symbol if present
        hex = hex.replace('#', '');
        // Check if this shorthand
        if (hex.length === 3) {
            hex = hex.split('').map((char) => char + char).join('');
        } else if (hex.length !== 6) {
            return null;
        }

        hex = hex.toUpperCase();

        // Check if this is a valid hex color
        if (!/^[0-9A-F]{6}$/i.test(hex)) {
            return null;
        }

        const r = parseInt(hex.slice(0, 2), 16);
        const g = parseInt(hex.slice(2, 4), 16);
        const b = parseInt(hex.slice(4, 6), 16);

        return { r, g, b };
    }

    /**
     * Combine color and alpha transparency
     * @param {string} color hex color string
     * @param {number} alpha float number from 0 to 1
     * @returns {string | null} rgba color string
     */
    function mixinColorAndAlpha(color, alpha) {
        const rgb = parseHexColor(color);
        if (!rgb) {
            return null;
        }
        const { r, g, b } = rgb;

        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    }

    function setAlpharamaInput(targetElement, hexColor, alphaValue) {
        // Combine both values into a single CSS variable
        const mixedIn = mixinColorAndAlpha(hexColor, alphaValue);

        if (mixedIn !== null) {
            // Set the background to the new value
            targetElement.style.backgroundImage = `linear-gradient(${mixedIn}), url(${checkerImageLocation.href})`;
        }
        return mixedIn;
    }

    // colorama
    $('[data-colorama]').each((_, elem) => {
        const $elem = $(elem);
        // Find alpharama pair
        const parent = elem.parentElement;
        const alpharamaInput = parent.querySelector('[data-alpharama]');
        const alpharamaBadge = parent.querySelector('[data-alpharama-target]');

        if (alpharamaInput && alpharamaBadge) {
            setAlpharamaInput(alpharamaBadge, elem.value, alpharamaInput.value);
        }

        $elem.iris({
            hide: true,
            change: function (event, ui) {
                const targetName = $(this).data('colorama');
                const target = $(`[data-colorama-target="${targetName}"]`);
                target.css('background-color', ui.color.toString());
                globalConfig[targetName] = ui.color.toString();
                if (alpharamaInput && alpharamaBadge) {
                    const withAlpha = setAlpharamaInput(alpharamaBadge, ui.color.toString(), alpharamaInput.value);
                    if (withAlpha) {
                        globalConfig[targetName] = withAlpha;
                    }
                }
                // Dispatch event to trigger validation changes
                this.dispatchEvent(new Event('input', { bubbles: true }));
                updateConfigDemoScript(globalConfig);
            }
        });
        globalState.coloramaSupported.push(elem);

        const picker = parent.querySelector('.iris-picker');

        elem.addEventListener('focus', (ev) => {
            picker.style.display = 'block';
        });
    });

    // alpharama
    $('[data-alpharama]').each((_, elem) => {
        const target = elem.dataset.alpharama;
        const targetElem = document.querySelector(`[data-alpharama-target="${target}"]`);

        // Find colorama pair
        const parent = elem.parentElement;
        const coloramaInput = parent.querySelector('[data-colorama]');

        elem.addEventListener('change', (ev) => {
            const value = parseFloat(ev.currentTarget.value) ?? 1.0;
            const hexValue = coloramaInput.value;

            const withAlpha = setAlpharamaInput(targetElem, hexValue, value);
            globalConfig[target] = value;
            if (withAlpha) {
                globalConfig[target.replace('_alpha', '')] = withAlpha;
            }
            updateConfigDemoScript(globalConfig);
        })
    });

    // After it done, we check if outside the colorama is clicked
    document.addEventListener('click', (ev) => {
        if (ev.target.closest('.linguise-color-group') === null) {
            // Close all colorama pickers
            globalState.coloramaSupported.forEach((elem) => {
                const parent = elem.parentElement;
                const picker = parent.querySelector('.iris-picker');
                // check what display mode is it
                const displayMode = picker.style.display;
                if (displayMode === 'none') {
                    return;
                }
                picker.style.display = 'none';
            });
        }
    });

    $('[data-colorama-target]').on('click', (ev) => {
        // stop propagation
        ev.stopPropagation();
        ev.preventDefault();

        // Get parent element
        const parent = ev.currentTarget.parentElement;
        const picker = parent.querySelector('.iris-picker');
        const displayMode = picker.style.display;
        // show
        if (displayMode === 'none') {
            picker.style.display = 'block';
        } else {
            picker.style.display = 'none';
        }
    });

    const copyToClipboard = (text) => {
        if (window.navigator.clipboard) {
            window.navigator.clipboard.writeText(text)
                .then(() => {
                    const elemMade = $('<div class="linguise-notification-popup"><span class="material-icons">done</span>Copied to clipboard</div>');
                    $('body').append(elemMade);
                    linguiseDelayHideNotification(elemMade);
                })
                .catch(err => {
                    console.log(err);
                    const elemMade = $('<div class="linguise-notification-popup"><span class="material-icons fail">close</span>Failed copying to clipboard</div>');
                    $('body').append(elemMade);
                    linguiseDelayHideNotification(elemMade);
                });
        } else {
            const elemMade = $('<div class="linguise-notification-popup"><span class="material-icons fail">close</span>Failed copying to clipboard</div>');
            $('body').append(elemMade);
            linguiseDelayHideNotification(elemMade);
        }
    }

    // copy to clipboard thing for each element
    const clipboardStuff = document.querySelectorAll('[data-clipboard-text]');
    clipboardStuff.forEach((elem) => {
        elem.addEventListener('click', (ev) => {
            ev.preventDefault();
            const clipText = ev.currentTarget?.getAttribute('data-clipboard-text');
            if (clipText) {
                copyToClipboard(clipText);
            }
        })
    });

    // Cache checkbox, on input value change
    $('[name="linguise_options[cache_enabled]"]').on('change', (ev) => {
        if (ev.currentTarget.checked) {
            $('[data-id="cache-wrapper"]').show();
        } else {
            $('[data-id="cache-wrapper"]').hide();
        }
    });
    $('[name="linguise_options[cache_enabled]"]').trigger('change');

    // Clear cache
    $('[data-linguise-action="clear-cache"]').on('click', (ev) => {
        ev.preventDefault();
        const href = ev.currentTarget.getAttribute('data-action-link');
        $.ajax({
            url: href,
            method: 'POST',
            success: (data) => {
                if (data.success === undefined) {
                    if (data === '0' || data === '') {
                        data = 'Cache empty!';
                    }
                    const elemMade = $('<div class="linguise-notification-popup"><span class="material-icons">done</span> ' + data + '</div>');
                    $('body').append(elemMade);
                    linguiseDelayHideNotification(elemMade);
                } else {
                    const elemMade = $('<div class="linguise-notification-popup"><span class="material-icons fail">close</span> Failed to clear cache!</div>');
                    $('body').append(elemMade);
                    linguiseDelayHideNotification(elemMade);
                }
            },
        });
    });
    // Clear debug file
    $('[data-linguise-action="clear-debug"]').on('click', (ev) => {
        ev.preventDefault();
        const href = ev.currentTarget.getAttribute('href');
        $.ajax({
            url: href,
            method: 'POST',
            success: (data) => {
                if (data.success) {
                    const elemMade = $('<div class="linguise-notification-popup"><span class="material-icons">done</span> ' + data.data + '</div>');
                    $('body').append(elemMade);
                    linguiseDelayHideNotification(elemMade);
                } else {
                    const elemMade = $('<div class="linguise-notification-popup"><span class="material-icons fail">close</span> Failed to clear debug file!</div>');
                    $('body').append(elemMade);
                    linguiseDelayHideNotification(elemMade);
                }
            },
        });
    });

    // Add shortcut to window to enable linguise options
    window.forceEnableLinguise = () => {
        // find all linguise-options
        const linguiseOptions = document.querySelectorAll('.linguise-options');
        linguiseOptions.forEach((elem) => {
            elem.classList.remove('is-disabled');
        });
    }

    // Add stuff to languages
    globalConfig.enabled_languages.forEach((lang) => {
        // Get the language name from all_languages
        const langName = globalConfig.all_languages[lang]?.name || lang;
        globalConfig.languages[lang] = langName;
    });

    // Do the color mixing for the alpharama inputs for now
    const flagShadowColor = globalConfig.flag_shadow_color || '#000000';
    const flagShadowAlpha = globalConfig.flag_shadow_color_alpha || 1.0;
    const mixedFlagShadow = mixinColorAndAlpha(flagShadowColor, flagShadowAlpha);
    if (mixedFlagShadow) {
        globalConfig.flag_shadow_color = mixedFlagShadow;
    }
    const flagHoverColor = globalConfig.flag_hover_shadow_color || '#000000';
    const flagHoverAlpha = globalConfig.flag_hover_shadow_color_alpha || 1.0;
    const mixedFlagHover = mixinColorAndAlpha(flagHoverColor, flagHoverAlpha);
    if (mixedFlagHover) {
        globalConfig.flag_hover_shadow_color = mixedFlagHover;
    }

    // Intercept form submit
    formValidator(document.querySelector('form.linguise-config-form'));

    // Render switcher preview
    updateConfigDemoScript(globalConfig);

    document.querySelectorAll('template[data-linguise="modal-iframe"]').forEach((template) => {
        /** @type {HTMLDivElement} */
        const rootFrame = template.content.querySelector('.linguise-modal-warn-area').cloneNode(true);
        const templateName = template.getAttribute('data-template');
        rootFrame.setAttribute('data-linguise-popup', templateName);

        // check if we already attached it
        if (!document.querySelector(`[data-linguise-popup="${templateName}"]`)) {
            document.body.appendChild(rootFrame);
        }
    });

    // Install the preview toggler
    const previewVisible = 'preview-visible';
    const previewToggle = document.querySelector('.preview-toggle');
    const previewArea = document.querySelector('.preview-area');
    previewToggle.addEventListener('click', (ev) => {
        ev.preventDefault();

        previewToggle.classList.toggle(previewVisible);
        previewArea.classList.toggle(previewVisible);
    });

    registerIframe();
});

jQuery(document).ready(($) => {
    // Separete load for hide notification
    linguiseDelayHideNotification();

    // Reset history state
    window.history.replaceState(null, '', window.location.href);

    const tippies = document.querySelectorAll('[data-tippy]');
    tippies.forEach((tippyElem) => {
        const placement = tippyElem.dataset.tippyDirection || 'top';
        tippy(tippyElem, {
            theme: 'reviews',
            animation: 'scale',
            animateFill: false,
            maxWidth: 300,
            duration: 0,
            arrow: true,
            placement,
            onShow(instance) {
                instance.setContent(instance.reference.dataset.tippy);
                instance.popper.hidden = instance.reference.dataset.tippy ? false : true;
                const tippyBox = instance.popper.querySelector('.tippy-box');
                if (tippyBox) {
                    tippyBox.classList.add('show');
                }
            },
        });
    });
});
