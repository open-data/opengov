<?php

// phpcs:ignoreFile SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Drupal\search_api_solr_test\EventSubscriber;

use Drupal\search_api_solr\Event\PostConfigFilesGenerationEvent;
use Drupal\search_api_solr\Event\PostCreateIndexDocumentsEvent;
use Drupal\search_api_solr\Event\SearchApiSolrEvents;
use Solarium\QueryType\Update\Query\Document;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Search API Solr events subscriber.
 */
class SearchApiSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[SearchApiSolrEvents::POST_CONFIG_FILES_GENERATION][] = ['postConfigFilesGeneration'];
    $events[SearchApiSolrEvents::POST_CREATE_INDEX_DOCUMENTS][] = ['postCreateIndexDocuments'];

    return $events;
  }

  /**
   * Adds a test config file after config generation.
   *
   * @param \Drupal\search_api_solr\Event\PostConfigFilesGenerationEvent $event
   *   The dispatched event.
   */
  public function postConfigFilesGeneration(PostConfigFilesGenerationEvent $event): void {
    $files = $event->getConfigFiles();

    $files['test.txt'] =
      "hook_search_api_solr_config_files_alter() works\n" .
      $event->getServerId() . "\n";

    $event->setConfigFiles($files);
  }

  /**
   * Alters generated index documents for fallback test coverage.
   *
   * @param \Drupal\search_api_solr\Event\PostCreateIndexDocumentsEvent $event
   *   The dispatched event.
   */
  public function postCreateIndexDocuments(PostCreateIndexDocumentsEvent $event): void {
    global $_search_api_solr_test_index_fallback_test;

    if ($_search_api_solr_test_index_fallback_test) {
      $documents = $event->getSolariumDocuments();
      foreach ($documents as $document) {
        assert($document instanceof Document);
        $fields = $document->getFields();
        if (
          'entity:entity_test_mulrev_changed/2:en' ===
          ($fields['ss_search_api_id'] ?? NULL)
        ) {
          // Send a string as value of a float field for the fallback test.
          $document->setField('fts_width', 'bar');
        }
      }
      $event->setSolariumDocuments($documents);
    }
  }

}
