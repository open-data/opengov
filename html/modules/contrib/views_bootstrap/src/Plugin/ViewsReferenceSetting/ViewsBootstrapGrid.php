<?php

namespace Drupal\views_bootstrap\Plugin\ViewsReferenceSetting;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views\ViewExecutable;
use Drupal\viewsreference\Plugin\ViewsReferenceSettingInterface;
use Drupal\views_bootstrap\ViewsBootstrap;

/**
 * The views reference setting Bootstrap Grid plugin.
 *
 * @ViewsReferenceSetting(
 *   id = "bootstrap_grid",
 *   label = @Translation("Bootstrap Grid Settings"),
 *   default_value = "",
 * )
 */
class ViewsBootstrapGrid extends PluginBase implements ViewsReferenceSettingInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function alterFormField(array &$form_field) {
    $form_field['#type'] = 'details';
    $form_field['#title'] = $this->t('Bootstrap Grid Settings');
    $form_field['#description'] = $this->t('Override grid column settings for this view reference. <strong>Important:</strong> Only applies to views using Bootstrap Grid or Cards display styles. Will be ignored for other view styles.');
    $form_field['#weight'] = 30;
    $form_field['#tree'] = TRUE;
    $form_field['#open'] = FALSE;

    // Add grid class override field.
    $form_field['grid_class'] = [
      '#title' => $this->t('Grid row custom class'),
      '#description' => $this->t('Additional classes to provide on the grid row. Separated by a space.'),
      '#type' => 'textfield',
      '#size' => '30',
      '#default_value' => $form_field['#default_value']['grid_class'] ?? '',
    ];

    // Add breakpoint column settings.
    foreach (ViewsBootstrap::getBreakpoints() as $breakpoint) {
      $breakpoint_option = "col_$breakpoint";
      $prefix = ViewsBootstrap::getColumnPrefix($breakpoint);

      $form_field[$breakpoint_option] = [
        '#type' => 'select',
        '#title' => $this->t("Column width at @breakpoint breakpoint", ['@breakpoint' => $breakpoint ?: '']),
        '#default_value' => $form_field['#default_value'][$breakpoint_option] ?? 'none',
        '#description' => $this->t("Set the number of columns each item should take up at the @breakpoint breakpoint and higher.", ['@breakpoint' => $breakpoint ?: '']),
        '#options' => [
          'none' => $this->t('No override (use view default)'),
          $prefix => $this->t('Equal'),
          $prefix . '-auto' => $this->t('Fit to content'),
        ],
      ];

      foreach ([1, 2, 3, 4, 6, 12] as $width) {
        $columns_per_row = $width <= 12 ? 12 / $width : 1;
        $form_field[$breakpoint_option]['#options'][$prefix . "-$width"] = $this->formatPlural(
          $columns_per_row,
          '@width (@count column per row)',
          '@width (@count columns per row)',
          ['@width' => $width]
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function alterView(ViewExecutable $view, $value) {
    // Only apply if this is a Bootstrap Grid style and we have override values.
    if (!empty($value) && is_array($value)) {
      $style_plugin = $view->getStyle();

      // Check if this is a supported Bootstrap style plugin.
      if ($style_plugin && in_array($style_plugin->getPluginId(), ['views_bootstrap_grid', 'views_bootstrap_cards'])) {
        $style_options = $style_plugin->options;

        // Override grid_class if provided.
        if (!empty($value['grid_class'])) {
          // For Grid plugin, use 'grid_class'. For Cards, might use
          // different option.
          if ($style_plugin->getPluginId() === 'views_bootstrap_grid') {
            $style_options['grid_class'] = $value['grid_class'];
          }
          elseif ($style_plugin->getPluginId() === 'views_bootstrap_cards') {
            $style_options['row_class_custom'] = $value['grid_class'];
          }
        }

        // Override breakpoint column settings if provided (only for Grid
        // plugin).
        if ($style_plugin->getPluginId() === 'views_bootstrap_grid') {
          foreach (ViewsBootstrap::getBreakpoints() as $breakpoint) {
            $breakpoint_option = "col_$breakpoint";
            if (!empty($value[$breakpoint_option]) && $value[$breakpoint_option] !== 'none') {
              $style_options[$breakpoint_option] = $value[$breakpoint_option];
            }
          }
        }

        // Update the style plugin options.
        $style_plugin->options = $style_options;
      }
    }
  }

}
