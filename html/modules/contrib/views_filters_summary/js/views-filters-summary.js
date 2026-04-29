/* eslint-disable class-methods-use-this */
((Drupal, once) => {
  class ViewsFiltersSummaryHandler {
    /**
     * Constructs a new viewsFiltersSummary instance.
     *
     * @param {Element} context
     *   The context in which the views filters summary is being used.
     */
    constructor(context) {
      this.context = context;
      // Bind the method to preserve 'this' context
      this.onRemoveClick = this.onRemoveClick.bind(this);
      this.onResetClick = this.onResetClick.bind(this);
    }

    /**
     * Get all exposed form inputs to reset.
     *
     * @param {Element} exposedForm
     *   The exposed form jQuery object.
     * @return {NodeListOf<Element>}
     *   The list of inputs.
     */
    getFormInputsToReset(exposedForm) {
      return exposedForm.querySelectorAll('input');
    }

    /**
     * Get all exposed form select elements to reset.
     *
     * @param {Element} exposedForm
     *   The exposed form jQuery object.
     * @return {NodeListOf<Element>}
     *   The list of select elements.
     */
    getFormSelectsToReset(exposedForm) {
      return exposedForm.querySelectorAll('select');
    }

    /**
     * Reset all exposed form inputs to an empty value/default state.
     *
     * @param {Element} exposedForm
     *   The exposed form jQuery object.
     * @param {Array} filterIds
     *  (optional) An array of filter IDs to reset. If not provided, all inputs
     *   will be reset.
     */
    reset(exposedForm, filterIds) {
      const inputs = this.getFormInputsToReset(exposedForm);
      // A function to check should this element be handled.
      const isSummaryElement = (element) => {
        // Remove array part from the name, mostly for checkboxes and radios.
        const nameClean = element.name.replace(/\[.*?\]/, '');
        return filterIds && filterIds.includes(nameClean);
      };
      inputs.forEach((input) => {
        if (!isSummaryElement(input)) {
          return;
        }
        switch (input.type) {
          case 'password':
          case 'select-multiple':
          case 'select-one':
          case 'text':
          case 'textarea':
          case 'date':
          case 'search':
            input.value = '';
            break;
          case 'checkbox':
          case 'radio':
            input.checked = false;
            break;
          case 'hidden':
            // BEF links widget handling.
            input.remove();
            break;
          default:
            break;
        }
      });
      const selects = this.getFormSelectsToReset(exposedForm);
      selects.forEach((select) => {
        if (!isSummaryElement(select)) {
          return;
        }
        Array.from(select.options).forEach((option) => {
          option.selected = false;
        });
      });
    }

    /**
     * Check if the views exposed form uses AJAX.
     *
     * @return {boolean}
     *   true if Ajax is used, false otherwise.
     */
    usesAjax() {
      // Views preview mode uses Ajax.
      if (this.exposedForm && this.exposedForm.id === 'views-ui-preview-form') {
        return true;
      }
      const summary = this.context.querySelector('.views-filters-summary');
      return summary.classList.contains('views-filters-summary--use-ajax');
    }

    /**
     * Returns the current view exposed form.
     *
     * @return {Element|*}
     *   The view exposed form.
     */
    getExposedForm() {
      if (!this.exposedForm) {
        const summaryElt = this.context.querySelector('[data-exposed-form-id]');
        const exposedFormId = summaryElt.getAttribute('data-exposed-form-id');
        this.exposedForm = this.context.querySelector(
          `form[id^="${exposedFormId}"]`,
        );
        if (!this.exposedForm) {
          // When the form is not found in the context (e.g. as a block), use document instead.
          this.exposedForm = document.querySelector(
            `form[id^="${exposedFormId}"]`,
          );
        }
      }
      return this.exposedForm;
    }

    /**
     * Returns the exposed form filter button.
     *
     * @param {Element} exposedForm
     *   The exposed form jQuery object.
     * @return {Element|*}
     *   The exposed form filter button.
     */
    getFilterSubmit(exposedForm) {
      // In preview mode, try to use the "Update preview" button first.
      if (exposedForm.id === 'views-ui-preview-form') {
        const previewSubmit = exposedForm.querySelector('#preview-submit');
        if (previewSubmit) {
          return previewSubmit;
        }
      }
      // Some exposed forms can have multiple "filter" buttons.
      // Collect all "submit" buttons/inputs within the exposed form.
      const candidates = Array.from(
        exposedForm.querySelectorAll(':is(button, input)[type="submit"]'),
      );

      // Helper to determine element visibility (no jQuery :visible).
      const isVisible = (el) => {
        if (!el) return false;
        const style = window.getComputedStyle(el);
        if (
          style.display === 'none' ||
          style.visibility === 'hidden' ||
          style.opacity === '0'
        ) {
          return false;
        }
        // offsetParent is null for display:none or detached. Allow
        // fixed-position too.
        return el.offsetParent !== null || style.position === 'fixed';
      };

      // Filter out disabled or hidden elements.
      const enabledVisible = candidates.filter(
        (el) => !el.disabled && isVisible(el),
      );

      // Preference 1: explicit views submit - data-drupal-selector ID begin
      // with edit-submit.
      const primaryBySelector = enabledVisible.find(
        (el) =>
          (el.getAttribute('data-drupal-selector') || '').startsWith(
            'edit-submit',
          ) || (el.id || '').startsWith('edit-submit'),
      );
      if (primaryBySelector) return primaryBySelector;

      // Preference 2: select visually primary button.
      const primaryByClass = enabledVisible.find(
        (el) =>
          el.classList.contains('button--primary') ||
          el.classList.contains('btn-primary'),
      );
      if (primaryByClass) return primaryByClass;

      // Fallback: select the first "submit" button or input.
      if (enabledVisible.length > 0) return enabledVisible[0];
      return candidates[0] || null;
    }

    /**
     * Execute exposed form Ajax submit.
     *
     * @param {Element} exposedForm
     *   The exposed form jQuery object.
     */
    ajaxSubmit(exposedForm) {
      const submit = this.getFilterSubmit(exposedForm);
      submit.click();
    }

    /**
     * Simulate a form submit using history and reload.
     *
     * @param {Element} exposedForm
     *   The exposed form jQuery object.
     */
    historySubmit(exposedForm) {
      const formData = new FormData(exposedForm); // Gather form data.
      const { action } = exposedForm; // Get the action URL.
      const params = new URLSearchParams(formData).toString(); // Convert form data to query string.
      const url = action + (action.includes('?') ? '&' : '?') + params;
      window.history.replaceState({}, document.title, url);
      window.location.reload();
    }

    /**
     * Get all exposed form elements that match a specific selector.
     *
     * @param {Element} exposedForm
     *   The exposed form jQuery object.
     * @param {string} selector
     *   The selector to match inputs against.

     * @return {NodeListOf<Element>}
     *   The list of inputs that match the selector.
     */
    getFormElementsByName(exposedForm, selector) {
      return exposedForm.querySelectorAll(`[name^="${selector}"]`);
    }

    /**
     * Get the form element that contains the input.
     *
     * @param {Element} input
     *   The input element to find the form for.
     *
     * @return {Element}
     *   The form element that contains the input.
     */
    getInputForm(input) {
      return input.form || input.closest('form');
    }

    /**
     * Submit the exposed form.
     *
     * @param {Element} exposedForm
     *   The exposed form jQuery object.
     */
    submit(exposedForm) {
      if (this.usesAjax()) {
        this.ajaxSubmit(exposedForm);
      } else {
        this.historySubmit(exposedForm);
      }
    }

    /**
     * Click the views exposed form submit button or reload the page if
     * filters existed in the URL.
     *
     * @param {MouseEvent} event
     *   The click event.
     */
    onRemoveClick(event) {
      event.preventDefault();
      const removeSelector = event.currentTarget.getAttribute(
        'data-remove-selector',
      );
      const colonIndex = removeSelector.indexOf(':');
      const selector = removeSelector.substring(0, colonIndex);
      const value = removeSelector.substring(colonIndex + 1);
      const exposedForm = this.getExposedForm();
      const inputs = this.getFormElementsByName(exposedForm, selector);
      const resetInputs = new Set();
      inputs.forEach((input) => {
        if (input.tagName === 'INPUT') {
          switch (input.type) {
            case 'radio':
            case 'checkbox':
              if (input.value === value) {
                input.checked = false;
                resetInputs.add(input);
              }
              break;
            case 'hidden':
              // BEF links widget handling.
              if (input.value === value) {
                resetInputs.add(input);
                input.remove();
              }
              break;
            default:
              // Handle the special case of an autocomplete field.
              if (input.hasAttribute('data-autocomplete-path')) {
                const originalValues = input.value.split(',');
                const updatedValues = originalValues.filter((v) => {
                  const match = v.match(/.+\s\(([^)]+)\)/);
                  return match && match[1] !== value;
                });
                if (originalValues.length !== updatedValues.length) {
                  input.value = updatedValues.join(', ');
                  resetInputs.add(input);
                }
              } else {
                input.value = '';
                resetInputs.add(input);
              }
              break;
          }
        } else if (
          input.tagName === 'SELECT' &&
          !input.hasAttribute('multiple')
        ) {
          // Simple deselect to avoid issue with custom select-based range fields.
          // Range filters currently use "min|max" as value, so the selector
          // based on value cannot be used here.
          // @see https://www.drupal.org/i/3533325
          input.selectedIndex = -1;
          resetInputs.add(input);
        } else {
          input.querySelectorAll(`[value="${value}"]`).forEach((element) => {
            element.selected = false;
            resetInputs.add(input);
          });
        }
      });
      // If no inputs were reset, there's no need to submit.
      if (resetInputs.size > 0) {
        const firstInput = resetInputs.values().next().value;
        const formToSubmit = this.getInputForm(firstInput) || exposedForm;
        this.submit(formToSubmit);
      }
    }

    /**
     * Returns the current view exposed form to reset.
     *
     * @param {Element} resetElement
     *   The reset button element that was clicked.
     *
     * @return {Element|*}
     *   The view exposed form to reset.
     */
    // eslint-disable-next-line no-unused-vars
    getExposedFormToReset(resetElement) {
      return this.getExposedForm();
    }

    /**
     * Reset all views exposed form filters.
     *
     * @param {MouseEvent} event
     *   The click event.
     */
    onResetClick(event) {
      event.preventDefault();
      const exposedForm = this.getExposedFormToReset(event.currentTarget);
      const summaryFilterIds = event.currentTarget
        .getAttribute('data-filter-ids')
        .split(',');
      this.reset(exposedForm, summaryFilterIds);
      this.submit(exposedForm);
    }

    /**
     * Bind the remove and reset click events.
     *
     * @param {Element} element
     *   The views summary element containing the links and buttons.
     */
    addEventListeners(element) {
      element.querySelectorAll('a.remove-filter').forEach((elt) => {
        elt.addEventListener('click', this.onRemoveClick);
        elt.classList.remove('disabled');
      });
      element.querySelectorAll('a.reset').forEach((elt) => {
        elt.addEventListener('click', this.onResetClick);
        elt.classList.remove('disabled');
      });
    }
  }
  Drupal.ViewsFiltersSummaryHandler = ViewsFiltersSummaryHandler;

  Drupal.behaviors.viewsFiltersSummary = {
    attach(context) {
      once('views-filters-summary', '.views-filters-summary', context).forEach(
        (element) => {
          /**
           * Bind the remove and reset click events.
           */
          const handler = new Drupal.ViewsFiltersSummaryHandler(context);
          handler.addEventListeners(element);
        },
      );
    },
  };
})(Drupal, once);
