<?php

namespace Drupal\content_type_breadcrumb\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;
use Drupal\views\Views;
use Drupal\taxonomy\Entity\Vocabulary;

class ConfigForm extends ConfigFormBase {

  private $views = [
    'pd_core_ati_details',
    'pd_core_contracts_details',
    'pd_core_grants_details',
    'pd_core_hospitalityq_details',
    'pd_core_inventory_details',
    'pd_core_reclassification_details',
    'pd_core_travela_details',
    'pd_core_travelq_details',
    'pd_core_wrongdoing_details',
    ];

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_type_breadcrumb_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'content_type_breadcrumb.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    try {
      $form['#tree'] = TRUE;
      $config = $this->config('content_type_breadcrumb.settings');

      // Menus
      // @todo support language handling when separate menus are used for each language
      $menus = ['main' => 'Main navigation'];
      if (\Drupal\system\Entity\Menu::load('main-navigation-fr')) {
        $menus['main-navigation-fr'] = 'Main navigation FR';
      }

      // Content types
      $form['node_group'] = [
        '#type' => 'details',
        '#title' => $this->t('Content types'),
      ];

      $contentTypes = NodeType::loadMultiple();
      asort($contentTypes);

      foreach ($contentTypes as $contentType) {
        $id = $contentType->id();
        $parent = $config->get('content_type_breadcrumb.type_' . $id) == ""
          ? 'main:'
          : $config->get('content_type_breadcrumb.type_' . $id);

        $form['node_group']['type_' . $id] = \Drupal::service('menu.parent_form_selector')->parentSelectElement($parent, $config->get('content_type_breadcrumb.type_' . $id), $menus);
        $form['node_group']['type_' . $id]['#title'] = $this->t($contentType->label());
        $form['node_group']['type_' . $id]['#description'] = $this->t('Select the parent menu for content type');
        $form['node_group']['type_' . $id]['#attributes']['class'][] = 'menu-title-select';
      }

      // Views which require custom breadcrumb
      $form['view_group'] = [
        '#type' => 'details',
        '#title' => $this->t('Proactive Disclosure Views'),
      ];

      foreach ($this->views as $view) {
        $id = $view;
        $view = Views::getView($id);
        if ($view) {
          $displays = $view->storage->get('display');

          foreach ($displays as $display) {
            if ($display['display_plugin'] == 'page') {
              $parent = $config->get('content_type_breadcrumb.view_' . $id) == ""
                ? 'main:'
                : $config->get('content_type_breadcrumb.view_' . $id);

              $form['view_group']['view_' . $id] = \Drupal::service('menu.parent_form_selector')->parentSelectElement($parent, $config->get('content_type_breadcrumb.view_' . $id), $menus);
              $form['view_group']['view_' . $id]['#title'] = isset($display['display_title']) ? $this->t($display['display_title']) : $id;
              $form['view_group']['view_' . $id]['#description'] = $this->t('Select the parent menu for view page');
              $form['view_group']['view_' . $id]['#attributes']['class'][] = 'menu-title-select';
            }
          }
        }
      }

      // Taxonomies
      $form['vocabulary_group'] = [
        '#type' => 'details',
        '#title' => $this->t('Vocabulary/ Taxonomy'),
      ];

      $vocabularies = Vocabulary::loadMultiple();
      asort($vocabularies);

      foreach ($vocabularies as $vocabulary) {
        $id = $vocabulary->id();
        $parent = $config->get('content_type_breadcrumb.vocabulary_' . $id) == ""
          ? 'main:'
          : $config->get('content_type_breadcrumb.vocabulary_' . $id);

        $form['vocabulary_group']['vocabulary_' . $id] = \Drupal::service('menu.parent_form_selector')->parentSelectElement($parent, $config->get('content_type_breadcrumb.type_' . $id), $menus);
        $form['vocabulary_group']['vocabulary_' . $id]['#title'] = $this->t($vocabulary->get('name'));
        $form['vocabulary_group']['vocabulary_' . $id]['#description'] = $this->t('Select the parent menu for taxonomy vocabulary');
        $form['vocabulary_group']['vocabulary_' . $id]['#attributes']['class'][] = 'menu-title-select';
      }

      return parent::buildForm($form, $form_state);

    } catch (\Exception $e) {
      \Drupal::logger('content breadcrumb')->error($e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = $this->config('content_type_breadcrumb.settings');

    // get all content types
    $form_node_group = $form_state->getValue('node_group');
    $contentTypes = NodeType::loadMultiple();
    asort($contentTypes);

    foreach ($contentTypes as $contentType) {
      $id = $contentType->id();
      $config_value = $form_node_group['type_' . $id] == 'main:' ? '' : $form_node_group['type_' . $id];
      $config->set('content_type_breadcrumb.' . 'type_' . $id, $config_value);
    }

    // get all views which require custom breadcrumb
    $form_view_group = $form_state->getValue('view_group');
    foreach ($this->views as $view) {
      $id = $view;
      $config_value = $form_view_group['view_' . $id] == 'main:' ? '' : $form_view_group['view_' . $id];
      $config->set('content_type_breadcrumb.' . 'view_' . $id, $config_value);
    }

    // get all vocabularies
    $form_vocabulary_group = $form_state->getValue('vocabulary_group');
    $vocabularies = Vocabulary::loadMultiple();
    asort($vocabularies);

    foreach ($vocabularies as $vocabulary) {
      $id = $vocabulary->id();
      $config_value = $form_vocabulary_group['vocabulary_' . $id] == 'main:' ? '' : $form_vocabulary_group['vocabulary_' . $id];
      $config->set('content_type_breadcrumb.' . 'vocabulary_' . $id, $config_value);
    }

    $config->save();
    parent::submitForm($form, $form_state);

  }
}
