import { Plugin } from 'ckeditor5/src/core';
import { ButtonView } from 'ckeditor5/src/ui';
import icon from '../../../../icons/tokenBrowser.svg';

export default class TokenBrowserUi extends Plugin {
  init() {
    const drupalSettings = window.drupalSettings;
    const t = this.editor.t;

    this.editor.ui.componentFactory.add('tokenBrowser', (locale) => {
      const command = this.editor.commands.get('tokenBrowser');
      const buttonView = new ButtonView(locale);

      buttonView.set({
        label: t('Token browser'),
        icon,
        tooltip: true,
      });

      buttonView.bind('isOn', 'isEnabled').to(command, 'value', 'isEnabled');

      this.listenTo(buttonView, 'execute', () => {
        drupalSettings.tokenFocusedCkeditor5 = this.editor;
        this.editor.execute('tokenBrowser');
      });

      return buttonView;
    });
  }
}
