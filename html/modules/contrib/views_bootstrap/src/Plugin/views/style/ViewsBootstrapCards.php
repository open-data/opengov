<?php

namespace Drupal\views_bootstrap\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Style plugin to render each item in an ordered or unordered list.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "views_bootstrap_cards",
 *   title = @Translation("Bootstrap Cards"),
 *   help = @Translation("Displays rows in a Bootstrap Card Group layout"),
 *   theme = "views_bootstrap_cards",
 *   theme_file = "../views_bootstrap.theme.inc",
 *   display_types = {"normal"}
 * )
 */
class ViewsBootstrapCards extends StylePluginBase {
  /**
   * Does the style plugin for itself support to add fields to it's output.
   *
   * @var bool
   */
  protected $usesFields = TRUE;

  /**
   * Does the style plugin allows to use style plugins.
   *
   * @var bool
   */
  protected $usesRowPlugin = TRUE;

  /**
   * Overrides \Drupal\views\Plugin\views\style\StylePluginBase::usesRowClass.
   *
   * @var bool
   */
  protected $usesRowClass = TRUE;

  /**
   * Definition.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    unset($options['grouping']);
    $options['display'] = ['default' => 'fields'];
    $options['card_title_field'] = ['default' => NULL];
    $options['card_content_field'] = ['default' => NULL];
    $options['card_image_field'] = ['default' => NULL];
    $options['card_group'] = ['default' => FALSE];
    $options['card_group_class_custom'] = ['default' => NULL];
    $options['row_class_custom'] = ['default' => NULL];
    $options['col_class_custom'] = ['default' => NULL];
    $options['columns'] = ['default' => 1];
    $options['card_clickable'] = ['default' => FALSE];
    $options['card_link_field'] = ['default' => NULL];
    return $options;
  }

  /**
   * Render the given style.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    unset($form['grouping']);
    $form['help'] = [
      '#markup' => $this->t('The Bootstrap cards displays content in a flexible container for a lead image with text (<a href=":docs">see documentation</a>).',
        [':docs' => 'https://www.drupal.org/docs/extending-drupal/contributed-modules/contributed-module-documentation/views-bootstrap-for-bootstrap-5/cards']),
      '#weight' => -99,
    ];

    if ($this->usesFields()) {
      $form['display'] = [
        '#type' => 'radios',
        '#title' => $this->t('Content display'),
        '#options' => [
          'fields' => $this->t('Select by fields'),
          'content' => $this->t('Display row content'),
        ],
        '#description' => $this->t('Displaying as row content will output the rendered content in the card body.'),
        '#default_value' => $this->options['display'],
      ];

      $form['card_title_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Card title field'),
        '#options' => $this->displayHandler->getFieldLabels(TRUE),
        '#empty_option' => $this->t('- None -'),
        '#required' => FALSE,
        '#states' => [
          'visible' => [
            ':input[name="style_options[display]"]' => ['value' => 'fields'],
          ],
        ],
        '#default_value' => $this->options['card_title_field'],
        '#description' => $this->t('Select the field that will be used for the card title.'),
        '#weight' => 1,
      ];

      $form['card_content_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Card content field'),
        '#options' => $this->displayHandler->getFieldLabels(TRUE),
        '#empty_option' => $this->t('- None -'),
        '#required' => FALSE,
        '#states' => [
          'required' => [
            ':input[name="style_options[display]"]' => ['value' => 'fields'],
          ],
          'visible' => [
            ':input[name="style_options[display]"]' => ['value' => 'fields'],
          ],
        ],
        '#default_value' => $this->options['card_content_field'],
        '#description' => $this->t('Select the field that will be used for the card content.'),
        '#weight' => 2,
      ];

      $form['card_image_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Card image field'),
        '#options' => $this->displayHandler->getFieldLabels(TRUE),
        '#empty_option' => $this->t('- None -'),
        '#required' => FALSE,
        '#states' => [
          'required' => [
            ':input[name="style_options[display]"]' => ['value' => 'fields'],
          ],
          'visible' => [
            ':input[name="style_options[display]"]' => ['value' => 'fields'],
          ],
        ],
        '#default_value' => $this->options['card_image_field'],
        '#description' => $this->t('Select the field that will be used for the card image.'),
        '#weight' => 3,
      ];
    }

    $form['card_group'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use card groups'),
      '#description' => $this->t('Use card groups to render cards as a single, attached element with equal width and height columns. Card groups start off stacked and use display: flex; to become attached with uniform dimensions starting at the sm breakpoint.'),
      '#default_value' => $this->options['card_group'],
    ];

    $form['row_class_custom'] = [
      '#title' => $this->t('Custom row wrapper class'),
      '#description' => $this->t('Additional classes to provide on the row wrapping div. Separated by a space.'),
      '#type' => 'textfield',
      '#default_value' => $this->options['row_class_custom'],
      '#states' => [
        'visible' => [
          ':input[name="style_options[card_group]"]' => ['checked' => FALSE],
        ],
      ],
      '#weight' => 4,
    ];

    $form['col_class_custom'] = [
      '#title' => $this->t('Custom col wrapper class'),
      '#description' => $this->t('Additional classes to provide on the col wrapping div. Separated by a space.'),
      '#type' => 'textfield',
      '#default_value' => $this->options['col_class_custom'],
      '#states' => [
        'visible' => [
          ':input[name="style_options[card_group]"]' => ['checked' => FALSE],
        ],
      ],
      '#weight' => 4,
    ];

    $form['card_group_class_custom'] = [
      '#title' => $this->t('Custom card group class'),
      '#description' => $this->t('Additional classes to provide on the card group. Separated by a space.'),
      '#type' => 'textfield',
      '#default_value' => $this->options['card_group_class_custom'],
      '#states' => [
        'visible' => [
          ':input[name="style_options[card_group]"]' => ['checked' => TRUE],
        ],
      ],
      '#weight' => 4,
    ];

    $form['row_class']['#title'] = $this->t('Custom card class');
    $form['row_class']['#weight'] = 5;

    $form['card_clickable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable clickable cards'),
      '#description' => $this->t("Enables support for clickable cards using Bootstrap's stretched-link utility. You can either select a link field below to have the stretched-link class automatically applied, or manually add the stretched-link class to any link within your card content fields."),
      '#default_value' => $this->options['card_clickable'],
      '#states' => [
        'visible' => [
          ':input[name="style_options[display]"]' => ['value' => 'fields'],
        ],
      ],
      '#weight' => 5,
    ];

    if ($this->usesFields()) {
      $form['card_link_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Card link field (optional)'),
        '#options' => $this->displayHandler->getFieldLabels(TRUE),
        '#empty_option' => $this->t('- None -'),
        '#required' => FALSE,
        '#states' => [
          'visible' => [
            ':input[name="style_options[display]"]' => ['value' => 'fields'],
            ':input[name="style_options[card_clickable]"]' => ['checked' => TRUE],
          ],
        ],
        '#default_value' => $this->options['card_link_field'],
        '#description' => $this->t('Optional: Select a field containing a link to automatically have the stretched-link class applied. If left empty, you can manually add the stretched-link class to any link in your card title or content fields. The link must be directly in the card-body (not wrapped in other divs) for stretched-link to work. To add additional classes, edit the field under "Style settings" > "Customize field HTML" > "CSS class".'),
        '#weight' => 5.5,
      ];
    }

    $form['columns'] = [
      '#type' => 'select',
      '#title' => $this->t('Maximum cards per row'),
      '#description' => $this->t('The number of cards to include in a row.'),
      '#options' => [
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
      '#default_value' => $this->options['columns'],
      '#weight' => 6,
    ];
  }

}
