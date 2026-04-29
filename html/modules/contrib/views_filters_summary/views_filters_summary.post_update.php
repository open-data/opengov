<?php

/**
 * @file
 * Post-update functions for the views_filters_summary module.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\views\ViewEntityInterface;

/**
 * Transforms values of show remove/replace link values to boolean.
 */
function views_filters_summary_post_update_fix_show_remove_replace_link_type(array &$sandbox): void {
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'view', function (ViewEntityInterface $view): bool {
    $displays = $view->get('display');
    $changed = FALSE;
    /** @var \Drupal\views\Plugin\views\display\DisplayPluginInterface $display */
    foreach ($displays as &$display) {
      if (!empty($display['display_options']['header'])) {
        foreach ($display['display_options']['header'] as &$handler) {
          if (is_array($handler) && $handler['id'] === 'views_filters_summary') {
            $changed = TRUE;
            $handler['show_remove_link'] = (bool) $handler['show_remove_link'];
            $handler['show_reset_link'] = (bool) $handler['show_reset_link'];
          }
        }
        unset($handler);
      }
    }

    if ($changed) {
      $view->set('display', $displays);
    }

    return $changed;
  });
}
