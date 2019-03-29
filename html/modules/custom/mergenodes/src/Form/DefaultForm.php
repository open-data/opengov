<?php

namespace Drupal\mergenodes\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class DefaultForm.
 */
class DefaultForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mergeNodesForm';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['contenttype'] = [
      '#type' => 'select',
      '#title' => $this->t('Select a content type to merge'),
      '#options' => ['app' => $this->t('App'), 'page' => $this->t('Basic page'), 'blog_post' => $this->t('Blog post'), 'commitment' => $this->t('Commitment'), 'consultation' => $this->t('Consultation'), 'idea' => $this->t('Idea'), 'landing_page' => $this->t('Landing page'), 'suggested_dataset' => $this->t('Suggested dataset'), 'webform' => $this->t('Webform')],
      '#size' => 5,
      '#weight' => '0',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#title' => $this->t('Merge'),
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
    // Display result.
    /*foreach ($form_state->getValues() as $key => $value) {
      drupal_set_message($key . ': ' . $value);
    }*/
    $contenttye = $form_state->getValue('contenttype');

    if ($contenttye) {
      // 1. Fetch all nodes of content type
      $query = \Drupal::entityQuery('node');
      $query->condition('type', $contenttye);
      $query->sort('field_previousnodeid');
      $nids = $query->execute();
      // for all nids {
      // 2. Load the field_previousnodeid for the first node
      // 3. check the field_previousnodeid for the next node
      // 4. If both have same value for field_previousnodeid merge the second node as a translation and delete after merging
      // }
    }
    else {
      drupal_set_message("No content type selected", 'warning');
    }

  }

}
