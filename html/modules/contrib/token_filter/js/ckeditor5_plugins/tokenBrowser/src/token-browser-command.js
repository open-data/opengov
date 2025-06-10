import { Command } from 'ckeditor5/src/core';

export default class TokenBrowserCommand extends Command {
  execute() {
    Drupal.ckeditor5.openDialog(
      this.editor.config.get('drupalTokenBrowser').url,
      // no save callback is needed because of the click-insert functionality of the token tree
      () => {},
      {
        dialogClass: 'token-browser-dialog',
        autoResize: false,
        modal: false,
        draggable: true,
      },
    );
  }
}
