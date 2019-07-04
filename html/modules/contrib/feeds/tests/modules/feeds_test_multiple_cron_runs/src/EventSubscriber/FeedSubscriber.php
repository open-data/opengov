<?php

namespace Drupal\feeds_test_multiple_cron_runs\EventSubscriber;

use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\ProcessEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to feeds events.
 */
class FeedSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      FeedsEvents::PROCESS => [
        ['afterProcess', FeedsEvents::AFTER],
      ],
    ];
  }

  /**
   * Delays execution after limit is reached.
   */
  public function afterProcess(ProcessEvent $event) {
    static $processed = 0;
    $processed++;

    $limit = \Drupal::config('feeds_test_multiple_cron_runs.settings')->get('import_queue_time');
    if ($processed == $limit) {
      sleep($limit);
    }
  }

}
