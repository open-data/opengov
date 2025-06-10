<?php

namespace Drupal\token_filter\Plugin\CKEditor4To5Upgrade;

use Drupal\ckeditor5\HTMLRestrictions;
use Drupal\ckeditor5\Plugin\CKEditor4To5UpgradePluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\filter\FilterFormatInterface;

/**
 * Token filter plugin for CKEditor 4 to 5 upgrade.
 *
 * @CKEditor4To5Upgrade(
 *   id = "token_filter",
 *   cke4_buttons = {
 *     "tokenbrowser",
 *   },
 *   cke4_plugin_settings = {
 *     "tokenbrowser",
 *   },
 *   cke5_plugin_elements_subset_configuration = {},
 * )
 */
class TokenBrowser extends PluginBase implements CKEditor4To5UpgradePluginInterface {

  /**
   * {@inheritDoc}
   */
  // phpcs:ignore Drupal.NamingConventions.ValidFunctionName.ScopeNotCamelCaps,Drupal.Commenting.FunctionComment.Missing
  public function mapCKEditor4ToolbarButtonToCKEditor5ToolbarItem(
    string $cke4_button,
    HTMLRestrictions $text_format_html_restrictions,
  ): ?array {
    switch ($cke4_button) {
      case 'tokenbrowser':
        return ['tokenBrowser'];

      default:
        throw new \OutOfBoundsException();
    }
  }

  /**
   * {@inheritDoc}
   */
  // phpcs:ignore Drupal.NamingConventions.ValidFunctionName.ScopeNotCamelCaps,Drupal.Commenting.FunctionComment.Missing
  public function mapCKEditor4SettingsToCKEditor5Configuration(
    string $cke4_plugin_id,
    array $cke4_plugin_settings,
  ): ?array {
    switch ($cke4_plugin_id) {
      case 'tokenbrowser':
        return NULL;

      default:
        throw new \OutOfBoundsException();
    }
  }

  /**
   * {@inheritDoc}
   */
  // phpcs:ignore Drupal.NamingConventions.ValidFunctionName.ScopeNotCamelCaps,Drupal.Commenting.FunctionComment.Missing
  public function computeCKEditor5PluginSubsetConfiguration(
    string $cke5_plugin_id,
    FilterFormatInterface $text_format,
  ): ?array {
    throw new \OutOfBoundsException();
  }

}
