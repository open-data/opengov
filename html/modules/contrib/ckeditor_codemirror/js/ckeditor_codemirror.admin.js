/**
 * @file
 * CKEditor CodeMirror plugin admin behavior.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Provides the summary for the CodeMirror plugin settings vertical tab.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches summary behaviour to the CodeMirror settings vertical tab.
   */

  Drupal.behaviors.ckeditorCodeMirrorSettingsSummary = {
    attach: function () {
      $('[data-ckeditor5-plugin-id="ckeditor_codemirror_source_editing"]').drupalSetSummary(function (context) {
        var $enable = $('input[name="editor[settings][plugins][ckeditor_codemirror_source_editing][enable]"]');
        var $mode = $('select[name="editor[settings][plugins][ckeditor_codemirror_source_editing][enable]"]');

        if (!$enable.is(':checked')) {
          return Drupal.t('Syntax highlighting <strong>disabled</strong>.');
        }

        var output = '';
        output += Drupal.t('Syntax highlighting <strong>enabled</strong>.');

        if ($mode.length) {
          var mode_name = 'Unknown';
          switch ($mode.val()) {
            case 'htmlmixed':
              mode_name = 'HTML';
              break;

            case 'application/x-httpd-php':
              mode_name = 'PHP';
              break;

            case 'text/javascript':
              mode_name = 'Javascript';
              break;
          }
          output += '<br />' + Drupal.t('Mode: ') + mode_name;
        }

        return output;
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
