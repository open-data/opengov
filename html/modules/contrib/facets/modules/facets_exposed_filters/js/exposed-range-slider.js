/**
 * @file
 * Provides range slider behavior for exposed facet filters.
 */

/* global noUiSlider */

(function (Drupal, drupalSettings, once) {
  /**
   * @typedef {Object} RangeSliderOptions
   * @property {string|number} min
   * @property {string|number} max
   * @property {string|number} step
   * @property {string|number} animate
   * @property {string} orientation
   * @property {boolean} tooltips
   * @property {string} tooltips_value_prefix
   * @property {string} tooltips_value_suffix
   * @property {boolean|string|number} snap_to_values
   * @property {Array<number>|string} snap_values
   * @property {boolean|string|number} preserve_active_range
   */

  /**
   * Reads slider options from element data attributes.
   *
   * @param {HTMLElement} slider
   *   Slider container element.
   *
   * @returns {RangeSliderOptions}
   *   Parsed slider options.
   */
  function getDatasetOptions(slider) {
    return {
      min: slider.dataset.min,
      max: slider.dataset.max,
      step: slider.dataset.step,
      animate: slider.dataset.animate,
      orientation: slider.dataset.orientation,
      tooltips: slider.dataset.tooltips === '1',
      tooltips_value_prefix: slider.dataset.tooltipsValuePrefix,
      tooltips_value_suffix: slider.dataset.tooltipsValueSuffix,
      snap_to_values: slider.dataset.snapToValues === '1',
      snap_values: slider.dataset.snapValues,
      preserve_active_range: slider.dataset.preserveActiveRange === '1',
    };
  }

  /**
   * Returns all unique min/max input names for range sliders in the form.
   *
   * @param {HTMLFormElement|null} form
   *   The form that owns the sliders.
   *
   * @returns {Array<string>}
   *   Unique range input names.
   */
  function getRangeInputNames(form) {
    if (!form) {
      return [];
    }

    const names = [];

    form
      .querySelectorAll(
        '[data-facets-exposed-range-slider-min], [data-facets-exposed-range-slider-max]',
      )
      .forEach((input) => {
        if (typeof input.name === 'string' && input.name !== '') {
          names.push(input.name);
        }
      });

    return [...new Set(names)];
  }

  /**
   * Removes specific query parameters from a URL string.
   *
   * @param {string} url
   *   Absolute or relative URL.
   * @param {Array<string>} names
   *   Query parameter names to remove.
   *
   * @returns {string}
   *   URL without the selected query parameters.
   */
  function removeQueryParameters(url, names) {
    try {
      const parsed = new URL(url, window.location.origin);

      names.forEach((name) => parsed.searchParams.delete(name));

      if (/^[a-z][a-z0-9+.-]*:/i.test(url)) {
        return parsed.toString();
      }

      return `${parsed.pathname}${parsed.search}${parsed.hash}`;
    } catch (e) {
      return url;
    }
  }

  /**
   * Parses a numeric input value with a fallback.
   *
   * @param {HTMLInputElement} input
   *   Input element to read.
   * @param {number} fallback
   *   Fallback value when parsing fails.
   *
   * @returns {number}
   *   Parsed number or fallback.
   */
  function parseInput(input, fallback) {
    const value = Number.parseFloat(input.value);
    return Number.isNaN(value) ? fallback : value;
  }

  /**
   * Restricts a value to a numeric interval.
   *
   * @param {number} value
   * @param {number} min
   * @param {number} max
   *
   * @returns {number}
   *   Clamped value.
   */
  function clamp(value, min, max) {
    return Math.min(Math.max(value, min), max);
  }

  /**
   * Finds the closest value in a numeric list.
   *
   * @param {number} value
   *   Source value.
   * @param {Array<number>} values
   *   Candidate values.
   *
   * @returns {number}
   *   Closest candidate, or source value for an empty list.
   */
  function findClosestValue(value, values) {
    if (values.length === 0) {
      return value;
    }

    let closestValue = values[0];
    let smallestDistance = Math.abs(value - closestValue);

    values.forEach((candidate) => {
      const distance = Math.abs(value - candidate);
      if (distance < smallestDistance) {
        closestValue = candidate;
        smallestDistance = distance;
      }
    });

    return closestValue;
  }

  /**
   * Converts ordered snap values into a noUiSlider `range` object.
   *
   * @param {Array<number>} values
   *   Sorted snap values.
   *
   * @returns {Object<string, number>}
   *   noUiSlider range map with `min`, `max`, and percentage keys.
   */
  function buildSnapRange(values) {
    const range = {};
    const lastIndex = values.length - 1;

    values.forEach((value, index) => {
      if (index === 0) {
        range.min = value;
        return;
      }

      if (index === lastIndex) {
        range.max = value;
        return;
      }

      const percentage = (index / lastIndex) * 100;
      range[`${percentage}%`] = value;
    });

    return range;
  }

  /**
   * Normalizes an initial handle value to valid bounds and snap points.
   *
   * @param {number} value
   * @param {number} min
   * @param {number} max
   * @param {Array<number>} snapValues
   * @param {boolean} useSnap
   *
   * @returns {number}
   *   Normalized start value.
   */
  function normalizeStartValue(value, min, max, snapValues, useSnap) {
    const clampedValue = clamp(value, min, max);

    if (!useSnap) {
      return clampedValue;
    }

    return findClosestValue(clampedValue, snapValues);
  }

  /**
   * Parses, filters, sorts, and de-duplicates snap values.
   *
   * @param {Array<number>|string} values
   *   Snap values array or comma-separated string.
   * @param {number} min
   *   Minimum allowed boundary.
   * @param {number} max
   *   Maximum allowed boundary.
   *
   * @returns {Array<number>}
   *   Normalized numeric snap values.
   */
  function normalizeSnapValues(values, min, max) {
    let inputValues = [];
    if (Array.isArray(values)) {
      inputValues = values;
    } else if (typeof values === 'string' && values.trim() !== '') {
      inputValues = values.split(',');
    }

    const numericValues = inputValues
      .map((value) => Number.parseFloat(value))
      .filter((value) => Number.isFinite(value) && value >= min && value <= max)
      .sort((a, b) => a - b);

    return numericValues.filter((value, index) => {
      if (index === 0) {
        return true;
      }

      return Math.abs(value - numericValues[index - 1]) > Number.EPSILON;
    });
  }

  /**
   * Returns the highest decimal precision used in a value list.
   *
   * @param {Array<number>} values
   *   Numeric values.
   *
   * @returns {number}
   *   Maximum decimal precision.
   */
  /**
   * Returns the decimal precision implied by a step value.
   *
   * @param {number|string} step
   *   Step configuration value.
   *
   * @returns {number}
   *   Number of decimal places.
   */
  function getPrecision(step) {
    const stepString = step.toString();

    if (!stepString.includes('.')) {
      return 0;
    }

    return stepString.split('.')[1].replace(/0+$/, '').length;
  }

  /**
   * Returns the highest decimal precision used in a value list.
   *
   * @param {Array<number>} values
   *   Numeric values.
   *
   * @returns {number}
   *   Maximum decimal precision.
   */
  function getValuesPrecision(values) {
    return values.reduce((precision, value) => {
      return Math.max(precision, getPrecision(value.toString()));
    }, 0);
  }

  /**
   * Normalizes common truthy representations.
   *
   * @param {*} value
   *   Raw value from settings or dataset.
   *
   * @returns {boolean}
   *   TRUE when value should be treated as enabled.
   */
  function toBoolean(value) {
    return value === true || value === '1' || value === 1 || value === 'true';
  }

  /**
   * Computes a precision-aware numeric tolerance.
   *
   * @param {number} precision
   *   Decimal precision.
   *
   * @returns {number}
   *   Tolerance value for floating-point comparisons.
   */
  function getNumericTolerance(precision) {
    return precision === 0 ? Number.EPSILON : 1 / 10 ** (precision + 2);
  }

  /**
   * Formats a numeric value for display and input synchronization.
   *
   * @param {number|string} value
   *   Value to format.
   * @param {number} precision
   *   Decimal precision to apply.
   *
   * @returns {string}
   *   Formatted value.
   */
  function formatValue(value, precision) {
    const number = Number.parseFloat(value);

    if (precision === 0) {
      return Math.round(number).toString();
    }

    return number.toFixed(precision).replace(/\.?0+$/, '');
  }

  /**
   * Checks whether current values equal the default min/max bounds.
   *
   * @param {number} currentMin
   * @param {number} currentMax
   * @param {number} min
   * @param {number} max
   * @param {number} precision
   *
   * @returns {boolean}
   *   TRUE if the range equals the full default range.
   */
  function isDefaultRange(currentMin, currentMax, min, max, precision) {
    const tolerance =
      precision === 0 ? Number.EPSILON : 1 / 10 ** (precision + 2);

    return (
      Math.abs(currentMin - min) <= tolerance &&
      Math.abs(currentMax - max) <= tolerance
    );
  }

  /**
   * Clears min/max inputs when the slider is at the full default range.
   *
   * This keeps query strings short and avoids persisting redundant defaults.
   *
   * @param {HTMLFormElement|null} form
   *   The form that owns the sliders.
   */
  function clearDefaultRangeInputs(form) {
    if (!form) {
      return;
    }

    form
      .querySelectorAll('[data-facets-exposed-range-slider]')
      .forEach((slider) => {
        const wrapper = slider.closest('.facets-exposed-range-slider');
        const minInput = wrapper?.querySelector(
          '[data-facets-exposed-range-slider-min]',
        );
        const maxInput = wrapper?.querySelector(
          '[data-facets-exposed-range-slider-max]',
        );

        if (!minInput || !maxInput) {
          return;
        }

        const min = Number.parseFloat(slider.dataset.min);
        const max = Number.parseFloat(slider.dataset.max);
        const step = slider.dataset.step || 1;

        if (Number.isNaN(min) || Number.isNaN(max)) {
          return;
        }

        const values = slider.noUiSlider
          ? slider.noUiSlider.get()
          : [minInput.value, maxInput.value];
        const normalizedValues = Array.isArray(values) ? values : [values];
        const currentMin =
          normalizedValues.length >= 2
            ? Number.parseFloat(normalizedValues[0])
            : parseInput(minInput, min);
        const currentMax = Number.parseFloat(
          normalizedValues[normalizedValues.length - 1],
        );

        if (Number.isNaN(currentMin) || Number.isNaN(currentMax)) {
          return;
        }

        if (
          isDefaultRange(currentMin, currentMax, min, max, getPrecision(step))
        ) {
          minInput.value = '';
          maxInput.value = '';
        }
      });
  }

  /**
   * Removes range-related parameters from the AJAX URL of a submit element.
   *
   * @param {HTMLElement} element
   *   Submit element used by Drupal AJAX.
   * @param {Array<string>} names
   *   Query parameter names to remove.
   */
  function pruneAjaxUrl(element, names) {
    const ajax = Object.values(Drupal.ajax?.instances || {}).find(
      (instance) => {
        return instance?.element === element;
      },
    );

    if (!ajax) {
      return;
    }

    if (typeof ajax.url === 'string') {
      ajax.url = removeQueryParameters(ajax.url, names);
    }

    if (typeof ajax.options?.url === 'string') {
      ajax.options.url = removeQueryParameters(ajax.options.url, names);
    }
  }

  /**
   * Updates one slider handle from manual text input.
   *
   * @param {HTMLElement} slider
   *   noUiSlider element.
   * @param {HTMLInputElement} input
   *   Input field for the handle.
   * @param {number} handle
   *   Handle index (0 for min, 1 for max).
   * @param {number} min
   *   Slider minimum boundary.
   * @param {number} max
   *   Slider maximum boundary.
   * @param {Array<number>} [snapValues=[]]
   *   Snap points when snap mode is enabled.
   */
  function updateHandle(slider, input, handle, min, max, snapValues = []) {
    let value = Number.parseFloat(input.value);

    if (Number.isNaN(value)) {
      value = handle === 0 ? min : max;
    }

    if (snapValues.length >= 2) {
      value = findClosestValue(value, snapValues);
    }

    slider.noUiSlider.setHandle(handle, clamp(value, min, max), true, true);
  }

  Drupal.behaviors.facetsExposedRangeSlider = {
    /**
     * Initializes exposed range sliders within the current behavior context.
     *
     * @param {Document|HTMLElement} context
     *   The render context provided by Drupal behaviors.
     * @param {object} [settings=drupalSettings]
     *   Runtime settings from Drupal.
     */
    attach(context, settings = drupalSettings) {
      if (typeof noUiSlider === 'undefined') {
        return;
      }

      const sliders =
        settings.facets_exposed_filters?.range_sliders ||
        drupalSettings.facets_exposed_filters?.range_sliders ||
        {};

      once(
        'facets-exposed-range-slider',
        '[data-facets-exposed-range-slider]',
        context,
      ).forEach((slider) => {
        const sliderId = slider.getAttribute(
          'data-facets-exposed-range-slider',
        );
        const options = sliders[sliderId] || getDatasetOptions(slider);
        const wrapper = slider.closest('.facets-exposed-range-slider');
        const minInput = wrapper?.querySelector(
          '[data-facets-exposed-range-slider-min]',
        );
        const maxInput = wrapper?.querySelector(
          '[data-facets-exposed-range-slider-max]',
        );

        if (!minInput || !maxInput) {
          return;
        }

        const min = Number.parseFloat(options.min);
        const max = Number.parseFloat(options.max);
        const step = Number.parseFloat(options.step || 1);
        const snapValues = normalizeSnapValues(options.snap_values, min, max);
        const useSnap =
          toBoolean(options.snap_to_values) && snapValues.length >= 2;
        const preserveActiveRange = toBoolean(options.preserve_active_range);

        if (
          Number.isNaN(min) ||
          Number.isNaN(max) ||
          Number.isNaN(step) ||
          max <= min ||
          step <= 0
        ) {
          wrapper?.classList.add('facets-exposed-range-slider--no-range');
          slider.classList.add('facets-exposed-range-slider__slider--no-range');
          const precision = getPrecision(options.step || 1);
          const lockedValue = Number.isNaN(min) ? parseInput(minInput, 0) : min;

          if (Number.isFinite(lockedValue)) {
            const formatted = formatValue(lockedValue, precision);
            minInput.value = formatted;
            maxInput.value = formatted;
          }

          minInput.readOnly = true;
          maxInput.readOnly = true;

          return;
        }

        wrapper?.classList.remove('facets-exposed-range-slider--no-range');
        slider.classList.remove(
          'facets-exposed-range-slider__slider--no-range',
        );
        minInput.readOnly = false;
        maxInput.readOnly = false;

        const precision = useSnap
          ? Math.max(
              getPrecision(options.step || step),
              getValuesPrecision(snapValues),
            )
          : getPrecision(options.step || step);
        const animationDuration = Number.parseInt(options.animate, 10) || 0;
        const defaultMin = normalizeStartValue(
          parseInput(minInput, min),
          min,
          max,
          snapValues,
          useSnap,
        );
        const defaultMax = normalizeStartValue(
          parseInput(maxInput, max),
          min,
          max,
          snapValues,
          useSnap,
        );
        const tooltipFormatter = {
          to(value) {
            return `${options.tooltips_value_prefix || ''}${formatValue(
              value,
              precision,
            )}${options.tooltips_value_suffix || ''}`;
          },
          from(value) {
            return Number.parseFloat(value);
          },
        };
        const sliderOptions = {
          range: useSnap ? buildSnapRange(snapValues) : { min, max },
          start: [defaultMin, defaultMax],
          connect: true,
          animate: animationDuration > 0,
          animationDuration,
          orientation: options.orientation || 'horizontal',
          direction: document.documentElement.dir === 'rtl' ? 'rtl' : 'ltr',
          format: {
            to(value) {
              return formatValue(value, precision);
            },
            from(value) {
              return Number.parseFloat(value);
            },
          },
          tooltips: options.tooltips
            ? [tooltipFormatter, tooltipFormatter]
            : false,
        };

        if (useSnap) {
          sliderOptions.snap = true;
        } else {
          sliderOptions.step = step;
        }

        noUiSlider.create(slider, sliderOptions);

        if (options.tooltips && wrapper) {
          wrapper.classList.add(
            'facets-exposed-range-slider--tooltips-enabled',
          );
        }

        slider.noUiSlider.on('update', (values) => {
          minInput.value = values[0];
          maxInput.value = values[1];

          const currentMin = Number.parseFloat(values[0]);
          const currentMax = Number.parseFloat(values[1]);

          if (
            !Number.isNaN(currentMin) &&
            !Number.isNaN(currentMax) &&
            !preserveActiveRange &&
            isDefaultRange(currentMin, currentMax, min, max, precision)
          ) {
            minInput.value = '';
            maxInput.value = '';
          }
        });

        slider.noUiSlider.on('set', () => {
          const form = slider.closest('form');
          const submit = form?.querySelector('[data-bef-auto-submit-click]');

          if (!submit) {
            return;
          }

          if (!preserveActiveRange) {
            clearDefaultRangeInputs(form);
          }
          pruneAjaxUrl(submit, getRangeInputNames(form));
          submit.click();
        });

        minInput.addEventListener('change', () => {
          updateHandle(slider, minInput, 0, min, max, snapValues);
        });

        maxInput.addEventListener('change', () => {
          updateHandle(slider, maxInput, 1, min, max, snapValues);
        });
      });
    },
  };
})(Drupal, drupalSettings, once);
