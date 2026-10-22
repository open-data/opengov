<?php

// phpcs:ignoreFile SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Drupal\search_api_solr_test\TypedData;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\ListDataDefinition;

/**
 * A typed data definition class for describing widgets.
 */
class WidgetDefinition extends ComplexDataDefinitionBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    $this->propertyDefinitions['widget_types'] = ListDataDefinition::create('string')
      ->setLabel('Widget Types');
    return $this->propertyDefinitions;
  }

}
