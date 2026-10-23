<?php

namespace Drupal\views_bootstrap\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Style plugin to render each item as a row in a Bootstrap Dropdown.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "views_bootstrap_dropdown",
 *   title = @Translation("Bootstrap Dropdown"),
 *   help = @Translation("Displays rows in a Bootstrap Dropdown."),
 *   theme = "views_bootstrap_dropdown",
 *   theme_file = "../views_bootstrap.theme.inc",
 *   display_types = {"normal"}
 * )
 */
class ViewsBootstrapDropdown extends StylePluginBase {
  /**
   * {@inheritdoc}
   */
  protected $usesFields = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $usesGrouping = FALSE;

  /**
   * Whether the config form exposes the class to provide on each row.
   *
   * @var bool
   */
  protected $usesRowClass = TRUE;

  /**
   * Declare the options used to configure this style.
   *
   * Based mostly on \Drupal\views\Plugin\views\style\HtmlList.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['class'] = ['default' => ''];
    $options['wrapper_class'] = ['default' => ''];
    $options['button_text'] = ['default' => 'Select'];
    $options['button_class'] = ['default' => 'btn btn-primary'];
    return $options;
  }

  /**
   * Form for configuring this plugin.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {

    $form['help'] = [
      '#markup' => $this->t('The Bootstrap dropdown style displays a list of links in a drop-down menu format (<a href=":docs">see documentation</a>).', [':docs' => 'https://www.drupal.org/docs/contributed-modules/views-bootstrap-for-bootstrap-3/dropdown']),
      '#weight' => -99,
    ];

    $form['button_text'] = [
      '#title' => $this->t('Button text'),
      '#description' => $this->t('Text label for the button that is the drop-down toggle.'),
      '#type' => 'textfield',
      '#default_value' => $this->options['button_text'],
    ];

    $form['button_class'] = [
      '#title' => $this->t('Button class'),
      '#description' => $this->t('Classes for the button that is the drop-down toggle.'),
      '#type' => 'select',
      '#options' => [
        'btn btn-primary' => $this->t('Primary'),
        'btn btn-secondary' => $this->t('Secondary'),
        'btn btn-success' => $this->t('Success'),
        'btn btn-info' => $this->t('Info'),
        'btn btn-warning' => $this->t('Warning'),
        'btn btn-danger' => $this->t('Danger'),
        'btn btn-light' => $this->t('Light'),
        'btn btn-dark' => $this->t('Dark'),
        'btn btn-link' => $this->t('Link'),
      ],
      '#default_value' => $this->options['button_class'],
    ];

    $form['wrapper_class'] = [
      '#title' => $this->t('Wrapper class'),
      '#description' => $this->t('The class to provide on the wrapper, outside the list.'),
      '#type' => 'textfield',
      '#size' => '30',
      '#default_value' => $this->options['wrapper_class'],
    ];

    $form['class'] = [
      '#title' => $this->t('List class'),
      '#description' => $this->t('The class to provide on the list element itself.'),
      '#type' => 'textfield',
      '#size' => '30',
      '#default_value' => $this->options['class'],
    ];

    parent::buildOptionsForm($form, $form_state);

  }

}
