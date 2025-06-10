import { Plugin } from 'ckeditor5/src/core';
import TokenBrowserUi from './token-browser-ui';
import TokenBrowserEditing from './token-browser-editing';

export default class TokenBrowser extends Plugin {
  static get requires() {
    return [TokenBrowserUi, TokenBrowserEditing];
  }
}
