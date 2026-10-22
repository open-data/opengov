<?php

namespace Drupal\search_api_solr\Entity;

use Drupal\search_api\Entity\Index as SearchApiIndex;

/**
 * Extends the Search API index entity with Solr-specific lock behavior.
 */
class Index extends SearchApiIndex {

  const KEEP_EMPTY_INDEX_STATE_SECONDS = 3600;

  /**
   * {@inheritdoc}
   */
  public function getLockId(): string {
    if ($this->hasValidTracker() && $this->getTrackerId() === 'index_parallel') {
      /** @var \Drupal\search_api_solr\Plugin\search_api\tracker\IndexParallel $tracker */
      $tracker = $this->getTrackerInstance();
      return "search_api:index:{$this->id}:thread:{$tracker->getThread()}";
    }

    return parent::getLockId();
  }

  /**
   * Checks whether an empty-index run is currently flagged.
   *
   * @return bool
   *   TRUE if the empty-index state is active, FALSE otherwise.
   */
  public function isIndexingEmptyIndex(): bool {
    $key = "search_api.index.{$this->id()}.indexing_empty";
    $timestamp = \Drupal::state()->get($key, 0);
    return (\Drupal::time()->getRequestTime() - $timestamp) < self::KEEP_EMPTY_INDEX_STATE_SECONDS;
  }

  /**
   * Sets whether the index is currently processing an empty-index run.
   *
   * @param bool $state
   *   TRUE to mark the state active, FALSE to clear it.
   */
  public function setIndexingEmptyIndex(bool $state): void {
    $key = "search_api.index.{$this->id()}.indexing_empty";
    \Drupal::state()->set($key, $state ? \Drupal::time()->getRequestTime() : 0);
  }

}
