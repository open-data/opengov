<?php

namespace Drupal\feeds\Feeds\Target;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\feeds\FeedTypeInterface;
use Drupal\feeds\FieldTargetDefinition;
use Drupal\feeds\Plugin\Type\Processor\EntityProcessorInterface;
use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;

/**
 * Defines a path field mapper.
 *
 * @FeedsTarget(
 *   id = "path",
 *   field_types = {"path"}
 * )
 */
class Path extends FieldTargetBase {

  /**
   * {@inheritdoc}
   *
   * This method is overridden to make the target available even when the
   * pathauto module is enabled.
   */
  public static function targets(array &$targets, FeedTypeInterface $feed_type, array $definition) {
    $processor = $feed_type->getProcessor();

    if (!$processor instanceof EntityProcessorInterface) {
      return $targets;
    }

    $field_definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions($processor->entityType(), $processor->bundle());

    foreach ($field_definitions as $id => $field_definition) {
      if ($id === $processor->bundleKey()) {
        continue;
      }
      if (in_array($field_definition->getType(), $definition['field_types'])) {
        if ($target = static::prepareTarget($field_definition)) {
          $target->setPluginId($definition['id']);
          $targets[$id] = $target;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget(FieldDefinitionInterface $field_definition) {
    return FieldTargetDefinition::createFromFieldDefinition($field_definition)
      ->addProperty('alias');
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    $values['alias'] = trim($values['alias']);
  }

}
