<?php

namespace Drupal\webform_resend\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\Plugin\WebformHandler\EmailWebformHandler;
use Drupal\webform\WebformSubmissionForm;

/**
 * Class DefaultForm.
 */
class DefaultForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'default_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // generate a list of webforms
    $webforms_list = Webform::loadMultiple();
    $webforms = array();
    foreach ($webforms_list as $webform_id => $webform) {
      $is_open = WebformSubmissionForm::isOpen($webform);
      if ($is_open === TRUE) {
        $webforms[$webform->id()] = $webform->label();
      }
    }
    ksort($webforms);

    // create a select list of all webforms
    $form['webform'] = [
      '#type' => 'select',
      '#title' => $this->t('Select webform to resend emails'),
      '#options' => $webforms,
    ];

    $form['start_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Enter start date'),
    ];

    $form['end_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Enter end date'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $webform_id = $form_state->getValue('webform');
    $start_date = $form_state->getValue('start_date');
    $end_date = $form_state->getValue('end_date');

    if (empty($webform_id)) {
      \Drupal::messenger()->addMessage('No webform selected to resend emails', 'warning');
    }
    elseif (empty($start_date)) {
      \Drupal::messenger()->addMessage('No start date selected to resend emails', 'warning');
    }
    elseif (empty($end_date)) {
      \Drupal::messenger()->addMessage('No end date selected to resend emails', 'warning');
    }
    else {
      $webform = Webform::load($webform_id);
      if ($webform->hasSubmissions()) {
        // fetch all submissions for the selected webform in the date range
        $query = \Drupal::entityQuery('webform_submission')
          ->condition('webform_id', $webform_id)
          ->condition('created', [strtotime($start_date), strtotime($end_date)], 'BETWEEN');
        $result = $query->execute();
        \Drupal::messenger()->addMessage(sizeof($result) . ' submissions of ' . $webform_id .
          ' form found from ' . $start_date . ' to ' . $end_date);

        $success = 0;
        $fail = 0;
        $success_list = '';
        $fail_list = '';

        foreach ($result as $item) {
          // fetch all email handlers of the selected webform
          $submission = WebformSubmission::load($item);
          $handlers = $submission->getWebform()->getHandlers();

          foreach ($handlers as $handler) {
            // only act on handlers where plugin is email
            if ($handler instanceOf EmailWebformHandler) {

              // re-send email
              $message_handler = $submission->getWebform()->getHandler($handler->getHandlerId());
              $message = $message_handler->getMessage($submission);
              $send_result = $message_handler->sendMessage($submission, $message);
              if ($send_result) {
                $success++;
                $success_list .= $submission->id() . ' (' . $message['to_mail'] . ')' . ' - ';
              }
              else {
                $fail++;
                $fail_list .= $submission->id() . ' (' . $message['to_mail'] . ')' . ' - ';
              }
            }
          }
        }
        \Drupal::messenger()->addMessage($success . ' emails resend successfully: ' . $success_list );
        \Drupal::messenger()->addMessage($fail . ' emails failed to resend: ' . $fail_list);
      }
    }
  }

}
