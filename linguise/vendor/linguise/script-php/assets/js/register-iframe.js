import $ from 'jquery';

function getDashboardUrlDomain() {
    const dashboardUrl = process.env.DASHBOARD_URL;
    if (typeof dashboardUrl === 'string' && dashboardUrl.length > 0) {
        const url = new URL(dashboardUrl);
        return `${url.protocol}//${url.host}`;
    }

    const overrideHost = window.linguise_configs.vars.configs.dashboard_url?.host;
    if (typeof overrideHost === 'string' && overrideHost.length > 0) {
        const overridePort = window.linguise_configs.vars.configs.dashboard_url?.port || 443;
        const noPort = [80, 443].includes(overridePort) ? '' : `:${overridePort}`;
        const protocol = overridePort === 443 ? 'https://' : 'http://';
        return `${protocol}${overrideHost}${noPort}`;
    }

    return 'https://dashboard.linguise.com';
}

function registerIframe() {
    const iframeState = {
        /** @type {string | null} */
        token: null,
        isLoggedIn: false,
    }

    function lockBody() {
        document.body.style.overflow = 'hidden';
    }

    function unlockBody() {
        document.body.style.overflow = '';
    }

    const globalConfig = window.linguise_configs.vars.configs;

    /* IFRAME DISPATCHER SYSTEM */
    /**
     * @type {HTMLTemplateElement}
     */
    const modalTemplate = document.querySelector('[data-template="linguise-register-frame"]');
    /** @type {HTMLDivElement} */
    const rootFrame = modalTemplate.content.querySelector('.linguise-register-area').cloneNode(true);
    rootFrame.setAttribute('data-modal', 'linguise-register-frame');

    const linguiseSiteUrl = document.querySelector('#linguise-site-url');
    const dashboardUrl = new URL(getDashboardUrlDomain());
    const realUrl = new URL(linguiseSiteUrl.getAttribute('data-url'));
    realUrl.search = '';
    realUrl.hash = '';

    // Attach to body, check if existing already attached
    if (!document.querySelector('[data-modal="linguise-register-frame"]')) {
        document.body.appendChild(rootFrame);
    }

    function showOrHideFooter(show = false) {
        const contentWrapper = rootFrame.querySelector('.content-wrapper');
        if (show) {
            contentWrapper.classList.add('with-login');
        } else {
            contentWrapper.classList.remove('with-login');
        }
    }

    /**
     * Show or hide the popup
     * @param {HTMLDivElement} popup 
     * @param {boolean} show 
     */
    function togglePopup(popup, show = false) {
        if (show) {
            popup.classList.add('is-visible');
        } else {
            popup.classList.remove('is-visible');
        }
    }

    function showDashboardRegistration(modeShow = 'login') {
        showOrHideFooter(false);
        iframeState.isLoggedIn = false;
        // Register method, show the iframe
        const iframeArea = rootFrame.querySelector('.frame-content');
        // Check if we already attached iframe
        if (iframeArea.querySelector('iframe')) {
            // Reset!
            iframeArea.innerHTML = '';
        }

        // Create iframe
        const iframe = document.createElement('iframe');

        const searchParams = new URLSearchParams();
        searchParams.set('plugin', realUrl.href);
        searchParams.set('start', modeShow);
        const fullUrl = new URL(dashboardUrl);
        fullUrl.pathname = '/';
        if (iframeState.token) {
            searchParams.set('token', iframeState.token);
        }
        fullUrl.search = searchParams.toString();

        // Set source
        iframe.src = fullUrl.href;
        // Set width and height to the full frame size of the iframe area
        iframe.style.width = '100%';
        iframe.style.height = '100%';

        // Attach
        iframeArea.appendChild(iframe);

        rootFrame.style.display = 'block';
        lockBody();
    }

    function hideAllPopup() {
        document.querySelectorAll('[data-linguise-popup]').forEach((el) => {
            togglePopup(el);
        });
    }

    document.querySelector('[data-linguise-register-action="register"]').addEventListener('click', (ev) => {
        ev.preventDefault();

        // Show the iframe
        showDashboardRegistration('register');
    });
    document.querySelector('[data-linguise-register-action="login"]').addEventListener('click', (ev) => {
        ev.preventDefault();

        // Show the iframe
        showDashboardRegistration('login');
    });
    document.querySelector('[data-linguise-action="close-modal"]').addEventListener('click', (ev) => {
        ev.preventDefault();

        // Show warning popup if already logged in
        if (iframeState.isLoggedIn) {
            const popup = document.querySelector('[data-linguise-popup="linguise-modal-abort"]');
            if (popup) {
                togglePopup(popup, true);
            }
            return;
        }

        // Hide the iframe
        const iframeArea = rootFrame.querySelector('.frame-content');
        // Check if we already attached iframe
        if (iframeArea.querySelector('iframe')) {
            // Reset!
            iframeArea.innerHTML = '';
        }

        // Then we hide the main modal
        rootFrame.style.display = 'none';
        unlockBody();
    });
    document.querySelector('[data-linguise-action="translate-save"]').addEventListener('click', (ev) => {
        const iframe = rootFrame.querySelector('iframe');
        if (!iframe) {
            console.log('No iframe found for Linguise dashboard!');
            return;
        }

        if (!iframe.contentWindow) {
            console.log('No content window for Linguise dashboard!');
            return;
        }

        // Send message to iframe
        iframe.contentWindow.postMessage(
            JSON.stringify({
                t: 'pluginSubmit',
                d: true,
            }),
            dashboardUrl.href,
        );
    });
    document.querySelectorAll('[data-linguise-action="close-modal-force"]').forEach((el) => {
        el.addEventListener('click', (ev) => {
            ev.preventDefault();

            const targetBtn = ev.currentTarget.getAttribute('data-linguise-action-target');

            // Hide the iframe
            const iframeArea = rootFrame.querySelector('.frame-content');
            // Check if we already attached iframe
            if (iframeArea.querySelector('iframe')) {
                // Reset!
                iframeArea.innerHTML = '';
            }

            // Then we hide the main modal
            rootFrame.style.display = 'none';
            unlockBody();

            // Then we hide all popup modals
            hideAllPopup();

            if (targetBtn === 'saved') {
                // hide button area
                document.querySelector('#login-register-btn-area').style.display = 'none';
                // hide regist warn
                document.querySelector('[data-id="linguise-register-warn"]').style.display = 'none';
                // enable save settings
                document.querySelector('.save-settings-input').removeAttribute('disabled');
                // remove all disabled state
                document.querySelectorAll('.linguise-options').forEach((el) => {
                    el.classList.remove('is-disabled');
                });
            }
        });
    });
    document.querySelector('[data-linguise-action="submit-try-again"]')?.addEventListener('click', (ev) => {
        ev.preventDefault();

        // We hide all popup modals
        hideAllPopup();

        const iframe = rootFrame.querySelector('iframe');
        if (!iframe) {
            console.log('No iframe found for Linguise dashboard!');
            return;
        }

        if (!iframe.contentWindow) {
            console.log('No content window for Linguise dashboard!');
            return;
        }

        // Send message to iframe
        iframe.contentWindow.postMessage(
            JSON.stringify({
                t: 'pluginSubmit',
                d: true,
            }),
            dashboardUrl.href,
        );
    });
    document.querySelectorAll('[data-linguise-action="popup-cancel-modal"]').forEach((el) => {
        el.addEventListener('click', (ev) => {
            ev.preventDefault();

            hideAllPopup();
        });
    });

    function updateConfigToPlugin(config) {
        // get nonce
        $.ajax({
            url: window.linguise_admin_iframe.ajax_url,
            type: 'POST',
            data: {
                nonce: window.linguise_admin_iframe.nonce,
                config: config,
            },
            success: function (response) {
                if (response.error) {
                    console.error('Error updating config:', response);   
                }
            },
            error: function (error) {
                // Handle error
                console.error('AJAX error:', error);
            },
        });
    }

    /**
     * Update data to global config
     * @param {LinguiseConfig} data 
     */
    function updateDataToLocalConfig(data) {
        globalConfig.default_language = data.language;
        globalConfig.current_language = data.language;
        globalConfig.enabled_languages = data.allowed_languages;
        globalConfig.dynamic_translations.enabled = data.dynamicTl;
        globalConfig.dynamic_translations.public_key = data.public_key;
        globalConfig.token = data.token;
    }

    /**
     * Update the data from the iframe to the plugin config
     * @param {LinguiseConfig} data the data from the iframe
     * 
     * @typedef {Object} LinguiseConfig
     * @property {number} id - The ID of the site
     * @property {string} url - The URL of the site
     * @property {string} token - The token from the iframe
     * @property {string} platform - The platform of the site (e.g., WordPress, Shopify, etc.)
     * @property {string} public_key - The public key for the site
     * @property {"subfolders" | "subdomains"} structure - The URL structure of the site
     * @property {string} language - The default language of the site
     * @property {string[]} allowed_languages - The list of enabled languages for the site
     */
    function updateDataFromIframe(data) {
        updateDataToLocalConfig(data);
        // we need to update our local config from the iframe
        /** @type {HTMLInputElement} */
        const tokenInput = document.querySelector('input[name="linguise_options[token]"]');
        tokenInput.value = data.token;

        /** @type {HTMLSelectElement} */
        const defaultLanguageSelect = document.querySelector('select[name="linguise_options[default_language]"]');
        defaultLanguageSelect.querySelectorAll('option').forEach((option) => {
            if (option.value === data.language) {
                option.selected = true;
            } else {
                option.selected = false;
            }
        });
        defaultLanguageSelect.dispatchEvent(new Event('change'));

        /** @type {HTMLSelectElement} */
        const msTranslateInto = document.querySelector('#ms-translate-into');
        msTranslateInto.querySelectorAll('option').forEach((option) => {
            if (data.allowed_languages.includes(option.value)) {
                option.selected = true;
            } else {
                option.selected = false;
            }
        });
        msTranslateInto.dispatchEvent(new Event('change'));
        $(msTranslateInto).trigger('chosen:updated');

        /** @type {HTMLInputElement} */
        const dynamicTl = document.querySelector('input[name="linguise_options[dynamic_translations]"]');
        dynamicTl.checked = data.dynamicContent;

        updateConfigToPlugin(data);
    }

    function updateTranslateButton(enabled = true) {
        const translateButton = document.querySelector('[data-linguise-action="translate-save"]');
        if (translateButton) {
            if (enabled) {
                translateButton.removeAttribute('disabled');
            } else {
                translateButton.setAttribute('disabled', 'disabled');
            }
        }
    }

    // Listen for message from the iframe window
    window.addEventListener('message', (event) => {
        const origin = new URL(event.origin);
        origin.pathname = '/';
        origin.search = '';

        if (origin.href !== dashboardUrl.href) {
            // Invalid origin
            return;
        }

        if (!event.data) {
            return;
        }

        let data;
        try {
            data = JSON.parse(event.data);
        } catch (e) {
            return;
        }

        if (!data) {
            return;
        }

        // Get iframe
        const iframe = rootFrame.querySelector('iframe');
        if (!iframe) {
            console.log('No iframe found for Linguise dashboard!');
            return;
        }

        if (!iframe.contentWindow) {
            console.log('No content window for Linguise dashboard!');
            return;
        }

        switch (data.t) {
            case 'pluginRequestInit': {
                showOrHideFooter(true);
                iframeState.isLoggedIn = true;
                const url = data.d;
                if (url !== realUrl.href) {
                    // mismatched URL
                    break;
                }

                // url, original, languages, platform, dynamicContent
                const metadata = {
                    url: realUrl.href,
                    original: globalConfig.default_language,
                    languages: globalConfig.enabled_languages,
                    platform: globalConfig.platform || 'other_php',
                    dynamicContent: Boolean(globalConfig.dynamic_translations.enabled),
                };
                if (metadata.platform === 'auto') {
                    metadata.platform = 'other_php';
                }

                if (globalConfig.original_default && globalConfig.original_default !== 'en' && metadata.original !== 'en') {
                    // Override original language if not set and not English
                    metadata.original = globalConfig.original_default;
                }

                // Send message to iframe
                console.log('Plugin request init!', metadata);
                iframe.contentWindow.postMessage(
                    JSON.stringify({
                        t: 'pluginInit',
                        d: metadata,
                    }),
                    dashboardUrl.href,
                )
                break;
            }
            case 'pluginSetupComplete': {
                console.log('Plugin setup complete!');
                updateDataFromIframe(data.d);
                // flush token!
                iframeState.token = null;
                iframeState.isLoggedIn = false;
                // hide the iframe window first
                rootFrame.style.display = 'none';
                break;
            }
            case 'pluginLoginToken': {
                if (data.d) {
                    iframeState.token = data.d;
                }
                break;
            }
            case 'pluginSaving': {
                // Show loading popup
                hideAllPopup();
                const popup = document.querySelector('[data-linguise-popup="linguise-modal-saving"]');
                if (popup) {
                    togglePopup(popup, true);
                }
                break;
            }
            case 'pluginValidationFail': {
                // This is early error before sending to the API
                break;
            }
            case 'pluginSaved': {
                // Show success modal
                hideAllPopup();
                const popup = document.querySelector('[data-linguise-popup="linguise-modal-saved"]');
                if (popup) {
                    togglePopup(popup, true);
                }
                break;
            }
            case 'pluginSavingFail': {
                // Show error modal
                hideAllPopup();
                const popup = document.querySelector('[data-linguise-popup="linguise-modal-error"]');
                if (popup) {
                    togglePopup(popup, true);
                }
                break;
            }
            case 'pluginTranslateButton': {
                if (typeof data.d === 'boolean') {
                    updateTranslateButton(data.d);
                }
                break;
            }
            default: {
                // Unknown message
                break;
            }
        }
    });
}

export default registerIframe;
