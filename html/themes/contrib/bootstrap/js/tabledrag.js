/**
 * @file
 * tabledrag.js overrides and functionality extensions.
 */

(($, Drupal) => {
  /**
   * Extends core's Tabledrag functionality.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.bootstrapTableDrag = {
    attach(context, settings) {
      function initTableDrag(table, base) {
        if (table.length) {
          // Create the new tableDrag instance. Save in the Drupal variable
          // to allow other scripts access to the object.
          Drupal.tableDrag[base] = new Drupal.tableDrag(
            table[0],
            settings.tableDrag[base]
          );
        }
      }

      Object.keys(settings.tableDrag || {}).forEach((base) => {
        document
          .getElementById(base)
          .classList.remove('table-bordered');
        document.getElementById(base).classList.add('table-hover');
        initTableDrag(
          $(once('tabledrag', `#${base}`, context)),
          base
        );
      });
    },
  };

  $.extend(
    Drupal.theme,
    /** @lends Drupal.theme */ {
      /**
       * Constructs the table drag changed warning.
       *
       * @return {string}
       *   Markup for the warning.
       */
      tableDragChangedWarning() {
        return `<div class="messages__content container alert alert-warning alert-dismissible fade show" role="alert">${Drupal.theme(
          'tableDragChangedMarker'
        )} ${Drupal.t(
          'You have unsaved changes.'
        )}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></div>`;
      },

      /**
       * The button for toggling table row weight visibility.
       *
       * @return {string}
       *   HTML markup for the weight toggle button and its container.
       */
      tableDragToggle: () =>
        `<div class="tabledrag-toggle-weight-wrapper" data-drupal-selector="tabledrag-toggle-weight-wrapper">
            <button type="button" class="link action-link tabledrag-toggle-weight btn  btn-secondary btn-sm" data-drupal-selector="tabledrag-toggle-weight"></button>
            </div>`,
    }
  );
})(jQuery, Drupal);
