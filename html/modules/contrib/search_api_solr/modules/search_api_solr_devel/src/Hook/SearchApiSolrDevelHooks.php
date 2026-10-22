<?php

namespace Drupal\search_api_solr_devel\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Search API Solr Devel hooks.
 */
final class SearchApiSolrDevelHooks {

  /**
   * Implements hook_entity_type_alter().
   */
  #[Hook('entity_type_alter')]
  public function entityTypeAlter(array &$entity_types): void {
    /** @var \Drupal\Core\Entity\EntityTypeInterface[] $entity_types */
    foreach ($entity_types as $entity_type_id => $entity_type) {
      if ($entity_type->hasLinkTemplate('devel-render') || $entity_type->hasLinkTemplate('devel-load')) {
        $entity_type->setLinkTemplate('devel-solr', "/devel/$entity_type_id/{{$entity_type_id}}/solr");
      }
    }
  }

}
