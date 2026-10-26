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
 *   id = "views_bootstrap_list_group",
 *   title = @Translation("Bootstrap List Group"),
 *   help = @Translation("Displays rows in a Bootstrap List Group."),
 *   theme = "views_bootstrap_list_group",
 *   theme_file = "../views_bootstrap.theme.inc",
 *   display_types = {"normal"}
 * )
 */
class ViewsBootstrapListGroup extends StylePluginBase {

  /**
   * Overrides \Drupal\views\Plugin\views\style\StylePluginBase::usesRowPlugin.
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
    $options['list_group_class_custom'] = ['default' => NULL];
    $options['title_field'] = ['default' => ''];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['help'] = [
      '#markup' => $this->t('The Bootstrap list group displays content in an unordered list with list group classes (<a href=":docs">see documentation</a>).',
        [':docs' => 'https://www.drupal.org/docs/extending-drupal/contributed-modules/contributed-module-documentation/views-bootstrap-for-bootstrap-5/list-group']),
      '#weight' => -99,
    ];

    $form['list_group_class_custom'] = [
      '#title' => $this->t('Custom list group class'),
      '#description' => $this->t('Additional classes to provide on the list group. Separated by a space.'),
      '#type' => 'textfield',
      '#default_value' => $this->options['list_group_class_custom'],
      '#weight' => 1,
    ];

    $form['row_class']['#weight'] = 2;

    $form['title_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Title field'),
      '#empty_option' => $this->t('- None -'),
      '#options' => $this->displayHandler->getFieldLabels(TRUE),
      '#required' => FALSE,
      '#default_value' => $this->options['title_field'],
      '#description' => $this->t('Select the field that will be used as the title.'),
    ];

  }

}
