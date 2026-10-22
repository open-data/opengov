<?php

namespace Drupal\search_api_solr\Plugin\search_api\tracker;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\search_api\Attribute\SearchApiTracker;
use Drupal\search_api\Plugin\search_api\tracker\Basic;

/**
 * Provides a tracker implementation which uses a FIFO-like processing order.
 */
#[SearchApiTracker(
  id: 'index_parallel',
  label: new TranslatableMarkup('Index parallel'),
  description: new TranslatableMarkup('Index parallel tracker which allows to index in parallel.')
)]
class IndexParallel extends Basic {

  const SAFETY_DISTANCE_FACTOR = 3;

  /**
   * The current offset.
   *
   * @var int
   */
  protected $offset = 0;

  /**
   * The current worker thread.
   *
   * @var int
   */
  protected $thread = 1;

  /**
   * Sets the current item offset.
   *
   * @param int $offset
   *   The current item offset for the worker thread.
   *
   * @return void
   *   This method does not return a value.
   */
  public function setOffset(int $offset): void {
    $this->offset = $offset;
  }

  /**
   * Sets the worker thread identifier.
   *
   * @param int $thread
   *   The worker thread identifier.
   *
   * @return void
   *   This method does not return a value.
   */
  public function setThread(int $thread): void {
    $this->thread = $thread;
  }

  /**
   * Gets the worker thread identifier.
   *
   * @return int
   *   The worker thread identifier.
   */
  public function getThread(): int {
    return $this->thread;
  }

  /**
   * {@inheritdoc}
   */
  public function getRemainingItems($limit = -1, $datasource_id = NULL) {
    try {
      $select = $this->createRemainingItemsStatement($datasource_id);
      if ($limit >= 0) {
        $select->range($this->offset, $limit);
      }
      return $select->execute()->fetchCol();
    }
    catch (\Exception $e) {
      $this->logException($e);
      return [];
    }
  }

}
