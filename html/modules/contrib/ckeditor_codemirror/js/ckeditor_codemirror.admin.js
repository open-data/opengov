/**
 * @file
 * CKEditor 'codemirror' plugin admin behavior.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Provides the summary for the "codemirror" plugin settings vertical tab.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches summary behaviour to the "codemirror" settings vertical tab.
   */

  Drupal.behaviors.ckeditorCodeMirrorSettingsSummary = {
    attach: function () {
      $('[data-ckeditor-plugin-id="codemirror"]').drupalSetSummary(function (context) {
        var $enable = $('input[name="editor[settings][plugins][codemirror][enable]"]');
        var $startupMode = $('select[name="editor[settings][plugins][codemirror][startupMode]"]');
        var $mode = $('select[name="editor[settings][plugins][codemirror][mode]"]');
        var $theme = $('select[name="editor[settings][plugins][codemirror][theme]"]');

        if (!$enable.is(':checked')) {
          return Drupal.t('Syntax highlighting <strong>disabled</strong>.');
        }

        var output = '';
        output += Drupal.t('Syntax highlighting <strong>enabled</strong>.');

        if ($startupMode.length) {
          var startupMode = 'Unknown';
          switch ($startupMode.val()) {
            case 'wysiwyg':
              startupMode = 'WYSIWYG';
              break;
            case 'source':
              startupMode = 'Source';
              break;
          }
          output += '<br />' + Drupal.t('Startup: ') + startupMode;
        }

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
        if ($theme.length) {
          output += '<br />' + Drupal.t('Theme: ') + $theme.val();
        }
        return output;
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
