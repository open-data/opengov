<?php

/**
 * @file
 * Theme functions
 */

define('VUD_WIDGET_MESSAGE_ERROR', 0);
define('VUD_WIDGET_MESSAGE_DENIED', 1);

/**
 * Implementation of hook_theme().
 */
function vud_theme() {

  $module_handler = \Drupal::service('module_handler');
  $module_path = $module_handler->getModule('vud')->getPath();

  return [
    'vud_widget' => [
      'variables' => [
        'id' => NULL,
        'entity_id' => NULL,
        'entity_type_id' => NULL,
        'show_links' => TRUE,
        'show_up_as_link' => TRUE,
        'show_down_as_link' => TRUE,
        'show_reset' => TRUE,
        'link_up' => NULL,
        'link_down' => NULL,
        'link_reset' => NULL,
        'class_up' => TRUE,
        'class_down' => TRUE,
        'class_reset' => TRUE,
        'link_class_up' => NULL,
        'link_class_down' => NULL,
        'link_class_reset' => NULL,
        'reset_short_text' => NULL,
        'reset_long_text' => NULL,
        'up_points' => 0,
        'down_points' => 0,
        'unsigned_points' => 0,
        'points' => 0,
        'vote_sum' => 0,
        'widget_template' => 'alternate',
        'template_path' => NULL,
        'base_path' => $module_path,
      ],
    ],
  ];
}
