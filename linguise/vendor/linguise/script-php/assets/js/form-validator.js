/**
 * Intercept the form submission and validate the form fields.
 * @param {HTMLFormElement | null} form the main form element
 * @returns {HTMLFormElement | null} the form element
 */
function formValidator(form) {
    if (!form) {
        return;
    }

    // Check if this form element
    if (form.tagName !== 'FORM') {
        console.error(`The formValidator function must be called with a form element, found ${form.tagName} instead.`);
        return;
    }

    /**
     * Hide or show the warning message.
     * @param {HTMLElement} element the element to show or hide
     * @param {boolean} show show or hide the element
     */
    const toggleWarning = (element, show) => {
        if (!element) {
            return;
        }
        if (show) {
            element.classList.remove('is-hidden');
        } else {
            element.classList.add('is-hidden');
        }
    }

    /**
     * Find the warning element for the given field.
     * @param {HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement} field the field to validate
     * @param {boolean} isInvalid if the field is invalid
     * @returns {HTMLElement} the warning element for the field
     */
    const highlightInputWarning = (field, isInvalid) => {
        if (!field) {
            return;
        }
        if (isInvalid) {
            // This will show some kind of error glow at the field
            field.classList.add('is-invalid');
        } else {
            field.classList.remove('is-invalid');
        }
    }

    /**
     * Create a warning message for the given field.
     * @param {HTMLElement} targetField the target warning field
     */
    function makeWarning(targetField) {
        if (!targetField) {
            return targetField;
        }
        targetField.classList.add('linguise-form-invalid', 'is-hidden');
        targetField.setAttribute('role', 'alert');
        targetField.setAttribute('aria-live', 'assertive');
        return targetField;
    }

    /**
     * Find the warning element for the given field.
     * @param {HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement} field the field to validate
     * @returns {HTMLElement} the warning element for the field
     */
    function findWarningElement(field) {
        const dataSelector = field.dataset.validateTarget;
        if (dataSelector) {
            const warnField = form.querySelector(`[data-validate-warn="${dataSelector}"]`);
            return makeWarning(warnField);
        }

        // Check if next sibling is a warning element
        if (field.nextElementSibling && field.nextElementSibling.classList.contains('linguise-form-invalid')) {
            return field.nextElementSibling;
        }
        // Create the form invalid element if it doesn't exist
        const warningElement = document.createElement('label');
        makeWarning(warningElement);
        if (field.id) {
            warningElement.setAttribute('for', field.id);
            warningElement.setAttribute('id', `${field.id}-warning`);
        }
        // Attach after the field
        field.insertAdjacentElement('afterend', warningElement);
        return warningElement;
    }

    /**
     * Set the text of the warning field.
     * @param {HTMLElement} warningField the warning field
     * @param {string} text the text to set
     */
    function prefillWarning(warningField, text = '') {
        if (!warningField) {
            return;
        }
        if (!text) {
            warningField.textContent = '';
            return;
        }
        
        const prefix = warningField.dataset.prefix;
        if (prefix) {
            text = `${prefix} ${text}`;
        }
        warningField.textContent = text;
    }

    /**
     * Check if the field has a validator.
     * @param {HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement} field the field to check
     * @returns {boolean} true if the field has a validator, false otherwise
     */
    function hasValidator(field) {
        const validAttributes = ['required', 'pattern', 'minlength', 'maxlength', 'min', 'max', 'step'];
        return validAttributes.some((attr) => field.hasAttribute(attr));
    }

    /**
     * Check if all fields are valid.
     * @param {HTMLFormElement} form the form element to validate
     * @returns {boolean}
     */
    function validateAllFields(form) {
        /** @type {NodeListOf<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>} */
        const fields = form.querySelectorAll('input, textarea, select');
        let allValid = true;
        fields.forEach((field) => {
            if (field.type === 'hidden' || field.type === 'submit') {
                return;
            }
            // Check if novalidate
            if (field.hasAttribute('novalidate')) {
                return;
            }
            // Check if it has any validator
            if (!hasValidator(field)) {
                return;
            }

            const dataSelector = field.dataset.validateTarget;
            const warningField = findWarningElement(field);
            if (field.validity.valid) {
                prefillWarning(warningField);
                toggleWarning(warningField, false);
                highlightInputWarning(field, false);
            } else {
                allValid = false;
                prefillWarning(warningField, field.validationMessage || 'This field is invalid.');
                toggleWarning(warningField, true);
                highlightInputWarning(field, true);
            }
        });

        return allValid;
    }

    function hideAllWarnings() {
        /** @type {NodeListOf<HTMLElement>} */
        const warnings = form.querySelectorAll('.linguise-form-invalid');
        warnings.forEach((warning) => {
            toggleWarning(warning, false);
        });
    }

    /**
     * Highlight the field with a warning message.
     * @param {HTMLElement} field 
     */
    function highlightWarningField(field) {
        if (!field) {
            return;
        }

        // Find whose parent is the fieldset
        const fieldset = field.closest('.linguise-config-form .tab-content');
        if (fieldset) {
            const hashData = fieldset.dataset.id;
            window.location.hash = `#${hashData}`;
            setTimeout(() => {
                // scroll to field
                field.scrollIntoView({ behavior: 'smooth', block: 'center' });
            })
        }
    }

    form.addEventListener('submit', (event) => {
        event.preventDefault(); // Prevent the default form submission
        const isValid = validateAllFields(event.currentTarget);
        if (isValid) {
            hideAllWarnings();
            form.submit(); // Submit the form if all fields are valid
        } else {
            // Optionally, you can scroll to the first invalid field or show a message
            const firstInvalidField = form.querySelector('.linguise-form-invalid:not(.is-hidden)');
            if (firstInvalidField) {
                highlightWarningField(firstInvalidField);
            }
        }
    });

    form.addEventListener('input', (event) => {
        const { target } = event;
        if (target.tagName !== 'INPUT' && target.tagName !== 'TEXTAREA' && target.tagName !== 'SELECT') {
            return;
        }

        // Check if novalidate
        if (target.hasAttribute('novalidate')) {
            return;
        }
        // Check if it has any validator
        if (!hasValidator(target)) {
            return;
        }

        const warningField = findWarningElement(target);
        // Hide if valid
        if (target.validity.valid) {
            prefillWarning(warningField);
            toggleWarning(warningField, false);
            highlightInputWarning(target, false);
        } else {
            // Show if invalid
            prefillWarning(warningField, target.validationMessage || 'This field is invalid.');
            toggleWarning(warningField, true);
            highlightInputWarning(target, true);
        }
    });

    //== Init
    form.setAttribute('novalidate', 'novalidate'); // Disable native validation
    // Hide all warnings
    form.querySelectorAll('[data-validate-warn]').forEach((field) => {
        makeWarning(field);
    });
    // Validate init
    validateAllFields(form);

    return form;
}

export default formValidator;
