<?php

namespace Drupal\feeds;

use Drupal\Core\Entity\EntityInterface;
use Drupal\feeds\Event\CleanEvent;
use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\FetchEvent;
use Drupal\feeds\Event\InitEvent;
use Drupal\feeds\Event\ParseEvent;
use Drupal\feeds\Event\ProcessEvent;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\Exception\LockException;
use Drupal\feeds\Feeds\Item\ItemInterface;
use Drupal\feeds\Feeds\State\CleanStateInterface;
use Drupal\feeds\Plugin\QueueWorker\FeedRefresh;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\Result\ParserResultInterface;
use Drupal\feeds\Result\RawFetcherResult;

/**
 * Runs the actual import on a feed.
 */
class FeedImportHandler extends FeedHandlerBase {

  /**
   * The fetcher result.
   *
   * @var \Drupal\feeds\Result\FetcherResultInterface
   */
  protected $fetcherResult;

  /**
   * Imports the whole feed at once.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to import for.
   *
   * @throws \Exception
   *   In case of an error.
   */
  public function import(FeedInterface $feed) {
    $feed->lock();
    $fetcher_result = $this->doFetch($feed);

    try {
      do {
        foreach ($this->doParse($feed, $fetcher_result) as $item) {
          $this->doProcess($feed, $item);
        }
      } while ($feed->progressImporting() !== StateInterface::BATCH_COMPLETE);

      // Clean up if needed.
      $clean_state = $feed->getState(StateInterface::CLEAN);
      if ($clean_state instanceof CleanStateInterface && $clean_state->count()) {
        while ($entity = $clean_state->nextEntity()) {
          $this->doClean($feed, $entity);
        }
      }
    }
    catch (EmptyFeedException $e) {
      // Not an error.
    }
    catch (\Exception $exception) {
      // Do nothing. Will throw later.
    }

    $feed->finishImport();

    if (isset($exception)) {
      throw $exception;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function startBatchImport(FeedInterface $feed) {
    try {
      $feed->lock();
    }
    catch (LockException $e) {
      \Drupal::messenger()->addWarning(t('The feed became locked before the import could begin.'));
      return;
    }

    $feed->clearStates();
    $this->startBatchFetch($feed);
  }

  /**
   * Sets the fetch batch.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed being fetched.
   */
  protected function startBatchFetch(FeedInterface $feed) {
    $batch = [
      'title' => $this->t('Fetching: %title', ['%title' => $feed->label()]),
      'init_message' => $this->t('Fetching: %title', ['%title' => $feed->label()]),
      'operations' => [
        [[$this, 'batchFetch'], [$feed]],
      ],
      'progress_message' => $this->t('Fetching: %title', ['%title' => $feed->label()]),
      'error_message' => $this->t('An error occored while fetching %title.', ['%title' => $feed->label()]),
    ];

    batch_set($batch);
  }

  /**
   * Performs the batch fetching.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed being fetched.
   */
  public function batchFetch(FeedInterface $feed) {
    try {
      $this->fetcherResult = $this->doFetch($feed);
    }
    catch (\Exception $exception) {
      return $this->handleException($feed, $exception);
    }

    $this->startBatchParse($feed);
    $feed->saveStates();
  }

  /**
   * Sets the parse batch.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed being fetched.
   */
  protected function startBatchParse(FeedInterface $feed) {
    $batch = [
      'title' => $this->t('Parsing: %title', ['%title' => $feed->label()]),
      'init_message' => $this->t('Parsing: %title', ['%title' => $feed->label()]),
      'operations' => [
        [[$this, 'batchParse'], [$feed]],
      ],
      'progress_message' => $this->t('Parsing: %title', ['%title' => $feed->label()]),
      'error_message' => $this->t('An error occored while parsing %title.', ['%title' => $feed->label()]),
    ];

    batch_set($batch);
  }

  /**
   * Performs the batch parsing.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed.
   */
  public function batchParse(FeedInterface $feed) {
    try {
      $parser_result = $this->doParse($feed, $this->fetcherResult);
    }
    catch (\Exception $exception) {
      return $this->handleException($feed, $exception);
    }

    $this->startBatchProcess($feed, $parser_result);
    $feed->saveStates();
  }

  /**
   * Starts the process batch.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed.
   * @param \Drupal\feeds\Result\ParserResultInterface $parser_result
   *   The parser result.
   */
  protected function startBatchProcess(FeedInterface $feed, ParserResultInterface $parser_result) {
    $batch = [
      'title' => $this->t('Processing: %title', ['%title' => $feed->label()]),
      'init_message' => $this->t('Processing: %title', ['%title' => $feed->label()]),
      'progress_message' => $this->t('Processing: %title', ['%title' => $feed->label()]),
      'error_message' => $this->t('An error occored while processing %title.', ['%title' => $feed->label()]),
    ];

    foreach ($parser_result as $item) {
      $batch['operations'][] = [[$this, 'batchProcess'], [$feed, $item]];
    }
    $batch['operations'][] = [[$this, 'batchPostProcess'], [$feed]];

    batch_set($batch);
  }

  /**
   * Performs the batch processing.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed.
   * @param \Drupal\feeds\Feeds\Item\ItemInterface $item
   *   An item to process.
   */
  public function batchProcess(FeedInterface $feed, ItemInterface $item) {
    try {
      $this->doProcess($feed, $item);
    }
    catch (\Exception $exception) {
      return $this->handleException($feed, $exception);
    }

    $feed->saveStates();
  }

  /**
   * Finishes importing, or starts unfinished stages.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed.
   */
  public function batchPostProcess(FeedInterface $feed) {
    if ($feed->progressParsing() !== StateInterface::BATCH_COMPLETE) {
      $this->startBatchParse($feed);
    }
    elseif ($feed->progressFetching() !== StateInterface::BATCH_COMPLETE) {
      $this->startBatchFetch($feed);
    }
    elseif ($feed->progressCleaning() !== StateInterface::BATCH_COMPLETE) {
      $this->startBatchClean($feed);
    }
    else {
      $feed->finishImport();
      $feed->startBatchExpire();
    }
  }

  /**
   * Starts the clean batch.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed.
   */
  protected function startBatchClean(FeedInterface $feed) {
    $batch = [
      'title' => $this->t('Cleaning: %title', ['%title' => $feed->label()]),
      'init_message' => $this->t('Cleaning: %title', ['%title' => $feed->label()]),
      'progress_message' => $this->t('Cleaning: %title', ['%title' => $feed->label()]),
      'error_message' => $this->t('An error occored while cleaning %title.', ['%title' => $feed->label()]),
    ];

    $batch['operations'][] = [[$this, 'batchClean'], [$feed]];
    $batch['operations'][] = [[$this, 'batchPostProcess'], [$feed]];

    batch_set($batch);
  }

  /**
   * Performs the batch cleaning.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed.
   * @param array $context
   *   Batch context.
   */
  public function batchClean(FeedInterface $feed, array &$context) {
    $state = $feed->getState(StateInterface::CLEAN);
    if (empty($context['sandbox'])) {
      $context['sandbox']['max'] = $state->count();
      $context['sandbox']['progress'] = 0;
    }

    try {
      $entity = $state->nextEntity();
      if ($entity) {
        $this->doClean($feed, $entity);
      }
      $context['sandbox']['progress']++;
      $context['finished'] = ($context['sandbox']['progress'] >= $context['sandbox']['max']);
      if (!$state->count()) {
        $state->setCompleted();
      }
    }
    catch (\Exception $exception) {
      return $this->handleException($feed, $exception);
    }

    $feed->saveStates();
  }

  /**
   * Starts importing a feed via cron.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to queue.
   *
   * @throws \Drupal\feeds\Exception\LockException
   *   Thrown if a feed is locked.
   */
  public function startCronImport(FeedInterface $feed) {
    if ($feed->isLocked()) {
      $args = ['@id' => $feed->bundle(), '@fid' => $feed->id()];
      throw new LockException($this->t('The feed @id / @fid is locked.', $args));
    }

    // Add feed import task to the queue.
    $queue = \Drupal::queue('feeds_feed_refresh:' . $feed->bundle());
    if ($queue->createItem([$feed, FeedRefresh::BEGIN, []])) {
      // Add timestamp to avoid queueing item more than once.
      $feed->setQueuedTime(\Drupal::time()->getRequestTime());
      $feed->save();
    }
  }

  /**
   * Handles a push import.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed receiving the push.
   * @param string $payload
   *   The feed contents.
   *
   * @todo Move this to a queue.
   */
  public function pushImport(FeedInterface $feed, $payload) {
    $feed->lock();
    $fetcher_result = new RawFetcherResult($payload);

    try {
      do {
        foreach ($this->doParse($feed, $fetcher_result) as $item) {
          $this->doProcess($feed, $item);
        }
      } while ($feed->progressImporting() !== StateInterface::BATCH_COMPLETE);

      // Clean up if needed.
      $clean_state = $feed->getState(StateInterface::CLEAN);
      if ($clean_state instanceof CleanStateInterface && $clean_state->count()) {
        while ($entity = $clean_state->nextEntity()) {
          $this->doClean($feed, $entity);
        }
      }
    }
    catch (EmptyFeedException $e) {
      // Not an error.
    }
    catch (\Exception $exception) {
      // Do nothing. Will throw later.
    }

    $feed->finishImport();

    if (isset($exception)) {
      throw $exception;
    }
  }

  /**
   * Invokes the fetch stage.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to fetch.
   *
   * @return \Drupal\feeds\Result\FetcherResultInterface
   *   The result of the fetcher.
   */
  protected function doFetch(FeedInterface $feed) {
    $this->dispatchEvent(FeedsEvents::INIT_IMPORT, new InitEvent($feed, 'fetch'));
    $fetch_event = $this->dispatchEvent(FeedsEvents::FETCH, new FetchEvent($feed));
    $feed->setState(StateInterface::PARSE, NULL);

    return $fetch_event->getFetcherResult();
  }

  /**
   * Invokes the parse stage.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to fetch.
   * @param \Drupal\feeds\Result\FetcherResultInterface $fetcher_result
   *   The result of the fetcher.
   *
   * @return \Drupal\feeds\Result\ParserResultInterface
   *   The result of the parser.
   */
  protected function doParse(FeedInterface $feed, FetcherResultInterface $fetcher_result) {
    $this->dispatchEvent(FeedsEvents::INIT_IMPORT, new InitEvent($feed, 'parse'));

    $parse_event = $this->dispatchEvent(FeedsEvents::PARSE, new ParseEvent($feed, $fetcher_result));

    return $parse_event->getParserResult();
  }

  /**
   * Invokes the process stage.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to fetch.
   * @param \Drupal\feeds\Feeds\Item\ItemInterface $item
   *   The item to process.
   */
  protected function doProcess(FeedInterface $feed, ItemInterface $item) {
    $this->dispatchEvent(FeedsEvents::INIT_IMPORT, new InitEvent($feed, 'process'));
    $this->dispatchEvent(FeedsEvents::PROCESS, new ProcessEvent($feed, $item));
  }

  /**
   * Invokes the clean stage.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to fetch.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to apply an action on.
   */
  protected function doClean(FeedInterface $feed, EntityInterface $entity) {
    $this->dispatchEvent(FeedsEvents::INIT_IMPORT, new InitEvent($feed, 'clean'));
    $this->dispatchEvent(FeedsEvents::CLEAN, new CleanEvent($feed, $entity));
  }

  /**
   * Handles an exception during importing.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed.
   * @param \Exception $exception
   *   The exception that was thrown.
   *
   * @throws \Exception
   *   Thrown if $exception is not an instance of EmptyFeedException.
   */
  protected function handleException(FeedInterface $feed, \Exception $exception) {
    $feed->finishImport();

    if ($exception instanceof EmptyFeedException) {
      return;
    }
    if ($exception instanceof \RuntimeException) {
      \Drupal::messenger()->addError($exception->getMessage());
      return;
    }

    throw $exception;
  }

}
