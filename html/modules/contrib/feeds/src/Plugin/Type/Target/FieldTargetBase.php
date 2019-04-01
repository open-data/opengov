<?php

namespace Drupal\feeds\Plugin\Type\Target;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\Exception\TargetValidationException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\FieldTargetDefinition;
use Drupal\feeds\FeedTypeInterface;
use Drupal\feeds\Plugin\Type\Processor\EntityProcessorInterface;
use LogicException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\Plugin\Type\Target\ConfigurableTargetInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Helper class for field mappers.
 */
abstract class FieldTargetBase extends TargetBase implements ConfigurableTargetInterface {

  /**
   * The field settings.
   *
   * @var array
   */
  protected $fieldSettings;

  /**
   * {@inheritdoc}
   */
  public static function targets(array &$targets, FeedTypeInterface $feed_type, array $definition) {
    $processor = $feed_type->getProcessor();

    if (!$processor instanceof EntityProcessorInterface) {
      return $targets;
    }

    $field_definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions($processor->entityType(), $processor->bundle());

    foreach ($field_definitions as $id => $field_definition) {
      if ($field_definition->isReadOnly() || $id === $processor->bundleKey()) {
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
   * Prepares a target definition.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   *
   * @return \Drupal\feeds\FieldTargetDefinition
   *   The target definition.
   */
  protected static function prepareTarget(FieldDefinitionInterface $field_definition) {
    return FieldTargetDefinition::createFromFieldDefinition($field_definition)
      ->addProperty('value');
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    $this->targetDefinition = $configuration['target_definition'];
    $this->settings = $this->targetDefinition->getFieldDefinition()->getSettings();
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function setTarget(FeedInterface $feed, EntityInterface $entity, $field_name, array $values) {
    if ($values = $this->prepareValues($values)) {
      $entity_target = $this->getEntityTarget($feed, $entity);
      if (!empty($entity_target)) {
        $item_list = $entity_target->get($field_name);

        // Append these values to the existing values.
        if (!empty($item_list->getValue()) && $item_list->getFieldDefinition()->getCardinality() > 1) {
          $values = array_merge($item_list->getValue(), $values);
        }

        if (is_array($values) && ($this->valuesAreEmpty($values) && $field_name == $entity->getEntityType()->getKey('label') && !empty($entity_target->get($field_name)->value))) {
          return;
        }

        $item_list->setValue($values);
      }
    }
  }

  /**
   * Check current field has value.
   *
   * This is done because we don't want set empty values to entity.
   *
   * @return bool
   *   TRUE if field has value.
   */
  public function valuesAreEmpty(array $values) {
    $empty = TRUE;
    foreach ($values as $value) {
      foreach ($value as $val) {
        if (!empty($val)) {
          $empty = FALSE;
          break 2;
        }
      }
    }
    return $empty;
  }

  public function languageExists() {
    $language_manager = \Drupal::service('language_manager');
    return $language_manager->getLanguage($this->configuration['language']) instanceof LanguageInterface;
  }

  /**
   * Get entity , or entity translation to set the map.
   *
   * @param FeedInterface $feed
   *   Feed.
   * @param EntityInterface $entity
   *   Entity.
   *
   * @see \Drupal\feeds\Plugin\Type\Target\FieldTargetBase::isTargetTranslatable()
   *
   * @return EntityInterface
   *   Self entity or entity translation.
   */
  public function getEntityTarget(FeedInterface $feed, EntityInterface $entity) {
    if ($entity instanceof TranslatableInterface &&
      $this->isTargetTranslatable()) {
      if ($this->languageExists()) {
        $this->ensureEntityTranslation($feed, $entity);
        $entity_target = $entity->getTranslation($this->configuration['language']);
      }
      else {
        $entity_target = NULL;
      }
    }
    else {
      $entity_target = $entity;
    }
    return $entity_target;
  }

  /**
   * We ensure the entity has the translation needed for this plugin.
   *
   * @param FeedInterface $feed
   *   Feed.
   * @param EntityInterface $entity
   *   Entity.
   */
  public function ensureEntityTranslation(FeedInterface $feed, EntityInterface $entity) {
    if (!$entity->hasTranslation($this->configuration['language'])) {
      $translation = $entity->addTranslation($this->configuration['language']);
      $processor = $feed->getType()
        ->getProcessor();
      if ($translation instanceof EntityOwnerInterface) {
        if ($processor->isOwnerFeedAuthor()) {
          $translation->setOwnerId($feed->getOwnerId());
        }
        else {
          $translation->setOwnerId($processor->getOwnerId());
        }
      }
    }
  }

  /**
   * Prepares the the values that will be mapped to an entity.
   *
   * @param array $values
   *   The values.
   */
  protected function prepareValues(array $values) {
    $return = [];
    foreach ($values as $delta => $columns) {
      try {
        $this->prepareValue($delta, $columns);
        $return[] = $columns;
      }
      catch (EmptyFeedException $e) {
        // Nothing wrong here.
      }
      catch (TargetValidationException $e) {
        // Validation failed.
        $this->addMessage($e->getFormattedMessage(), 'error');
      }
    }

    return $return;
  }

  /**
   * Prepares a single value.
   *
   * @param int $delta
   *   The field delta.
   * @param array $values
   *   The values.
   */
  protected function prepareValue($delta, array &$values) {
    foreach ($values as $column => $value) {
      $values[$column] = (string) $value;
    }
  }

  /**
   * Constructs a base query which is used to find an existing entity.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   An entity query.
   *
   * @see ::getUniqueValue()
   */
  protected function getUniqueQuery() {
    return \Drupal::entityQuery($this->feedType->getProcessor()->entityType())
      ->range(0, 1);
  }

  /**
   * Looks for an existing entity and returns an entity ID if found.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed that is being processed.
   * @param string $target
   *   The ID of the field target plugin.
   * @param string $key
   *   The property of the field to search on.
   * @param string $value
   *   The value to look for.
   *
   * @return string|int|null
   *   An entity ID, if found. Null otherwise.
   */
  public function getUniqueValue(FeedInterface $feed, $target, $key, $value) {
    $base_fields = \Drupal::service('entity_field.manager')->getBaseFieldDefinitions($this->feedType->getProcessor()->entityType());

    if (isset($base_fields[$target])) {
      $field = $target;
    }
    else {
      $field = "$target.$key";
    }
    if ($result = $this->getUniqueQuery()->condition($field, $value)->execute()) {
      return reset($result);
    }
  }

  /**
   * Returns the messenger to use.
   *
   * @return \Drupal\Core\Messenger\MessengerInterface
   *   The messenger service.
   *
   * @throws \LogicException
   *   In case the messinger does not exist (we're on < Drupal core 8.5.0).
   */
  protected function getMessenger() {
    if (!interface_exists('\Drupal\Core\Messenger\MessengerInterface')) {
      throw new LogicException('Messenger not found. Install Drupal core 8.5.0 or later.');
    }
    return \Drupal::messenger();
  }

  /**
   * Adds a message.
   *
   * @param string|\Drupal\Component\Render\MarkupInterface $message
   *   The translated message to be displayed to the user.
   * @param string $type
   *   (optional) The message's type.
   * @param bool $repeat
   *   (optional) If this is FALSE and the message is already set, then the
   *   message won't be repeated. Defaults to FALSE.
   */
  protected function addMessage($message, $type = 'status', $repeat = FALSE) {
    try {
      $this->getMessenger()->addMessage($message, $type, $repeat);
    }
    catch (LogicException $e) {
      // Backwards compatibility with Drupal core < 8.5.0.
      // @todo remove once Drupal core 8.6.0 is released.
      drupal_set_message($message, $type, $repeat);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $this->dependencies = parent::calculateDependencies();

    // Add the configured field as a dependency.
    $field_definition = $this->targetDefinition
      ->getFieldDefinition();
    if ($field_definition && $field_definition instanceof EntityInterface) {
      $this->dependencies['config'][] = $field_definition->getConfigDependencyName();
    }

    return $this->dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    // See if this target is responsible for any of the dependencies being
    // removed. If this is the case, indicate that the mapping that uses this
    // target needs to be removed from the feed type.
    $remove = FALSE;
    // Get all the current dependencies for this target.
    $current_dependencies = $this->calculateDependencies();
    foreach ($current_dependencies as $group => $dependency_list) {
      // Check if any of the target dependencies match the dependencies being
      // removed.
      foreach ($dependency_list as $config_key) {
        if (isset($dependencies[$group]) && array_key_exists($config_key, $dependencies[$group])) {
          // This targets dependency matches a dependency being removed,
          // indicate that mapping using this target needs to be removed.
          $remove = TRUE;
          break 2;
        }
      }
    }
    return $remove;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = [];
    if ($this->isTargetFieldTranslatable()) {
      $configuration['language'] = NULL;
    }
    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $summary = NULL;
    if ($this->isTargetTranslatable()) {
      $language_manager = \Drupal::service('language_manager');
      $language = $language_manager->getLanguage($this->configuration['language']);
      if ($language instanceof LanguageInterface) {
        $summary = $this->t('Language: @language', ['@language' => $language->getName()]);
      }
    }
    return $this->showSummary($summary);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    if ($this->isTargetFieldTranslatable()) {
      $language_manager = \Drupal::service('language_manager');
      $languages = $language_manager->getLanguages();
      $options = [
        '' => $this->t('Default'),
      ];
      foreach ($languages as $langcode => $language) {
        $options[$langcode] = $language->getName();
      }
      $language_default = !empty($this->configuration['language']) ? $this->configuration['language'] : '';
      $form['language'] = [
        '#title'   => $this->t('Language'),
        '#options' => $options,
        '#type'    => 'select',
        '#default_value' => $language_default,
      ];
    }
    return $form;
  }

  /**
   * 
   */
  public function isTargetFieldTranslatable() {
    $field_storage = $this->targetDefinition->getFieldDefinition()->getFieldStorageDefinition();
    return !empty($field_storage) && $field_storage->isTranslatable();
  }

  /**
   * Check if target is translatable.
   *
   * Target is translatable when a language is set in configuration.
   *
   * @return bool
   *   TRUE if target translatable.
   */
  public function isTargetTranslatable() {
    return $this->isTargetFieldTranslatable()
      && !empty($this->configuration['language']);
  }

  /**
   * Get language.
   *
   * @return string
   *   Language.
   */
  public function getLanguage() {
    return !empty($this->configuration['language']) ? $this->configuration['language'] : 'default';
  }

  /**
   * Show summary from configuration.
   *
   * If summary is array separate config by break lines.
   *
   * @param mixed $summary
   *   Summary. It can be array or single value (int, string...).
   *
   * @return string
   *   Summary ready to view at UI.
   */
  public function showSummary($summary) {
    return is_array($summary) ? implode('<br>', array_filter($summary)) : $summary;
  }

}
