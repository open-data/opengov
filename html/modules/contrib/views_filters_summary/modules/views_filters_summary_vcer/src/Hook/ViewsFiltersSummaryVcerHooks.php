<?php

namespace Drupal\views_filters_summary_vcer\Hook;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Hook implementations for views_filters_summary_vcer.
 */
class ViewsFiltersSummaryVcerHooks {

  /**
   * Constructs a new ViewsFiltersSummaryVcerHooks object.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Implements hook_views_filters_summary_filter_value_label_alter().
   */
  #[Hook('views_filters_summary_filter_value_label_alter')]
  public function filterValueLabelAlter(
    string &$label,
    string &$value,
    FilterPluginBase $filter,
  ) {
    if ($filter->getPluginId() === 'entity_reference') {
      $entity_type_id = $filter->getEntityType();
      $entity = $this->entityTypeManager->getStorage($entity_type_id)->load($value);
      if ($entity) {
        $label = $entity->label();
      }
    }
  }

}
