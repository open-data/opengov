<?php

namespace Drupal\facets_custom_widget\Plugin\facets\processor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\State\StateInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\PreQueryProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The URL processor handler triggers the actual url processor.
 *
 * @FacetsProcessor(
 *   id = "test_pre_query",
 *   label = @Translation("test pre query"),
 *   description = @Translation("TEST pre query"),
 *   stages = {
 *     "pre_query" = 50
 *   }
 * )
 */
class TestPreQuery extends ProcessorPluginBase implements PreQueryProcessorInterface, ContainerFactoryPluginInterface {

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new TestPreQuery plugin.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MessengerInterface $messenger, StateInterface $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->messenger = $messenger;
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
      $container->get('messenger'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    return [
      'test_value' => [
        '#type' => 'textfield',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryType() {
    return 'string';
  }

  /**
   * {@inheritdoc}
   */
  public function preQuery(FacetInterface $facet) {
    $this->messenger->addMessage($this->getConfiguration()['test_value']);
  }

  /**
   * {@inheritdoc}
   */
  public function supportsFacet(FacetInterface $facet) {
    return $this->state->get('facets_test_supports_facet', TRUE);
  }

}
