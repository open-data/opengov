/**
 * @file
 * Enhances Drupal's table drag behavior.
 */

(function ($, Drupal, once) {
  if (!Drupal.behaviors.tableDrag) {
    return;
  }

  /**
   * Enhances Webform's drag-and-drop table rows with field manipulation features.
   *
   * Adds a changed warning when an input element (e.g., checkbox, select,
   * or textarea) in a drag-and-drop table is modified.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformAdminTableDrag = {
    attach(context, settings) {
      // Iterate through all tableDrag configurations for the current context.
      Object.keys(settings.tableDrag || {}).forEach(function (base) {
        // Ensure behavior is applied only once per table.
        $(once('webform-tabledrag', '#' + base, context))
          .on('change', function (event) {
            // Check if the changed element is an input, select, or textarea.
            if (event.target.tagName.toLowerCase() !== 'input' &&
              event.target.tagName.toLowerCase() !== 'select' &&
              event.target.tagName.toLowerCase() !== 'textarea'
            ) {
              return;
            }

            // Locate the closest table and row containing the changed element.
            var table = event.target.closest('table.draggable-table');
            var row = event.target.closest('tr.draggable');
            if (!table || !row) {
              return;
            }

            // Retrieve the tableDrag instance and initialize a new row object.
            var tableDrag = Drupal.tableDrag[table.id];
            var rowObject = new tableDrag.row(row, 'mouse', tableDrag.indentEnabled, tableDrag.maxDepth, true);

            // Mark the table row as changed and display a warning.
            rowObject.markChanged();
            rowObject.addChangedWarning();
          });
      });
    }
  };

  // Inject CSS to display previously hidden elements in drag-and-drop tables.
  $(function () {
    $('head').append('<style type="text/css">.webform-tabledrag-hide {display: table-cell;}</style>');
  });

})(jQuery, Drupal, once);
