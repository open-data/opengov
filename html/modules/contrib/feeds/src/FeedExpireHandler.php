<?php

namespace Drupal\feeds;

use Drupal\feeds\Event\ExpireEvent;
use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\InitEvent;
use Drupal\feeds\Exception\LockException;

/**
 * Expires the items of a feed.
 */
class FeedExpireHandler extends FeedHandlerBase {

  /**
   * Starts a batch for expiring items.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed for which to expire items.
   */
  public function startBatchExpire(FeedInterface $feed) {
    try {
      $feed->lock();
    }
    catch (LockException $e) {
      \Drupal::messenger()->addWarning(t('The feed became locked before the expiring could begin.'));
      return;
    }
    $feed->clearStates();

    $ids = $this->getExpiredIds($feed);

    if (!$ids) {
      $feed->unlock();
      return;
    }

    $batch = [
      'title' => $this->t('Expiring: %title', ['%title' => $feed->label()]),
      'init_message' => $this->t('Expiring: %title', ['%title' => $feed->label()]),
      'progress_message' => $this->t('Expiring: %title', ['%title' => $feed->label()]),
      'error_message' => $this->t('An error occored while expiring %title.', ['%title' => $feed->label()]),
    ];

    foreach ($ids as $id) {
      $batch['operations'][] = [[$this, 'expireItem'], [$feed, $id]];
    }
    $batch['operations'][] = [[$this, 'postExpire'], [$feed]];

    batch_set($batch);
  }

  /**
   * Returns feed item ID's to expire.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed for which to get the expired item ID's.
   *
   * @return array
   *   A list of item ID's.
   */
  protected function getExpiredIds(FeedInterface $feed) {
    return $feed->getType()->getProcessor()->getExpiredIds($feed);
  }

  /**
   * Expires a single item imported with the given feed.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed for which to expire the item.
   * @param int $item_id
   *   The ID of the item to expire. Usually this is an entity ID.
   *
   * @return float
   *   The progress being made on expiring.
   */
  public function expireItem(FeedInterface $feed, $item_id) {
    try {
      $this->dispatchEvent(FeedsEvents::INIT_EXPIRE, new InitEvent($feed));
      $this->dispatchEvent(FeedsEvents::EXPIRE, new ExpireEvent($feed, $item_id));
    }
    catch (\RuntimeException $e) {
      \Drupal::messenger()->addError($e->getMessage());
      $feed->clearStates();
      $feed->unlock();
    }
    catch (\Exception $e) {
      $feed->clearStates();
      $feed->unlock();
      throw $e;
    }

    return $feed->progressExpiring();
  }

  /**
   * Handles clean up tasks after expiring items is done.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed for which items got expired.
   */
  public function postExpire(FeedInterface $feed) {
    $state = $feed->getState(StateInterface::EXPIRE);
    if ($state->total) {
      \Drupal::messenger()->addStatus($this->t('Expired @count items.', ['@count' => $state->total]));
    }
    $feed->clearStates();
    $feed->save();
    $feed->unlock();
  }

}
