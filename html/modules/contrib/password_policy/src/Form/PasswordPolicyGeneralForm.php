<?php

namespace Drupal\password_policy\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * The general settings of the policy not tied to constraints.
 */
class PasswordPolicyGeneralForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'password_policy_general_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    /** @var \Drupal\password_policy\Entity\PasswordPolicy $policy */
    $policy = $cached_values['password_policy'];

    $form['password_reset'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password Reset Days'),
      '#description' => $this->t('User password will reset after the selected number of days.  0 days indicates that passwords never expire.'),
      '#default_value' => $policy->getPasswordReset(),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    /** @var \Drupal\password_policy\Entity\PasswordPolicy $policy */
    $policy = $cached_values['password_policy'];
    $policy->set('password_reset', $form_state->getValue('password_reset'));
    $form_state->setTemporaryValue('wizard', $cached_values);
  }

}
