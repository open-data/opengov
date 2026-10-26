/**
 * @file
 * JavaScript behaviors for color element integration.
 */

(function ($, Drupal, once) {
  /**
   * Enhance HTML5 color element.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformColor = {
    attach(context) {
      $(
        once('webform-color', '.form-color:not(.form-color-output)', context),
      ).each(function () {
        const $element = $(this);
        const id = `${$element.attr('id')}--output`;
        // Display color input's output w/ visually-hidden label to
        // the end user.
        const $output = $('<input/>')
          .addClass('form-color-output js-webform-input-mask')
          .addClass($element.attr('class'))
          .attr('data-inputmask-mask', '\\#######')
          .attr('id', id);
        const $label = $element
          .parent('.js-form-type-color')
          .find('label')
          .clone();
        $label.attr({ for: id, class: 'visually-hidden' });
        if ($.fn.inputmask) {
          $output.inputmask();
        }
        $output[0].value = $element[0].value;
        $element.after($output).after($label).css({ float: 'left' });

        // Sync $element and $output.
        $element.on('input', function () {
          $output[0].value = $element[0].value;
        });
        $output.on('input', function () {
          $element[0].value = $output[0].value;
        });
      });
    },
  };
})(jQuery, Drupal, once);
