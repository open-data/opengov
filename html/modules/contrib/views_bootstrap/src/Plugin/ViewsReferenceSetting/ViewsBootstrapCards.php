<?php

namespace Drupal\views_bootstrap\Plugin\ViewsReferenceSetting;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views\ViewExecutable;
use Drupal\viewsreference\Plugin\ViewsReferenceSettingInterface;

/**
 * The views reference setting Bootstrap Cards plugin.
 *
 * @ViewsReferenceSetting(
 *   id = "bootstrap_cards",
 *   label = @Translation("Bootstrap Cards Settings"),
 *   default_value = "",
 * )
 */
class ViewsBootstrapCards extends PluginBase implements ViewsReferenceSettingInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function alterFormField(array &$form_field) {
    $form_field['#type'] = 'details';
    $form_field['#title'] = $this->t('Bootstrap Cards Settings');
    $form_field['#description'] = $this->t('Override card layout settings for this view reference. <strong>Important:</strong> Only applies to views using Bootstrap Cards display style. Will be ignored for other view styles.');
    $form_field['#weight'] = 31;
    $form_field['#tree'] = TRUE;
    $form_field['#open'] = FALSE;

    // Card group setting.
    $form_field['card_group'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use card groups'),
      '#description' => $this->t('Use card groups to render cards as a single, attached element with equal width and height columns.'),
      '#default_value' => $form_field['#default_value']['card_group'] ?? FALSE,
    ];

    // Maximum cards per row setting.
    $form_field['columns'] = [
      '#type' => 'select',
      '#title' => $this->t('Maximum cards per row'),
      '#description' => $this->t('The number of cards to include in a row.'),
      '#options' => [
        0 => $this->t('No override (use view default)'),
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
        12 => 12,
      ],
      '#default_value' => $form_field['#default_value']['columns'] ?? 0,
    ];

    // Custom classes.
    $form_field['row_class_custom'] = [
      '#title' => $this->t('Custom row wrapper class'),
      '#description' => $this->t('Additional classes to provide on the row wrapping div. Separated by a space.'),
      '#type' => 'textfield',
      '#default_value' => $form_field['#default_value']['row_class_custom'] ?? '',
      '#states' => [
        'visible' => [
          ':input[name$="[card_group]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form_field['col_class_custom'] = [
      '#title' => $this->t('Custom col wrapper class'),
      '#description' => $this->t('Additional classes to provide on the col wrapping div. Separated by a space.'),
      '#type' => 'textfield',
      '#default_value' => $form_field['#default_value']['col_class_custom'] ?? '',
      '#states' => [
        'visible' => [
          ':input[name$="[card_group]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form_field['card_group_class_custom'] = [
      '#title' => $this->t('Custom card group class'),
      '#description' => $this->t('Additional classes to provide on the card group. Separated by a space.'),
      '#type' => 'textfield',
      '#default_value' => $form_field['#default_value']['card_group_class_custom'] ?? '',
      '#states' => [
        'visible' => [
          ':input[name$="[card_group]"]' => ['checked' => TRUE],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function alterView(ViewExecutable $view, $value) {
    // Only apply if this is a Bootstrap Cards style and we have override
    // values.
    if (!empty($value) && is_array($value)) {
      $style_plugin = $view->getStyle();

      // Check if this is a Bootstrap Cards style plugin.
      if ($style_plugin && $style_plugin->getPluginId() === 'views_bootstrap_cards') {
        $style_options = $style_plugin->options;

        // Override card_group if provided.
        if (isset($value['card_group'])) {
          $style_options['card_group'] = $value['card_group'];
        }

        // Override columns if provided.
        if (!empty($value['columns']) && $value['columns'] != 0) {
          $style_options['columns'] = $value['columns'];
        }

        // Override custom classes if provided.
        if (!empty($value['row_class_custom'])) {
          $style_options['row_class_custom'] = $value['row_class_custom'];
        }

        if (!empty($value['col_class_custom'])) {
          $style_options['col_class_custom'] = $value['col_class_custom'];
        }

        if (!empty($value['card_group_class_custom'])) {
          $style_options['card_group_class_custom'] = $value['card_group_class_custom'];
        }

        // Update the style plugin options.
        $style_plugin->options = $style_options;
      }
    }
  }

}
