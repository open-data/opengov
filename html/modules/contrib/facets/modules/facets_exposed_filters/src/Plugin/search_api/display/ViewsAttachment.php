<?php

namespace Drupal\facets_exposed_filters\Plugin\search_api\display;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\search_api\Plugin\search_api\display\ViewsDisplayBase;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Represents a Views attachment display.
 *
 * @SearchApiDisplay(
 *   id = "views_attachment",
 *   views_display_type = "attachment",
 *   deriver = "Drupal\search_api\Plugin\search_api\display\ViewsDisplayDeriver"
 * )
 */
class ViewsAttachment extends ViewsDisplayBase implements ContainerFactoryPluginInterface {

  /**
   * The current path service.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $display */
    $display = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $display->currentPath = $container->get('path.current');
    return $display;
  }

  /**
   * {@inheritdoc}
   */
  public function isRenderedInCurrentRequest() {
    // Attachment displays are always rendered as part of their parent display.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    $plugin_definition = $this->getPluginDefinition();

    // Get the executable view to access display handlers.
    $executable_view = Views::getView($plugin_definition['view_id']);
    $executable_view->setDisplay($plugin_definition['view_display']);

    // Get the displays this attachment is attached to.
    $attachment_display = $executable_view->display_handler;
    $displays = $attachment_display->getOption('displays');

    if (!empty($displays)) {
      foreach ($displays as $display_id) {
        if (isset($executable_view->displayHandlers[$display_id])) {
          $display = $executable_view->displayHandlers[$display_id];
          if (method_exists($display, 'getPath')) {
            return $display->getPath();
          }
        }
      }
    }

    // Fallback to the current path.
    return $this->currentPath->getPath();
  }

}
