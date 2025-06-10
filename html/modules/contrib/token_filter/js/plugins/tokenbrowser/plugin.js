/**
 * @file
 * Drupal token browser plugin.
 */

(function ($, Drupal, CKEDITOR) {
  CKEDITOR.plugins.add('tokenbrowser', {
    // The plugin initialization logic goes inside this method.
    beforeInit(editor) {
      // Generic command.
      editor.addCommand('edittokenbrowser', {
        modes: { wysiwyg: 1 },
        canUndo: true,
        exec(editor, data) {
          data = data || {};

          // We have no current existingValues.
          const existingValues = {};

          // Set all options for the model.
          const dialogOptions = {
            dialogClass: 'token-browser-dialog',
            autoResize: false,
            modal: false,
            draggable: true,
          };
          const dialogSettings = drupalSettings.dialog;

          // We have no current saveCallback.
          const saveCallback = function (values) {};

          // Set the active CKEditor id.
          Drupal.ckeditorActiveId = editor.name;

          // Open token browser dialog.
          Drupal.ckeditor.openDialog(
            editor,
            data.link,
            existingValues,
            saveCallback,
            dialogOptions,
          );
        },
      });

      // Register the toolbar buttons.
      if (editor.ui.addButton) {
        Object.keys(editor.config.TokenBrowser_buttons).forEach(function (key) {
          const button = editor.config.TokenBrowser_buttons[key];
          editor.ui.addButton(button.id, {
            label: button.label,
            data: button,
            click(editor) {
              editor.execCommand('edittokenbrowser', this.data);
            },
            icon: button.image,
          });
        });
      }
    },
  });
  // eslint-disable-next-line no-undef
})(jQuery, Drupal, CKEDITOR);
