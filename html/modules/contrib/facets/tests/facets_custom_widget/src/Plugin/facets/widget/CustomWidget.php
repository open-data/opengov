<?php

namespace Drupal\facets_custom_widget\Plugin\facets\widget;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\State\StateInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\Widget\WidgetPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A simple widget class that returns a simple array of the facet results.
 *
 * @FacetsWidget(
 *   id = "custom_widget",
 *   label = @Translation("Custom widget"),
 *   description = @Translation("Custom widget"),
 * )
 */
class CustomWidget extends WidgetPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $url_processor_manager, StateInterface $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $url_processor_manager);
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.facets.url_processor'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isPropertyRequired($name, $type) {
    if ($type == 'processors' && $name == 'hide_non_narrowing_result_processor') {
      return TRUE;
    }
    if ($type == 'settings' && $name == 'show_only_one_result') {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsFacet(FacetInterface $facet) {
    return $this->state->get('facets_test_supports_facet', TRUE);
  }

}
