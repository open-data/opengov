<?php

namespace Drupal\views_filters_summary_verf\Hook;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for views_filters_summary_verf.
 */
class ViewsFiltersSummaryVerfHooks {

  /**
   * Constructs a new ViewsFiltersSummaryVerfHooks object.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Implements hook_views_filters_summary_info_alter().
   */
  #[Hook('views_filters_summary_info_alter')]
  public function infoAlter(&$info, $filter) {
    if ($filter->getPluginId() === 'verf') {
      $values = [];
      $definition = $filter->definition;
      $target_entity_type_id = $definition['verf_target_entity_type_id'];
      $storage = $this->entityTypeManager->getStorage($target_entity_type_id);
      foreach ($filter->value as $index => $index_value) {
        if (empty($index_value)) {
          continue;
        }
        if ($referenced_entity = $storage->load($index_value)) {
          $values[] = [
            'id' => $index,
            'raw' => $referenced_entity->id(),
            'value' => $referenced_entity->label(),
          ];
        }
      }
      $info['value'] = $values;
    }
  }

}
