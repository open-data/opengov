<?php

namespace Drupal\feeds\Feeds\Target;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\Exception\ReferenceNotFoundException;
use Drupal\feeds\Exception\TargetValidationException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\FieldTargetDefinition;
use Drupal\feeds\Plugin\Type\Target\ConfigurableTargetInterface;
use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;

/**
 * Defines an entity reference mapper.
 *
 * @FeedsTarget(
 *   id = "entity_reference",
 *   field_types = {"entity_reference"},
 *   arguments = {
 *     "@entity_type.manager",
 *     "@entity.query",
 *     "@entity_field.manager",
 *     "@entity.repository",
 *   }
 * )
 */
class EntityReference extends FieldTargetBase implements ConfigurableTargetInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity query factory object.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Constructs a new EntityReference object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The entity query factory.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entity_type_manager, QueryFactory $query_factory, EntityFieldManagerInterface $entity_field_manager, EntityRepositoryInterface $entity_repository) {
    $this->entityTypeManager = $entity_type_manager;
    $this->queryFactory = $query_factory;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityRepository = $entity_repository;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget(FieldDefinitionInterface $field_definition) {
    // Only reference content entities. Configuration entities will need custom
    // targets.
    $type = $field_definition->getSetting('target_type');
    if (!\Drupal::entityTypeManager()->getDefinition($type)->entityClassImplements('\Drupal\Core\Entity\ContentEntityInterface')) {
      return;
    }

    return FieldTargetDefinition::createFromFieldDefinition($field_definition)
      ->addProperty('target_id');
  }

  /**
   * {@inheritdoc}
   */
  public function setTarget(FeedInterface $feed, EntityInterface $entity, $field_name, array $raw_values) {
    $values = [];
    foreach ($raw_values as $delta => $columns) {
      try {
        $this->prepareValue($delta, $columns);
        $values[] = $columns;
      }
      catch (ReferenceNotFoundException $e) {
        // The referenced entity is not found. We need to enforce Feeds to try
        // to import the same item again on the next import.
        // Feeds stores a hash of every imported item in order to make the
        // import process more efficient by ignoring items it has already seen.
        // In this case we need to destroy the hash in order to be able to
        // import the reference on a next import.
        $entity->get('feeds_item')->hash = NULL;
      }
      catch (EmptyFeedException $e) {
        // Nothing wrong here.
      }
      catch (TargetValidationException $e) {
        // Validation failed.
        $this->addMessage($e->getFormattedMessage(), 'error');
      }
    }

    if (!empty($values)) {
      $item_list = $entity->get($field_name);

      // Append these values to the existing values.
      $values = array_merge($item_list->getValue(), $values);

      $item_list->setValue($values);
    }
  }

  /**
   * Returns a list of fields that may be used to reference by.
   *
   * @return array
   *   A list subfields of the entity reference field.
   */
  protected function getPotentialFields() {
    $field_definitions = $this->entityFieldManager->getFieldStorageDefinitions($this->getEntityType());
    $field_definitions = array_filter($field_definitions, [$this, 'filterFieldTypes']);
    $options = [];
    foreach ($field_definitions as $id => $definition) {
      $options[$id] = Html::escape($definition->getLabel());
    }

    return $options;
  }

  /**
   * Callback for the potential field filter.
   *
   * Checks whether the provided field is available to be used as reference.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $field
   *   The field to check.
   *
   * @return bool
   *   TRUE if the field can be used as reference otherwise FALSE.
   *
   * @see ::getPotentialFields()
   */
  protected function filterFieldTypes(FieldStorageDefinitionInterface $field) {
    if ($field instanceof DataDefinitionInterface && $field->isComputed()) {
      return FALSE;
    }

    switch ($field->getType()) {
      case 'integer':
      case 'string':
      case 'text_long':
      case 'path':
      case 'uuid':
      case 'feeds_item':
        return TRUE;

      default:
        return FALSE;
    }
  }

  /**
   * Returns the entity type to reference.
   *
   * @return string
   *   The entity type to reference.
   */
  protected function getEntityType() {
    return $this->settings['target_type'];
  }

  /**
   * Returns a list of bundles that may be referenced.
   *
   * If there are no target bundles configured on the entity reference field, an
   * empty array is returned.
   *
   * @return array
   *   Bundles that are allowed to be referenced.
   */
  protected function getBundles() {
    if (!empty($this->settings['handler_settings']['target_bundles'])) {
      return $this->settings['handler_settings']['target_bundles'];
    }
    return [];
  }

  /**
   * Returns the entity type's bundle key.
   *
   * @return string
   *   The bundle key of the entity type.
   */
  protected function getBundleKey() {
    return $this->entityTypeManager->getDefinition($this->getEntityType())->getKey('bundle');
  }

  /**
   * Returns the entity type's label key.
   *
   * @return string
   *   The label key of the entity type.
   */
  protected function getLabelKey() {
    return $this->entityTypeManager->getDefinition($this->getEntityType())->getKey('label');
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    // Check if there is a value for target ID.
    if (!isset($values['target_id']) || strlen(trim($values['target_id'])) === 0) {
      // No value.
      throw new EmptyFeedException();
    }

    if ($this->configuration['reference_by'] == 'feeds_item') {
      switch ($this->configuration['feeds_item']) {
        case 'guid':
          if ($target_id = $this->findEntityByGuid($this->getEntityType(), $values['target_id'])) {
            $values['target_id'] = $target_id;
            return;
          }
          break;
      }
    }
    else {
      if ($target_id = $this->findEntity($values['target_id'], $this->configuration['reference_by'])) {
        $values['target_id'] = $target_id;
        return;
      }
    }

    throw new ReferenceNotFoundException();
  }

  /**
   * Searches for an entity by its Feed item's GUID value.
   *
   * @param string $entity_type
   *   The entity type with the feeds_item field.
   * @param string $guid
   *   The GUID value to look for inside the entity's feed item field.
   *
   * @return int|null
   *   The entity id, or false, if not found.
   */
  protected function findEntityByGuid($entity_type, $guid) {
    // Check if the target entity type has a 'feeds_item' field.
    $field_definitions = $this->entityFieldManager->getFieldStorageDefinitions($entity_type);
    if (!isset($field_definitions['feeds_item']) || $field_definitions['feeds_item']->getType() != 'feeds_item') {
      // No feeds_item field found. Abort to prevent a fatal error.
      return NULL;
    }

    $items = $this->queryFactory->get($entity_type)
      ->condition('feeds_item.guid', $guid)
      ->execute();
    if (!empty($items)) {
      return (int) reset($items);
    }

    return NULL;
  }

  /**
   * Searches for an entity by entity key.
   *
   * @param string $value
   *   The value to search for.
   * @param string $field
   *   The subfield to search in.
   *
   * @return int|bool
   *   The entity id, or false, if not found.
   */
  protected function findEntity($value, $field) {
    // When referencing by UUID, use the EntityRepository service.
    if ($field === 'uuid') {
      if (NULL !== ($entity = $this->entityRepository->loadEntityByUuid($this->getEntityType(), $value))) {
        return $entity->id();
      }
    }
    else {
      $query = $this->queryFactory->get($this->getEntityType());

      if ($bundles = $this->getBundles()) {
        $query->condition($this->getBundleKey(), $bundles, 'IN');
      }

      $ids = array_filter($query->condition($field, $value)->range(0, 1)->execute());
      if ($ids) {
        return reset($ids);
      }
    }

    if ($this->configuration['autocreate'] && $this->configuration['reference_by'] === $this->getLabelKey()) {
      return $this->createEntity($value);
    }

    return FALSE;
  }

  /**
   * Creates a new entity with the given label and saves it.
   *
   * @param string $label
   *   The label the new entity should get.
   *
   * @return int|string|false
   *   The ID of the new entity or false if the given label is empty.
   */
  protected function createEntity($label) {
    if (!strlen(trim($label))) {
      return FALSE;
    }

    $bundles = $this->getBundles();

    $entity = $this->entityTypeManager->getStorage($this->getEntityType())->create([
      $this->getLabelKey() => $label,
      $this->getBundleKey() => reset($bundles),
    ]);
    $entity->save();

    return $entity->id();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = [
      'reference_by' => $this->getLabelKey(),
      'autocreate' => FALSE,
    ];
    if (array_key_exists('feeds_item', $this->getPotentialFields())) {
      $config['feeds_item'] = FALSE;
    }
    return $config;
  }

  /**
   * Returns options for feeds_item configuration.
   */
  public function getFeedsItemOptions() {
    return [
      'guid' => $this->t('Item GUID'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options = $this->getPotentialFields();

    // Hack to find out the target delta.
    foreach ($form_state->getValues() as $key => $value) {
      if (strpos($key, 'target-settings-') === 0) {
        list(, , $delta) = explode('-', $key);
        break;
      }
    }

    $form['reference_by'] = [
      '#type' => 'select',
      '#title' => $this->t('Reference by'),
      '#options' => $options,
      '#default_value' => $this->configuration['reference_by'],
    ];

    $feed_item_options = $this->getFeedsItemOptions();

    $form['feeds_item'] = [
      '#type' => 'select',
      '#title' => $this->t('Feed item'),
      '#options' => $feed_item_options,
      '#default_value' => $this->getConfiguration('feeds_item'),
      '#states' => [
        'visible' => [
          ':input[name="mappings[' . $delta . '][settings][reference_by]"]' => [
            'value' => 'feeds_item',
          ],
        ],
      ],
    ];

    $form['autocreate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Autocreate entity'),
      '#default_value' => $this->configuration['autocreate'],
      '#states' => [
        'visible' => [
          ':input[name="mappings[' . $delta . '][settings][reference_by]"]' => [
            'value' => $this->getLabelKey(),
          ],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $options = $this->getPotentialFields();

    $summary = [];

    if ($this->configuration['reference_by'] && isset($options[$this->configuration['reference_by']])) {
      $summary[] = $this->t('Reference by: %message', ['%message' => $options[$this->configuration['reference_by']]]);
      if ($this->configuration['reference_by'] == 'feeds_item') {
        $feed_item_options = $this->getFeedsItemOptions();
        $summary[] = $this->t('Feed item: %feed_item', ['%feed_item' => $feed_item_options[$this->configuration['feeds_item']]]);
      }
    }
    else {
      $summary[] = $this->t('Please select a field to reference by.');
    }

    if ($this->configuration['reference_by'] === $this->getLabelKey()) {
      $create = $this->configuration['autocreate'] ? $this->t('Yes') : $this->t('No');
      $summary[] = $this->t('Autocreate terms: %create', ['%create' => $create]);
    }

    return implode('<br>', $summary);
  }

}
