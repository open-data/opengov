/**
 * @file
 * JavaScript behaviors for computed elements.
 */

(function ($, Drupal, debounce) {

  'use strict';

  Drupal.webform = Drupal.webform || {};
  Drupal.webform.computed = Drupal.webform.computed || {};
  Drupal.webform.computed.delay = Drupal.webform.computed.delay || 500;

  /**
   * Initialize computed elements.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformComputed = {
    attach: function (context) {
      $(context).find('.js-webform-computed').once('webform-computed').each(function () {
        // Get computed element and parent form.
        var $element = $(this);
        var $form = $element.closest('form');

        // Get elements that are used by the computed element.
        var elementKeys = $(this).data('webform-element-keys').split(',');
        if (!elementKeys) {
          return;
        }

        // Get computed element triggers.
        var inputs = [];
        $.each(elementKeys, function (i, key) {
          // Exact input match.
          inputs.push(':input[name="' + key + '"]');
          // Sub inputs. (aka #tree)
          inputs.push(':input[name^="' + key + '["]');
        });
        var $triggers = $form.find(inputs.join(','));

        // Add event handler to computed element triggers.
        $triggers.on('keyup change',
          debounce(triggerUpdate, Drupal.webform.computed.delay));

        // Initialize computed element update which refreshes the displayed
        // value and accounts for any changes to the #default_value for a
        // computed element.
        triggerUpdate(true);

        function triggerUpdate(initialize) {
          // Prevent duplicate computations.
          // @see Drupal.behaviors.formSingleSubmit
          if (initialize !== true) {
            var formValues = $triggers.serialize();
            var previousValues = $element.attr('data-webform-computed-last');
            if (previousValues === formValues) {
              return;
            }
            $element.attr('data-webform-computed-last', formValues);
          }

          // Add loading class to computed wrapper.
          $element.find('.js-webform-computed-wrapper')
            .addClass('webform-computed-loading');

          // Trigger computation.
          $element.find('.js-form-submit').mousedown();
        }
      });
    }
  };

})(jQuery, Drupal, Drupal.debounce);
