<?php

namespace Drupal\views_filters_summary_search_api\Hook;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\RendererInterface;

/**
 * Hook implementations for views_filters_summary_search_api.
 */
class ViewsFiltersSummarySearchApiHooks {

  /**
   * Constructs a new ViewsFiltersSummarySearchApiHooks.
   */
  public function __construct(
    protected RendererInterface $renderer,
  ) {}

  /**
   * Implements hook_views_filters_summary_plugin_alias().
   */
  #[Hook('views_filters_summary_plugin_alias')]
  public function pluginAlias($filter) {
    switch ($filter->getPluginId()) {
      case 'search_api_term':
        return 'taxonomy_index_tid';

      case 'search_api_options':
        return 'list_field';
    }
  }

  /**
   * Implements hook_views_filters_summary_replacements_alter().
   */
  #[Hook('views_filters_summary_replacements_alter')]
  public function replacementsAlter(&$replacements, $view) {
    foreach ($view->display_handler->handlers['filter'] as $filter) {
      if ($filter->getPluginId() === 'search_api_fulltext') {
        $identifier = $filter->options['expose']['identifier'];
        $exposed_data = $view->exposed_data[$identifier] ?? NULL;
        if (!empty($exposed_data)) {
          $replacements['search_api_fulltext'] = Html::escape($exposed_data);
        }
      }
    }
  }

  /**
   * Implements hook_form_alter().
   */
  #[Hook('form_alter')]
  public function formAlter(&$form, FormStateInterface $form_state, $form_id) {
    if ($form_id === 'views_ui_config_item_form') {
      $storage = $form_state->getStorage();
      if ($storage['id'] === 'views_filters_summary') {
        $item_list = [
          '#theme' => 'item_list',
          '#items' => [
            '@search_api_fulltext -- the fulltext search api filter value',
          ],
        ];
        $form['options']['content']['#description']
          .= $this->renderer->render($item_list);
      }
    }
  }

  /**
   * Implements hook_views_filters_summary_valid_index().
   */
  #[Hook('views_filters_summary_valid_index')]
  public function validIndex($index, $filter) {
    if ($filter->getPluginId() === 'search_api_options') {
      return !is_null($index);
    }
  }

}
