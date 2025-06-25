// Login script
import $ from 'jquery';

import '../css/login-main.scss';
import 'material-design-icons/iconfont/material-icons.css'

import tippy from 'tippy.js';
import 'tippy.js/dist/tippy.css';

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

function showSuccessMessage(message) {
    const elemMade = $(`<div class="linguise-notification-popup"><span class="material-icons">done</span>${message}</div>`);
    $('body').append(elemMade);
    linguiseDelayHideNotification(elemMade);
}

function showErrorMessage(message) {
    const elemMade = $(`<div class="linguise-notification-popup"><span class="material-icons fail">close</span>${message}</div>`);
    $('body').append(elemMade);
    linguiseDelayHideNotification(elemMade);
}


/**
 * Form page for login
 * @param {HTMLFormElement} formEl the form element
 * @param {JQueryStatic} $ the jQuery object
 */
async function loginPageEntrypoint(formEl, $) {
    // Intercept the form submission
    formEl.addEventListener('submit', (ev) => {
        // disable the submit button but do not prevent any event
        const submitButton = formEl.querySelector('input[type="submit"]');
        const loginBox = formEl.querySelector('#login-box');
        // set submit status
        if (submitButton) {
            submitButton.setAttribute('disabled', 'disabled');
        }
        // set login box status
        if (loginBox) {
            loginBox.setAttribute('aria-disabled', 'true');
        }
    });
}

/**
 * Form page for login
 * @param {HTMLFormElement} formEl the form element
 * @param {JQueryStatic} $ the jQuery object
 */
async function registerPageEntrypoint(formEl, $) {
    /** @type {HTMLSelectElement} */
    const databaseBox = document.querySelector('#database-box');
    const dbContainer = document.querySelector('#db-container');
    const submitButton = formEl.querySelector('input[type="submit"]');
    const testConnection = document.querySelector('[data-action="test-connection"]');
    const requiredInputs = ['db_host', 'db_user', 'db_name', 'db_prefix'];

    function showOrHideMySQLConfig(show) {
        dbContainer.querySelectorAll('.mysql-marker').forEach((el) => {
            // If not show, add none. If show remove none
            el.style.display = show ? '' : 'none';
        });
    }

    databaseBox.addEventListener('change', (ev) => {
        // check if we only have one option
        const options = databaseBox.querySelectorAll('option');
        if (options.length !== 1) {
            // If sqlite is selected, hide the mysql container
            if (databaseBox.value === 'sqlite') {
                showOrHideMySQLConfig(false);

                // if we select sqlite, we need to remove all required input
                requiredInputs.forEach((inputName) => {
                    const input = formEl.querySelector(`[name="${inputName}"]`);
                    if (input) {
                        input.removeAttribute('required');
                    }
                });
            } else {
                showOrHideMySQLConfig(true);

                // if we select mysql, we need to add all required input
                requiredInputs.forEach((inputName) => {
                    const input = formEl.querySelector(`[name="${inputName}"]`);
                    if (input) {
                        input.setAttribute('required', 'required');
                    }
                });
            }
        }
    });
    // trigger the change to update required state and more
    databaseBox.dispatchEvent(new Event('change'));
    
    const submitStatus = (submitting = true) => {
        // find all inputs and set them to disabled
        const inputs = formEl.querySelectorAll('input, select, textarea');
        inputs.forEach((input) => {
            if (input.dataset?.action === 'register') {
                return;
            }
            if (input === databaseBox) {
                if (databaseBox.dataset.alwaysDisable) {
                    return;
                }
            }

            if (submitting) {
                input.setAttribute('disabled', 'disabled');
            } else {
                input.removeAttribute('disabled');
            }
        });

        if (submitting) {
            testConnection.setAttribute('disabled', 'disabled');
        } else {
            testConnection.removeAttribute('disabled');
        }
    }

    formEl.addEventListener('submit', (ev) => {
        // we prevent the actual submission but allow validation
        ev.preventDefault();

        const formData = new FormData(formEl);
        const actionUrl = new URL(formEl.action);
        if (!formData.has(databaseBox.name)) {
            formData.append(databaseBox.name, databaseBox.value);
        }
        actionUrl.searchParams.set('linguise_action', 'activate-linguise');

        submitStatus(true);

        $.ajax({
            type: 'POST',
            url: actionUrl.href,
            data: formData,
            // this should return a JSON object
            dataType: 'json',
            processData: false,
            contentType: false,
            success: (response) => {
                if (response.error) {
                    showErrorMessage(response.message);
                    submitStatus(false);
                } else {
                    // reload page, some artificial delay
                    setTimeout(() => {
                        window.location.reload();
                    }, 100);
                }
            },
            error: (xhr, status, error) => {
                // Handle the error response here
                console.error('Error:', error);
                const responseData = xhr.responseJSON;
                if (responseData && responseData.message) {
                    showErrorMessage(responseData.message);
                } else {
                    showErrorMessage('An error occurred while processing your request.');
                }
                submitStatus(false);
            }
        })
    });

    testConnection.addEventListener('click', (ev) => {
        ev.preventDefault();

        // request AJAX
        const formData = new FormData(formEl);
        const actionUrl = new URL(formEl.action);
        if (!formData.has(databaseBox.name)) {
            formData.append(databaseBox.name, databaseBox.value);
        }

        actionUrl.searchParams.set('linguise_action', 'test-connection');
        submitStatus(true);

        $.ajax({
            type: 'POST',
            url: actionUrl.href,
            data: formData,
            // this should return a JSON object
            dataType: 'json',
            processData: false,
            contentType: false,
            success: (response) => {
                submitStatus(false);
                if (response.error) {
                    showErrorMessage(response.message);
                    submitButton.setAttribute('disabled', 'disabled');
                } else {
                    showSuccessMessage(response.message);
                    submitButton.removeAttribute('disabled');
                }
            },
            error: (xhr, status, error) => {
                // Handle the error response here
                console.error('Error:', error);
                const responseData = xhr.responseJSON;
                if (responseData && responseData.message) {
                    showErrorMessage(responseData.message);
                } else {
                    showErrorMessage('An error occurred while processing your request.');
                }
                submitStatus(false);
                submitButton.setAttribute('disabled', 'disabled');
            }
        })
    });
}

jQuery(document).ready(($) => {
    // Hide notifications after a delay
    linguiseDelayHideNotification();

    // Reset history state
    window.history.replaceState(null, null, window.location.href);

    // Initialize tooltips
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
            },
        });
    });

    const formLogin = document.querySelector('#login-page');
    if (formLogin) {
        loginPageEntrypoint(formLogin, $);
    }

    const formRegister = document.querySelector('#register-page');
    if (formRegister) {
        registerPageEntrypoint(formRegister, $);
    }

    const $elements = $('.linguise-notification-popup');
    if ($elements.length) {
        setTimeout(() => {
            $elements.fadeOut(2000);
            setTimeout(() => {
                $elements.remove();
            }, 2100);
        }, 3000);
    }
});
