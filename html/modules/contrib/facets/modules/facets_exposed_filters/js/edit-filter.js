/**
 * @file
 * Attaches show/hide functionality to checkboxes in the "Processor" tab.
 */

(function ($) {
  Drupal.behaviors.facetsIndexFormatter = {
    attach: (context, settings) => {
      $('input.form-checkbox[data-processor-id]', context).each(function () {
        const checkbox = this;
        const $checkbox = $(checkbox);
        const processorId = $checkbox.data('processor-id');

        const $rows = $(
          `.search-api-processor-weight--${processorId}`,
          context,
        );

        // Bind a click handler to this checkbox to conditionally show and hide the processor's table row.
        $checkbox.on('click.updateProcessorState', function () {
          if (checkbox.matches(':checked')) {
            $rows.show();
          } else {
            $rows.hide();
          }
        });

        // Trigger our bound click handler to update elements to initial state.
        $checkbox.triggerHandler('click.updateProcessorState');
      });
    },
  };
})(jQuery);
