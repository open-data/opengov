<?php

namespace Drupal\vud\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Configures Vote Up/Down settings for this site.
 */
class VudAdminAdvancedSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vud_admin_advanced_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $vud_settings = $this->config('vud.settings');
    $vud_settings->set('tag', $form_state->getValue('vud_tag'));
    $vud_settings->set('message_on_deny', $form_state->getValue('vud_message_on_deny'));
    $vud_settings->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['vud.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $vud_settings = $this->config('vud.settings');
    $form['vud_tag'] = [
      '#type' => 'textfield',
      '#title' => t('Voting API tag'),
      '#default_value' => $vud_settings->get('tag'),
      '#description' => t('Since Vote Up/Down uses Voting API, all votes will be tagged with this term. (default: vote)<br />This tag is useful if you have deployed various modules that use Voting API. It should always be a unique value. Usually, there is NO need to change this.'),
    ];
    $form['vud_message_on_deny'] = [
      '#type' => 'checkbox',
      '#title' => t('Message on denied permission'),
      '#default_value' => $vud_settings->get('message_on_deny'),
      '#description' => t('When this flag is active, a modal window will be shown to the end user instead of avoid showing the voting links'),
    ];

    return parent::buildForm($form, $form_state);
  }

}
