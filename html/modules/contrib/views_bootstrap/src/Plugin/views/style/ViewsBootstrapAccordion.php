<?php

namespace Drupal\views_bootstrap\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Style plugin to render each item as a row in a Bootstrap Accordion.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "views_bootstrap_accordion",
 *   title = @Translation("Bootstrap Accordion"),
 *   help = @Translation("Displays rows in a Bootstrap Accordion."),
 *   theme = "views_bootstrap_accordion",
 *   theme_file = "../views_bootstrap.theme.inc",
 *   display_types = {"normal"}
 * )
 */
class ViewsBootstrapAccordion extends StylePluginBase {
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
   * Definition.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['panel_output'] = ['default' => 'single'];
    $options['panel_title_field'] = ['default' => ''];
    $options['label_field'] = ['default' => NULL];
    $options['flush'] = ['default' => FALSE];
    $options['behavior'] = ['default' => 'closed'];
    $options['sections'] = ['default' => []];

    return $options;
  }

  /**
   * Render the given style.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['help'] = [
      '#markup' => $this->t('The Bootstrap accordion displays content in collapsible panels (<a href=":docs">see documentation</a>).',
        [':docs' => 'https://www.drupal.org/docs/extending-drupal/contributed-modules/contributed-module-documentation/views-bootstrap-for-bootstrap-5/accordion']),
      '#weight' => -99,
    ];

    $form['panel_output'] = [
      '#type' => 'select',
      '#title' => $this->t('Accordion grouping'),
      '#options' => [
        'single' => $this->t('One accordion item per result'),
        'grouped' => $this->t('Accordion content by grouped field'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="style_options[grouping][0][field]"]' => ['filled' => FALSE],
        ],
      ],
      '#description' => $this->t('Select how to organize your content into accordion items: either group by individual field values or aggregate by a field.'),
      '#default_value' => $this->options['panel_output'],
    ];

    $form['group_help_container'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="style_options[panel_output]"]' => ['value' => 'grouped'],
        ],
      ],
    ];

    $form['group_help_container']['group_help'] = [
      '#markup' => $this->t('When grouping content by a specific field, the displayed results depend on the total number of records returned. Please make sure the results include all the entries you expect to see.'),
    ];

    $form['panel_title_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Panel title field'),
      '#empty_option' => $this->t('- Select -'),
      '#options' => $this->displayHandler->getFieldLabels(TRUE),
      '#required' => TRUE,
      '#default_value' => $this->options['panel_title_field'],
      '#description' => $this->t('Select the field that will be used as the accordion panel titles.'),
    ];

    $form['flush'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Flush Borders'),
      '#description' => $this->t('Add accordion-flush class to remove some borders and rounded corners to render accordions edge-to-edge with their parent container.'),
      '#default_value' => $this->options['flush'],
    ];

    $form['behavior'] = [
      '#type' => 'radios',
      '#title' => $this->t('Collapse Options'),
      '#options' => [
        'closed' => $this->t('All Items Closed'),
        'all' => $this->t('All Items Open'),
        'specify' => $this->t('Specify Behavior by Section'),
      ],
      '#required' => TRUE,
      '#description' => $this->t('Default panel state for collapse behavior.'),
      '#default_value' => $this->options['behavior'],
    ];

    $form['sections'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Open Elements'),
      '#options' => [
        'first' => $this->t('First'),
        'middle' => $this->t('Middle'),
        'last' => $this->t('Last'),
      ],
      '#description' => $this->t('Select the elements which will be opened.'),
      '#states' => [
        'visible' => [
          ':input[name="style_options[behavior]"]' => ['value' => 'specify'],
        ],
      ],
      '#default_value' => $this->options['sections'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $errors = parent::validate();
    if (!$this->options['panel_title_field']) {
      $errors[] = $this->t('@style style will not display without the "@field" setting.',
        [
          '@style' => $this->definition['title'],
          '@field' => $this->t('Panel title field'),
        ]
      );
    }
    return $errors;
  }

}
