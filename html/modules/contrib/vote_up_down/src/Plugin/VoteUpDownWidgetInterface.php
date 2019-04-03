<?php

namespace Drupal\vud\Plugin;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Vote Up/Down Widget plugins.
 */
interface VoteUpDownWidgetInterface extends PluginInspectionInterface {

  /**
   * Returns the label of the specific plugin instance
   *
   * @return mixed
   */
  public function getWidgetId();

  /**
   * Returns the widget template for a specific plugin instance
   *
   * @return mixed
   */
  public function getWidgetTemplate();

  /**
   *
   * Array containing all info for a plugin instance
   * @param $variables
   *
   * @return mixed
   */
  public function alterTemplateVariables(&$variables);

  /**
   * Returns the path to the widget template to be used.
   *
   * @param $base_path
   * @param $widget_template
   * @param $variables
   *
   * @return array
   */
  public function getWidgetTemplateVars($base_path, $widget_template, &$variables);

  /**
   * Returns renderable array for the plugin
   *
   * @param $entity EntityInterface
   *
   * @return array
   */
  public function build($entity);

}
