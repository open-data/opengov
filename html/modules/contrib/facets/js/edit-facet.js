/**
 * @file
 * UX improvements for the facet edit form.
 */

(function ($) {
  Drupal.behaviors.facetsEditForm = {
    attach: (context, settings) => {
      $('.facet-source-field-wrapper select').change(function () {
        let defaultName = $(this).find('option:selected')[0].textContent;
        defaultName = defaultName.replace(/(\s\((?!.*\().*\))/g, '');
        $('#edit-name')[0].value = defaultName;
        setTimeout(function () {
          $('#edit-name').trigger('change');
        }, 100);
      });
    },
  };
})(jQuery);
