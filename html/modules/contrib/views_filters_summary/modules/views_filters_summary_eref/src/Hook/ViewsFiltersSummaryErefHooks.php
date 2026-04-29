<?php

namespace Drupal\views_filters_summary_eref\Hook;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for views_filters_summary_eref.
 */
class ViewsFiltersSummaryErefHooks {

  /**
   * Constructs a new ViewsFiltersSummaryErefHooks object.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Implements hook_views_filters_summary_info_alter().
   */
  #[Hook('views_filters_summary_info_alter')]
  public function infoAlter(&$info, $filter) {
    if ($filter->getPluginId() === 'eref_node_titles') {
      $values = [];
      $storage = $this->entityTypeManager->getStorage('node');
      foreach ($filter->value as $index => $nid) {
        if ($node = $storage->load($nid)) {
          $values[] = [
            'id' => $index,
            'raw' => $node->id(),
            'value' => $node->label(),
          ];
        }
      }
      $info['value'] = $values;
    }
  }

}
