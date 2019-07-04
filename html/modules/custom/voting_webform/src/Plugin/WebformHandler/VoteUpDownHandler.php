<?php

namespace Drupal\voting_webform\Plugin\WebformHandler;

use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\webformSubmissionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Webform voteSubmission handler.
 *
 * @WebformHandler(
 *   id = "vote_up_down_handler",
 *   label = @Translation("Vote Up Down Submission Handler"),
 *   category = @Translation("voteSubmission"),
 *   description = @Translation("voteSubmission of a webform submission handler."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */

class VoteUpDownHandler extends WebformHandlerBase
{

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $node = \Drupal::routeMatch()->getParameter($webform_submission->getSourceEntity()->getEntityTypeId());
    if ($node instanceof \Drupal\node\NodeInterface) {
      if ($webform_submission->getSourceEntity()->id() === $node->id()) {
        $node->field_vote_up_down = $node->get('field_vote_up_down')->value + 1;
        $node->save();
      }
    }
  }
}
