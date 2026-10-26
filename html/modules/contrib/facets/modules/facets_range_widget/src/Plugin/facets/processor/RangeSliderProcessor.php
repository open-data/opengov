<?php

namespace Drupal\facets_range_widget\Plugin\facets\processor;

use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\facets\Processor\PreQueryProcessorInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\facets\Utility\FacetsUrlGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a processor that adds all range values between an min and max range.
 *
 * @FacetsProcessor(
 *   id = "range_slider",
 *   label = @Translation("Range slider"),
 *   description = @Translation("Add range results for all the steps between min and max range."),
 *   stages = {
 *     "pre_query" =60,
 *     "post_query" = 60,
 *     "build" = 20
 *   }
 * )
 */
class RangeSliderProcessor extends SliderProcessor implements PreQueryProcessorInterface, BuildProcessorInterface, ContainerFactoryPluginInterface {

  /**
   * The URL generator.
   *
   * @var \Drupal\facets\Utility\FacetsUrlGenerator
   */
  protected $urlGenerator;

  /**
   * Constructs a new RangeSliderProcessor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FacetsUrlGenerator $url_generator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->urlGenerator = $url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('facets.utility.url_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function preQuery(FacetInterface $facet) {
    $active_items = $facet->getActiveItems();

    array_walk($active_items, function (&$item) {
      if (preg_match('/\(min:((?:-)?[\d\.]+),max:((?:-)?[\d\.]+)\)/i', $item, $matches)) {
        $item = [$matches[1], $matches[2]];
      }
      else {
        $item = NULL;
      }
    });
    $facet->setActiveItems($active_items);
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results) {
    /** @var \Drupal\facets\Plugin\facets\processor\UrlProcessorHandler $url_processor_handler */
    $url_processor_handler = $facet->getProcessors()['url_processor_handler'];
    $url_processor = $url_processor_handler->getProcessor();
    $active_filters = $url_processor->getActiveFilters();

    if (isset($active_filters[''])) {
      unset($active_filters['']);
    }

    /** @var \Drupal\facets\Result\ResultInterface[] $results */
    foreach ($results as &$result) {
      $new_active_filters = $active_filters;
      unset($new_active_filters[$facet->id()]);
      // Add one generic query filter with the min and max placeholder.
      $new_active_filters[$facet->id()][] = '(min:__range_slider_min__,max:__range_slider_max__)';
      $url = $this->urlGenerator->getUrl($new_active_filters, FALSE);
      $result->setUrl($url);
    }

    return $results;
  }

}
