<?php

namespace Drupal\views_bootstrap;

use Drupal\Component\Utility\Html;
use Drupal\views\ViewExecutable;

/**
 * The primary class for the Views Bootstrap module.
 *
 * Provides many helper methods.
 *
 * @ingroup utility
 */
class ViewsBootstrap {

  /**
   * Returns the theme hook definition information.
   */
  public static function getThemeHooks(): array {
    $hooks['views_bootstrap_accordion'] = [
      'preprocess functions' => [
        'template_preprocess_views_bootstrap_accordion',
      ],
      'file' => 'views_bootstrap.theme.inc',
    ];
    $hooks['views_bootstrap_carousel'] = [
      'preprocess functions' => [
        'template_preprocess_views_bootstrap_carousel',
      ],
      'file' => 'views_bootstrap.theme.inc',
    ];
    $hooks['views_bootstrap_cards'] = [
      'preprocess functions' => [
        'template_preprocess_views_bootstrap_cards',
      ],
    ];
    $hooks['views_bootstrap_dropdown'] = [
      'preprocess functions' => [
        'template_preprocess_views_bootstrap_dropdown',
      ],
    ];
    $hooks['views_bootstrap_grid'] = [
      'preprocess functions' => [
        'template_preprocess_views_bootstrap_grid',
      ],
      'file' => 'views_bootstrap.theme.inc',
    ];
    $hooks['views_bootstrap_list_group'] = [
      'preprocess functions' => [
        'template_preprocess_views_bootstrap_list_group',
      ],
      'file' => 'views_bootstrap.theme.inc',
    ];
    $hooks['views_bootstrap_media_object'] = [
      'preprocess functions' => [
        'template_preprocess_views_bootstrap_media_object',
      ],
      'file' => 'views_bootstrap.theme.inc',
    ];
    $hooks['views_bootstrap_tab'] = [
      'preprocess functions' => [
        'template_preprocess_views_bootstrap_tab',
      ],
      'file' => 'views_bootstrap.theme.inc',
    ];
    $hooks['views_bootstrap_table'] = [
      'preprocess functions' => [
        'template_preprocess_views_view_table',
        'template_preprocess_views_bootstrap_table',
      ],
      'file' => 'views_bootstrap.theme.inc',
    ];

    return $hooks;
  }

  /**
   * Return an array of breakpoint names.
   */
  public static function getBreakpoints(): array {
    return ['xs', 'sm', 'md', 'lg', 'xl', 'xxl'];
  }

  /**
   * Get column class prefix for the breakpoint.
   */
  public static function getColumnPrefix($breakpoint): string {
    return 'col' . ($breakpoint != 'xs' ? '-' . $breakpoint : '');
  }

  /**
   * Get unique element id.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   A ViewExecutable object.
   *
   * @return string
   *   A unique id for an HTML element.
   */
  public static function getUniqueId(ViewExecutable $view): string {
    $id = $view->storage->id() . '-' . $view->current_display;
    return Html::getUniqueId('views-bootstrap-' . $id);
  }

}
