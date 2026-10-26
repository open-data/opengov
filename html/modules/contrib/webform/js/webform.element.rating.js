/**
 * @file
 * JavaScript behaviors for RateIt integration.
 */

(function ($, Drupal, once) {
  // All options can be override using custom data-* attributes.
  // @see https://github.com/gjunge/rateit.js/wiki#options.

  /**
   * Remove unsafe RateIt backing field selectors and return the backing input.
   *
   * @param {jQuery} $rateit
   *   A RateIt element.
   *
   * @return {jQuery}
   *   The backing input, when available.
   */
  function sanitizeRateItBackingField($rateit) {
    const backingFieldSelector = $rateit.attr('data-rateit-backingfld');
    if (!backingFieldSelector) {
      return $();
    }

    try {
      const backingField = document.querySelector(backingFieldSelector);
      if (backingField) {
        return $(backingField);
      }
    } catch {
      // Remove invalid selectors below.
    }

    $rateit
      .removeAttr('data-rateit-backingfld')
      .removeData('rateitBackingfld');
    return $();
  }

  if ($.fn.rateit && !$.fn.rateit.webformRateIt) {
    const originalRateIt = $.fn.rateit;

    /**
     * Wrap RateIt to prevent unsafe backing field selector initialization.
     *
     * @param {...any} parameters
     *   RateIt parameters.
     *
     * @return {jQuery|mixed}
     *   The original RateIt return value.
     */
    $.fn.rateit = function (...parameters) {
      this.each(function () {
        sanitizeRateItBackingField($(this));
      });

      return originalRateIt.apply(this, parameters);
    };
    $.fn.rateit.webformRateIt = true;
  }

  /**
   * Initialize rating element using RateIt.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformRating = {
    attach(context) {
      $(once('webform-rating', '[data-rateit-backingfld]', context)).each(
        function () {
          const $rateit = $(this);
          const $input = sanitizeRateItBackingField($rateit);
          if (!$.fn.rateit) {
            $rateit.remove();
            $input.removeClass('js-webform-visually-hidden');
            return;
          }

          // Rateit only initialize inputs on load.
          if (document.readyState === 'complete') {
            $rateit.rateit();
          } else {
            window.setTimeout(function () {
              $rateit.rateit();
            });
          }

          // Update the RateIt widget when the input's value has changed.
          // @see webform.states.js
          $input.on('change', function () {
            $rateit.rateit('value', $input.val());
          });

          // Set RateIt widget to be readonly when the input is disabled.
          // @see webform.states.js
          $input.on('webform:disabled', function () {
            $rateit.rateit('readonly', $input.is(':disabled'));
          });
        },
      );
    },
  };
})(jQuery, Drupal, once);
