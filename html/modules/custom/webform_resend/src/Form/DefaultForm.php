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
      '#attributes' => [ 'type' => 'date', 'required' => 'true' ],
    ];

    $form['start_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Enter start date'),
      '#attributes' => [ 'type' => 'date', 'required' => 'true' ],
    ];

    $form['end_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Enter end date'),
      '#attributes' => [ 'required' => 'true' ],
    ];

    if ($form_state->isSubmitted()) {
      $webform_id = $form_state->getValue('webform');
      $start_date = $form_state->getValue('start_date');
      $end_date = $form_state->getValue('end_date');

      $webform = Webform::load($webform_id);
      if ($webform->hasSubmissions()) {
        // fetch all submissions for the selected webform in the date range
        $query = \Drupal::entityQuery('webform_submission')
          ->condition('webform_id', $webform_id)
          ->condition('created', [strtotime($start_date), strtotime($end_date)], 'BETWEEN');
        $result = $query->execute();
        \Drupal::messenger()->addMessage(sizeof($result) . ' submissions of ' . $webform_id .
          ' form found from ' . $start_date . ' to ' . $end_date);

        foreach ($result as $item) {
          // fetch all email handlers of the selected webform
          $submission = WebformSubmission::load($item);
          $handlers = $submission->getWebform()->getHandlers();

          // generate a table of submissions
          $rows = array();
          foreach ($handlers as $handler) {
            // only act on handlers where plugin is email
            if ($handler instanceOf EmailWebformHandler) {
              $message_handler = $submission->getWebform()->getHandler($handler->getHandlerId());
              $message = $message_handler->getMessage($submission);
              $rows[] = [
                $submission->label(),
                $handler->label(),
                date('Y-m-d g:i A', $submission->getCreatedTime()),
                $message['subject'],
                $message['to_mail'],
                $message['from_mail'],
              ];
            }
          }
          $form['submissions'] = [
            '#type' => 'table',
            '#header' => [
              $this->t('Submission #'),
              $this->t('Handler'),
              $this->t('Date'),
              $this->t('Subject'),
              $this->t('To Email'),
              $this->t('From Email'),
            ],
            '#rows' => $rows,
          ];
        }
      }
    }

    $form['view_submissions'] = array(
      '#name' => 'btn_view_submissions',
      '#type' => 'submit',
      '#value' => t('View Webform Submissions'),
      '#submit' => array([$this, 'viewSubmissions']),
    );

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Resend Webform Submission Emails'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function viewSubmissions(array &$form, FormStateInterface &$form_state) {
    $form_state-> setRebuild();
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

    // validate webform
    if (empty($form_state->getValue('webform'))) {
      $form_state->setErrorByName('webform', $this->t('No webform selected to view submissions'));
    }

    // validate start date
    if (empty($form_state->getValue('start_date'))) {
      $form_state->setErrorByName('start_date', $this->t('No start date selected to view submissions'));
    }

    if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$form_state->getValue('start_date'))) {
      $form_state->setErrorByName('start_date', $this->t('Invalid start date selected to view submissions'));
    }

    // validate end date
    if (empty($form_state->getValue('end_date'))) {
      $form_state->setErrorByName('end_date', $this->t('No end date selected to view submissions'));
    }

    if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$form_state->getValue('end_date'))) {
      $form_state->setErrorByName('end_date', $this->t('Invalid end date selected to view submissions'));
    }

    if ($form_state->getValue('end_date') <= $form_state->getValue('start_date')) {
      $form_state->setErrorByName('end_date', $this->t('End date is less than or equal to start date'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $webform_id = $form_state->getValue('webform');
    $start_date = $form_state->getValue('start_date');
    $end_date = $form_state->getValue('end_date');

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
            } else {
              $fail++;
              $fail_list .= $submission->id() . ' (' . $message['to_mail'] . ')' . ' - ';
            }
          }
        }
      }
      \Drupal::messenger()->addMessage($success . ' emails resend successfully: ' . $success_list);
      \Drupal::messenger()->addMessage($fail . ' emails failed to resend: ' . $fail_list);
    }
  }

}
