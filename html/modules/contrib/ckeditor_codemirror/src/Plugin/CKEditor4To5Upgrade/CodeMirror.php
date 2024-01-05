<?php

namespace Drupal\ckeditor_codemirror\Plugin\CKEditor4To5Upgrade;

use Drupal\ckeditor5\HTMLRestrictions;
use Drupal\ckeditor5\Plugin\CKEditor4To5UpgradePluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\filter\FilterFormatInterface;

/**
 * Provides the CKEditor 4 to 5 upgrade for CKEditor CodeMirror.
 *
 * @CKEditor4To5Upgrade(
 *   id = "codemirror",
 *   cke4_buttons = {},
 *   cke4_plugin_settings = {"codemirror"},
 *   cke5_plugin_elements_subset_configuration = {}
 * )
 */
class CodeMirror extends PluginBase implements CKEditor4To5UpgradePluginInterface {

  /**
   * {@inheritdoc}
   */
  public function mapCkeditor4ToolbarButtonToCkeditor5ToolbarItem(string $cke4_button, HTMLRestrictions $text_format_html_restrictions): ?array {
    throw new \OutOfBoundsException();
  }

  /**
   * {@inheritdoc}
   */
  public function mapCkeditor4SettingsToCkeditor5Configuration(string $cke4_plugin_id, array $cke4_plugin_settings): ?array {
    switch ($cke4_plugin_id) {
      case 'codemirror':
        // Exclude settings no longer supported in CKE5 plugin.
        $settings = [
          'enable' => $cke4_plugin_settings['enable'],
          'mode' => $cke4_plugin_settings['mode'],
          'options' => [
            'autoCloseBrackets' => $cke4_plugin_settings['options']['autoCloseBrackets'],
            'autoCloseTags' => $cke4_plugin_settings['options']['autoCloseTags'],
            'folding' => $cke4_plugin_settings['options']['enableCodeFolding'],
            'lineNumbers' => $cke4_plugin_settings['options']['lineNumbers'],
            'lineWrapping' => $cke4_plugin_settings['options']['lineWrapping'],
            'matchBrackets' => $cke4_plugin_settings['options']['matchBrackets'],
          ],
        ];
        return ['ckeditor_codemirror_source_editing' => $settings];

      default:
        throw new \OutOfBoundsException();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function computeCkeditor5PluginSubsetConfiguration(string $cke5_plugin_id, FilterFormatInterface $text_format): ?array {
    throw new \OutOfBoundsException();
  }

}
