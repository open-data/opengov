<?php

namespace Drupal\voting_webform\Plugin\WebformHandler;

use Drupal\facets\Exception\Exception;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\webformSubmissionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Webform submission test handler.
 *
 * @WebformHandler(
 *   id = "vote_up_external",
 *   label = @Translation("Vote Up or LIKE (external) Webform Handler"),
 *   category = @Translation("Vote"),
 *   description = @Translation("Update vote count for external entities"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */

class VoteUpDownExternal extends WebformHandlerBase {
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $uuid = $webform_submission->getElementData('uuid');
    try {
      // get current vote count
      $connection = \Drupal::database();
      $query = $connection->select('external_voting', 'v');
      $query->condition('v.uuid', $uuid, '=');
      $query->fields('v', ['vote_count']);
      $result = $query->execute();
      $vote_count=0;
      foreach ($result as $record) {
        $vote_count = $record->vote_count;
      }

      // update or insert vote count+1
      $connection = \Drupal::database();
      $query = $connection->upsert('external_voting');
      $query->fields(['type', 'uuid', 'vote_count']);
      $query->values(['inventory', $uuid, $vote_count+1]);
      $query->key('uuid');
      $query->execute();
    }
    catch (Exception $e) {
      \Drupal::logger('vote')->
        warning('Vote-Vote Up or LIKE (external): Exception thrown while trying to update vote count for uuid: '
        . $uuid . '\n'
        . $e->getMessage());
    }
  }
}

