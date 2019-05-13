/**
 * @file
 * JavaScript behaviors for webform wizard.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Tracks the wizard's current page in the URL.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Tracks the wizard's current page in the URL.
   */
  Drupal.behaviors.webformWizardTrackPage = {
    attach: function (context) {
      // Make sure on page load or Ajax refresh the location ?page is correct
      // since conditional logic can skip pages.
      // Note: window.history is only supported by IE 10+.
      if (window.history && window.history.replaceState) {
        // Track the form's current page for 8.5.x and below.
        // @todo Remove the below code once only 8.6.x is supported.
        // @see https://www.drupal.org/project/drupal/issues/2508796
        $('form[data-webform-wizard-current-page]', context)
          .once('webform-wizard-current-page')
          .each(function () {
            trackPage(this);
          });

        // Track the form's current page for 8.6.x and above.
        if ($(context).hasData('webform-wizard-current-page')) {
          trackPage(context);
        }
      }

      // When paging next and back update the URL so that Drupal knows what
      // the expected page name or index is going to be.
      // NOTE: If conditional wizard page logic is configured the
      // expected page name or index may not be accurate.
      $(':button[data-webform-wizard-page], :submit[data-webform-wizard-page]', context).once('webform-wizard-page').on('click', function () {
        var page = $(this).attr('data-webform-wizard-page');
        this.form.action = this.form.action.replace(/\?.+$/, '') + '?page=' + page;
      });

      /**
       * Append the form's current page data attribute to the browser's URL.
       *
       * @param {HTMLElement} form
       *   The form element.
       */
      function trackPage(form) {
        var $form = $(form);
        // Make sure the form is visible before updating the URL.
        if ($form.is(':visible')) {
          var page = $form.attr('data-webform-wizard-current-page');
          var url = window.location.toString().replace(/\?.+$/, '') +
            '?page=' + page;
          window.history.replaceState(null, null, url);
        }
      }
    }
  };

})(jQuery, Drupal);
