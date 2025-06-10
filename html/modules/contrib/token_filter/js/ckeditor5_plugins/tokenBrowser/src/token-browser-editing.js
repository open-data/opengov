import { Plugin } from 'ckeditor5/src/core';
import TokenBrowserCommand from './token-browser-command';

export default class TokenBrowserEditing extends Plugin {
  init() {
    this.editor.commands.add(
      'tokenBrowser',
      new TokenBrowserCommand(this.editor),
    );
  }
}
