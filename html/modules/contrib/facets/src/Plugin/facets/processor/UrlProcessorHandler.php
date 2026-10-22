<?php

namespace Drupal\facets\Plugin\facets\processor;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\facets\Exception\InvalidProcessorException;
use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\facets\Processor\PreQueryProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;
use Drupal\facets\UrlProcessor\UrlProcessorPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The URL processor handler triggers the actual url processor.
 *
 * The URL processor handler allows managing the weight of the actual URL
 * processor per Facet. This handler will trigger the actual.
 *
 * @FacetsUrlProcessor, which can be configured on the Facet source.
 *
 * @FacetsProcessor(
 *   id = "url_processor_handler",
 *   label = @Translation("URL handler"),
 *   description = @Translation("Trigger the URL processor, which is set in the facet source configuration. You only must select this if a facet is not used as Views exposed filter."),
 *   stages = {
 *     "pre_query" = 50,
 *     "build" = 15,
 *   },
 *   locked = true
 * )
 */
class UrlProcessorHandler extends ProcessorPluginBase implements BuildProcessorInterface, PreQueryProcessorInterface, ContainerFactoryPluginInterface {

  /**
   * The actual url processor used for handing urls.
   *
   * @var \Drupal\facets\UrlProcessor\UrlProcessorInterface
   */
  protected $processor;

  /**
   * The URL processor plugin manager.
   *
   * @var \Drupal\facets\UrlProcessor\UrlProcessorPluginManager
   */
  protected $urlProcessorManager;

  /**
   * Gets the Processor.
   *
   * @return \Drupal\facets\UrlProcessor\UrlProcessorInterface
   *   The Processor.
   */
  public function getProcessor() {
    return $this->processor;
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, UrlProcessorPluginManager $url_processor_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->urlProcessorManager = $url_processor_manager;

    if (!isset($configuration['facet']) || !$configuration['facet'] instanceof FacetInterface) {
      throw new InvalidProcessorException("The UrlProcessorHandler doesn't have the required 'facet' in the configuration array.");
    }

    /** @var \Drupal\facets\FacetInterface $facet */
    $facet = $configuration['facet'];

    /** @var \Drupal\facets\FacetSourceInterface $fs */
    $fs = $facet->getFacetSourceConfig();

    $url_processor_name = $fs->getUrlProcessorName();

    $this->processor = $this->urlProcessorManager->createInstance($url_processor_name, ['facet' => $facet]);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.facets.url_processor')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results) {
    return $this->processor->buildUrls($facet, $results);
  }

  /**
   * {@inheritdoc}
   */
  public function preQuery(FacetInterface $facet) {
    $this->processor->setActiveItems($facet);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return CacheableMetadata::createFromObject($this->processor)->getCacheTags();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return CacheableMetadata::createFromObject($this->processor)->getCacheContexts();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return CacheableMetadata::createFromObject($this->processor)->getCacheMaxAge();
  }

}
