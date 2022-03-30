/**
 * @file
 * Drupal Entity embed plugin.
 */

(function ($, Drupal, CKEDITOR) {

  "use strict";

  CKEDITOR.plugins.add('tokenbrowser', {

    // The plugin initialization logic goes inside this method.
    beforeInit: function (editor) {

      // Generic command.
      editor.addCommand('edittokenbrowser', {
        modes: {wysiwyg: 1},
        canUndo: true,
        exec: function (editor, data) {
          data = data || {};

          // We have no current existingValues.
          var existingValues = {};

          // Set all options for the model.
          var dialogOptions = {
            dialogClass: 'token-browser-dialog',
            autoResize: false,
            modal: false,
            draggable: true,
          };
          var dialogSettings = drupalSettings.dialog;

          // We have no current saveCallback.
          var saveCallback = function (values) {};

          // Set the active CKEditor id.
          Drupal.ckeditorActiveId = editor.name;

          // Open token browser dialog.
          Drupal.ckeditor.openDialog(editor, data.link, existingValues, saveCallback, dialogOptions);

        }
      });

      // Register the toolbar buttons.
      if (editor.ui.addButton) {
        for (var key in editor.config.TokenBrowser_buttons) {
          var button = editor.config.TokenBrowser_buttons[key];
          editor.ui.addButton(button.id, {
            label: button.label,
            data: button,
            click: function (editor) {
              editor.execCommand('edittokenbrowser', this.data);
            },
            icon: button.image
          });
        }
      }
    }
  });

})(jQuery, Drupal, CKEDITOR);
