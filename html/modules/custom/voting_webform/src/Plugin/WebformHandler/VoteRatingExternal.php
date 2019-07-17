<?php

namespace Drupal\voting_webform\Plugin\WebformHandler;

use Drupal\facets\Exception\Exception;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\webformSubmissionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;

/**
 * Webform submission test handler.
 *
 * @WebformHandler(
 *   id = "vote_rating_external",
 *   label = @Translation("Maple leaf rating (external) Webform Handler"),
 *   category = @Translation("Vote"),
 *   description = @Translation("Update vote count and vote average for external entities"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */

class VoteRatingExternal extends WebformHandlerBase {
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $uuid = $webform_submission->getElementData('dataset_uuid');

    // @todo generalize type when needed
    $type='dataset';

    try {
      // get current vote count and average
      $connection = \Drupal::database();
      $query = $connection->select('external_rating', 'v');
      $query->condition('v.uuid', $uuid, '=');
      $query->fields('v', ['vote_count', 'vote_average']);
      $result = $query->execute();
      $vote_count = 0;
      $vote_average = 0;

      foreach ($result as $record) {
        $vote_count = $record->vote_count;
        $vote_average = $record->vote_average;
      }

      $new_count = $vote_count + 1;
      $new_average = ($vote_average * $vote_count + $form_state->getValue('rating')) / $new_count;

      // update or insert vote count+1
      $query = $connection->upsert('external_rating');
      $query->fields([ 'type', 'uuid', 'vote_count', 'vote_average' ]);
      $query->values([ $type, $uuid, $new_count, $new_average ]);
      $query->key('uuid');
      $query->execute();

      // clear cache
      $webform = $webform_submission->getWebform();
      Cache::invalidateTags($webform->getCacheTags());
    }
    catch (Exception $e) {
      \Drupal::logger('vote')->
        warning('Vote-Vote Up or LIKE (external): Exception thrown while trying to update vote count for uuid: '
        . $uuid . '\n'
        . $e->getMessage());
    }
  }
}

