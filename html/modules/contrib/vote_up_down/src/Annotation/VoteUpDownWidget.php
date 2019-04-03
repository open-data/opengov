<?php

namespace Drupal\vud\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Vote Up/Down Widget annotation object.
 *
 * @see \Drupal\vud\Plugin\VoteUpDownWidgetManager
 * @see plugin_api
 *
 * @Annotation
 */
class VoteUpDownWidget extends Plugin {

  /**
   * Machine name of a plugin.
   *
   * @var string
   */
  public $id;

  /**
   * Human readable label of a widget.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $admin_label;

  /**
   * Human readable description of a widget.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * Widget's Template file name (without extension).
   *
   * @var string
   */
  public $widget_template = "widget";

  /**
   * Vote's Template file name (without extension).
   *
   * @var string
   */
  public $votes_template = "";

}
