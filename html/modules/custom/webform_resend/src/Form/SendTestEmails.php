<?php

namespace Drupal\webform_resend\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SendTestEmails.
 */
class SendTestEmails extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'send_test_emails';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['email_from'] = [
      '#type' => 'email',
      '#title' => 'Email address from which emails will be sent',
      '#default_value' => \Drupal::config('system.site')->get('mail'),
      '#attributes' => [ 'disabled' => 'true', 'required' => 'true' ],
    ];
    $form['email_to'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Enter all email addresses to send email, separated with comma (,)'),
      '#attributes' => [ 'rows' => '5', 'cols' => '100', 'required' => 'true' ],
    ];
    $form['email_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter the subject for test email'),
      '#attributes' => [ 'required' => 'true' ],
    ];
    $form['email_body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Enter the Body for the test email'),
      '#format' => 'rich_text',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send Test Emails'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // check user permission
    $user_role = \Drupal::currentUser()->getRoles();
    if (!in_array('business_owner', $user_role, TRUE) && !in_array('administrator', $user_role, TRUE) ) {
      $form_state->setErrorByName('submit', $this->t('Insufficient user permissions'));
    }

    // validate email from field
    if (empty($form_state->getValue('email_from'))) {
      $form_state->setErrorByName('email_from', $this->t('Missing: From email address'));
    }

    // validate email to field
    if (empty($form_state->getValue('email_to'))) {
      $form_state->setErrorByName('email_to', $this->t('Missing: To email address'));
    }

    $email_list = explode(',', $form_state->getValue('email_to'));
    $emails_clean_flag = NULL;
    foreach($email_list as $email_to_individual) {
      if (!filter_var($email_to_individual, FILTER_VALIDATE_EMAIL)) {
        $emails_clean_flag = $email_to_individual;
      }
    }
    if ($emails_clean_flag) {
      $form_state->setErrorByName('email_to', $this->t('Invalid email address: ') . $emails_clean_flag);
    }

    // validate email subject
    if (empty($form_state->getValue('email_subject'))) {
      $form_state->setErrorByName('email_subject', $this->t('Missing: Subject for email'));
    }

    // validate email body
    $email_body = $form_state->getValue('email_body');
    if (empty($email_body['value'])) {
      $form_state->setErrorByName('email_body', $this->t('Missing: Body for email'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $email_list = explode(',', $form_state->getValue('email_to'));
    foreach($email_list as $email_to_individual) {
      $mailManager = \Drupal::service('plugin.manager.mail');
      $langcode =  \Drupal::currentUser()->getPreferredLangcode();
      $params['to'] = $email_to_individual;
      $params['from'] = $form_state->getValue('email_from');
      $params['subject'] = $form_state->getValue('email_subject');
      $params['message'] = $form_state->getValue('email_body')['value'];

      if ($mailManager->mail('webform_resend', 'testmail', $email_to_individual, $langcode, $params, $params['from'], TRUE)) {
        \Drupal::messenger()->addMessage("Successfully sent email to " . $email_to_individual);
        \Drupal::logger('webform_resend')->notice("Successfully sent email to " . $email_to_individual);
      }
      else {
        \Drupal::messenger()->addMessage("Failed to send email to " . $email_to_individual, 'error');
        \Drupal::logger('webform_resend')->notice("Failed to send email to " . $email_to_individual);
        $form_state->setRebuild();
      }
    }
  }

}
