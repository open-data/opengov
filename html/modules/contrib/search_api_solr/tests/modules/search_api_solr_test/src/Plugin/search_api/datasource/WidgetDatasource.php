<?php

// phpcs:ignoreFile SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Drupal\search_api_solr_test\Plugin\search_api\datasource;

use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\search_api\Attribute\SearchApiDatasource;
use Drupal\search_api\Datasource\DatasourcePluginBase;

/**
 * Represents a datasource which exposes widgets.
 */
#[SearchApiDatasource(
  id: 'search_api_solr_test_widget',
  label: new TranslatableMarkup('Widgets'),
  description: new TranslatableMarkup('A test widget.'),
)]
class WidgetDatasource extends DatasourcePluginBase {

  /**
   * The typed data manager.
   */
  protected TypedDataManagerInterface $typedDataManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $plugin */
    $plugin = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $plugin->typedDataManager = $container->get('typed_data_manager');
    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    $definition = $this->typedDataManager->createDataDefinition('search_api_solr_test_widget');
    assert($definition instanceof ComplexDataDefinitionInterface);
    return $definition->getPropertyDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function getItemId(ComplexDataInterface $item) {
    return 0;
  }

}
