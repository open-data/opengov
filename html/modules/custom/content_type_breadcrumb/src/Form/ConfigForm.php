<?php

namespace Drupal\content_type_breadcrumb\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class ConfigForm extends ConfigFormBase {

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

      // get all content types
      $contentTypes = \Drupal::service('entity.manager')->getStorage('node_type')->loadMultiple();
      asort($contentTypes);

      foreach ($contentTypes as $contentType) {
        $id = $contentType->id();
        $parent = $config->get('content_type_breadcrumb.type_' . $id) == "" ? 'main:' : $config->get('content_type_breadcrumb.type_' . $id);

        $form['type_' . $id] = \Drupal::service('menu.parent_form_selector')->parentSelectElement($parent, $config->get('content_type_breadcrumb.type_' . $id), ['main' => 'Main navigation']);
        $form['type_' . $id]['#title'] = $this->t($contentType->label());
        $form['type_' . $id]['#description'] = $this->t('Select the parent menu for content type');
        $form['type_' . $id]['#attributes']['class'][] = 'menu-title-select';
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
    $contentTypes = \Drupal::service('entity.manager')->getStorage('node_type')->loadMultiple();
    asort($contentTypes);

    foreach ($contentTypes as $contentType) {
      $id = $contentType->id();
      $config_value = $form_state->getValue('type_' . $id) == 'main:' ? '' : $form_state->getValue('type_' . $id);
      $config->set('content_type_breadcrumb.' . 'type_' . $id, $config_value);
    }

    $config->save();
    parent::submitForm($form, $form_state);
  }
}