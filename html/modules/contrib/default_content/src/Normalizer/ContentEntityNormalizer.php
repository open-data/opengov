<?php

namespace Drupal\default_content\Normalizer;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Plugin\DataType\EntityReference;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Installer\InstallerKernel;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\TypedData\DataReferenceTargetDefinition;
use Drupal\Core\TypedData\Plugin\DataType\Uri;
use Drupal\Core\TypedData\PrimitiveInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\TypedData\TypedDataInternalPropertiesHelper;
use Drupal\menu_link_content\MenuLinkContentInterface;
use Drupal\path\Plugin\Field\FieldType\PathItem;
use Drupal\pathauto\PathautoState;
use Drupal\serialization\Normalizer\SerializedColumnNormalizerTrait;
use Drupal\user\UserInterface;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

/**
 * Normalizes and denormalizes content entities.
 */
class ContentEntityNormalizer implements ContentEntityNormalizerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The dependency information.
   *
   * Build during normalization, set and used to load entities during
   * denormalization.
   *
   * @var array
   */
  protected $dependencies;

  /**
   * Constructs an ContentEntityNormalizer object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, EntityRepositoryInterface $entity_repository, LanguageManagerInterface $language_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
    $this->entityRepository = $entity_repository;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize(ContentEntityInterface $entity) {
    // Define the generic metadata, define a version to allow to change the
    // format later.
    $normalized = [
      '_meta' => [
        'version' => '1.0',
        'entity_type' => $entity->getEntityTypeId(),
        'uuid' => $entity->uuid(),
      ],
    ];

    $entity_type = $entity->getEntityType();
    if ($bundle_key = $entity_type->getKey('bundle')) {
      $normalized['_meta']['bundle'] = $entity->bundle();
    }
    if ($langcode_key = $entity_type->getKey('langcode')) {
      $normalized['_meta']['default_langcode'] = $entity->language()->getId();
    }

    $is_root = FALSE;
    if ($this->dependencies === NULL) {
      $is_root = TRUE;
      $this->dependencies = [];
    }

    $field_names = $this->getFieldsToNormalize($entity);

    // For menu links, add dependency information for the parent.
    if ($entity instanceof MenuLinkContentInterface) {
      if (strpos($entity->getParentId(), PluginBase::DERIVATIVE_SEPARATOR) !== FALSE) {
        [$plugin_id, $parent_uuid] = explode(PluginBase::DERIVATIVE_SEPARATOR, $entity->getParentId());
        if ($plugin_id === 'menu_link_content' && $parent_entity = $this->entityRepository->loadEntityByUuid('menu_link_content', $parent_uuid)) {
          $this->addDependency($parent_entity);
        }
      }
    }

    foreach ($entity->getTranslationLanguages() as $langcode => $language) {
      $translation = $entity->getTranslation($langcode);
      $normalized_translation = $this->normalizeTranslation($translation, $field_names);
      if ($translation->isDefaultTranslation()) {
        $normalized['default'] = $normalized_translation;
      }
      else {
        $normalized['translations'][$langcode] = $normalized_translation;
      }
    }

    if ($is_root) {
      if ($this->dependencies) {
        $normalized['_meta']['depends'] = $this->dependencies;
      }
      $this->dependencies = NULL;
    }

    return $normalized;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize(array $data) {
    if (!isset($data['_meta']['entity_type'])) {
      throw new UnexpectedValueException('The entity type metadata must be specified.');
    }
    if (!isset($data['_meta']['uuid'])) {
      throw new UnexpectedValueException('The uuid metadata must be specified.');
    }

    $is_root = FALSE;
    if ($this->dependencies === NULL && !empty($data['_meta']['depends'])) {
      $is_root = TRUE;
      $this->dependencies = $data['_meta']['depends'];
    }

    $entity_type = $this->entityTypeManager->getDefinition($data['_meta']['entity_type']);

    $values = [
      'uuid' => $data['_meta']['uuid'],
    ];
    if (!empty($data['_meta']['bundle'])) {
      $values[$entity_type->getKey('bundle')] = $data['_meta']['bundle'];
    }

    if (!empty($data['_meta']['default_langcode'])) {
      $data = $this->verifyNormalizedLanguage($data);
      $values[$entity_type->getKey('langcode')] = $data['_meta']['default_langcode'];
    }

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->entityTypeManager->getStorage($entity_type->id())->create($values);
    foreach ($data['default'] as $field_name => $values) {
      $this->setFieldValues($entity, $field_name, $values);
    }

    if (!empty($data['translations'])) {
      foreach ($data['translations'] as $langcode => $translation_data) {
        if ($this->languageManager->getLanguage($langcode)) {
          $translation = $entity->addTranslation($langcode, $entity->toArray());
          foreach ($translation_data as $field_name => $values) {
            $this->setFieldValues($translation, $field_name, $values);
          }
        }
      }
    }

    if ($is_root) {
      $this->dependencies = NULL;
    }

    return $entity;
  }

  /**
   * Set field values based on the normalized data.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity.
   * @param string $field_name
   *   The name of the field.
   * @param array $values
   *   The normalized data for the field.
   */
  protected function setFieldValues(ContentEntityInterface $entity, string $field_name, array $values) {
    if (!$entity->hasField($field_name)) {
      return;
    }
    foreach ($values as $delta => $item_value) {
      if (!$entity->get($field_name)->get($delta)) {
        $entity->get($field_name)->appendItem();
      }
      /** @var \Drupal\Core\Field\FieldItemInterface $item */
      $item = $entity->get($field_name)->get($delta);

      // Update the URI based on the target UUID for link fields.
      if (isset($item_value['target_uuid']) && isset($item->getProperties()['uri'])) {
        $target_entity = $this->loadEntityDependency($item_value['target_uuid']);
        if ($target_entity) {
          $item_value['uri'] = 'entity:' . $target_entity->getEntityTypeId() . '/' . $target_entity->id();
        }
        unset($item_value['target_uuid']);
      }

      $serialized_property_names = $this->getCustomSerializedPropertyNames($item);
      foreach ($item_value as $property_name => $value) {

        if (\in_array($property_name, $serialized_property_names)) {
          if (\is_string($value)) {
            throw new \LogicException("Received string for serialized property $field_name.$delta.$property_name");
          }
          $value = serialize($value);
        }

        $property = $item->get($property_name);

        if ($property instanceof EntityReference) {
          if (is_array($value)) {
            $target_entity = $this->denormalize($value);
          }
          else {
            $target_entity = $this->loadEntityDependency($value);
          }
          $property->setValue($target_entity);
        }
        else {
          $property->setValue($value);
        }
      }
    }
  }

  /**
   * Returns a list of fields to be normalized.
   *
   * Ignores identifiers, fields that are already defined in the metadata,
   * fields that are known to be overwritten like revision creation time
   * and media thumbnail.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity.
   *
   * @return string[]
   *   The list of fields to normalize.
   */
  protected function getFieldsToNormalize(ContentEntityInterface $entity): array {
    $fields = $entity->getFieldDefinitions();

    // Ignore internal and read-only field definitions.
    $fields = array_filter($fields, static function (FieldDefinitionInterface $field_definition): bool {
      $definition = $field_definition->getFieldStorageDefinition();
      if ($definition instanceof BaseFieldDefinition) {
        // We do not want to remove all computed fields (e.g: path).
        // \Drupal\Core\TypedData\DataDefinition::isReadOnly() checks if it is
        // computed so we can't rely on it. Get the definition array instead.
        $definition_array = $definition->toArray();
        if (!empty($definition_array['internal']) || !empty($definition_array['read-only'])) {
          return FALSE;
        }
      }
      return TRUE;
    });

    // Ignore langcode and default_langcode.
    /** @var \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type */
    $entity_type = $entity->getEntityType();
    if ($langcode_key = $entity_type->getKey('langcode')) {
      unset($fields[$langcode_key]);
      unset($fields[$entity_type->getKey('default_langcode')]);
    }

    // Ignore the revision created timestamp, it is set on save.
    if ($revision_created_key = $entity_type->getRevisionMetadataKey('revision_created')) {
      unset($fields[$revision_created_key]);
    }

    // Ignore parent reference fields of composite entities.
    $parent_reference_keys = [
      'entity_revision_parent_type_field',
      'entity_revision_parent_id_field',
      'entity_revision_parent_field_name_field',
    ];
    foreach ($parent_reference_keys as $parent_reference_key) {
      if ($key_field_name = $entity_type->get($parent_reference_key)) {
        unset($fields[$key_field_name]);
      }
    }
    return array_keys($fields);
  }

  /**
   * Normalizes an entity (translation).
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $translation
   *   The entity to be normalized with its currently active language.
   * @param string[] $field_names
   *   List of fields to normalize.
   *
   * @return array
   *   The normalized field values.
   */
  protected function normalizeTranslation(ContentEntityInterface $translation, array $field_names) {
    $translation_normalization = [];
    foreach ($field_names as $field_name) {

      if ($translation->getFieldDefinition($field_name)->getType() == 'changed') {
        // Ignore the changed field.
        continue;
      }

      if ($translation->isDefaultTranslation() || $translation->getFieldDefinition($field_name)->isTranslatable()) {
        foreach ($translation->get($field_name) as $delta => $field_item) {

          // Ignore empty field items.
          if ($field_item->isEmpty()) {
            continue;
          }

          $serialized_property_names = $this->getCustomSerializedPropertyNames($field_item);

          /** @var \Drupal\Core\Field\FieldItemInterface $field_item */
          foreach ($field_item->getProperties(TRUE) as $property_name => $property) {
            $value = $this->getValueFromProperty($property, $field_item, $translation_normalization[$field_name][$delta]);

            if ($value !== NULL) {
              if (is_string($value) && in_array($property_name, $serialized_property_names)) {
                $value = unserialize($value);
              }
              $translation_normalization[$field_name][$delta][$property_name] = $value;
            }
          }
        }
      }
    }
    return $translation_normalization;
  }

  /**
   * Returns the value for a given property.
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface $property
   *   The property to be normalized.
   * @param \Drupal\Core\Field\FieldItemInterface $field_item
   *   The field item parent of the property.
   * @param array|null $normalized_item
   *   The normalized values of the field item, can be used to set a value
   *   other than the current property.
   *
   * @return mixed|null
   *   The normalized value, a scalar, array or NULL to skip this property.
   */
  protected function getValueFromProperty(TypedDataInterface $property, FieldItemInterface $field_item, &$normalized_item = NULL) {
    $value = NULL;
    // @todo Is there case where it is not the entity property?
    if ($property->getDataDefinition() instanceof DataReferenceTargetDefinition && $field_item->entity instanceof ContentEntityInterface) {

      // Ignore broken references.
      if (!$field_item->entity) {
        return NULL;
      }

      // Ignore data reference target properties for content entities,
      // except user 0 and 1, which can be referenced by ID unlike
      // their UUIDs, which are expected to changed.
      if (!($field_item->entity instanceof UserInterface) || !in_array($field_item->entity->id(), [0, 1])) {
        return NULL;
      }

      $value = $property->getCastedValue();
    }
    elseif ($property instanceof EntityReference && $property->getValue() instanceof ContentEntityInterface) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $target */
      $target = $property->getValue();

      // Ignore user 0 and 1, they are stored with their ID.
      if ($field_item->entity instanceof UserInterface && in_array($field_item->entity->id(), [0, 1])) {
        return NULL;
      }

      // Regular entity references are referenced by UUID, entity
      // types like paragraphs that are child entities are embedded
      // directly.
      if ($field_item->getFieldDefinition()->getType() == 'entity_reference_revisions' && $target->getEntityType()->get('entity_revision_parent_type_field')) {
        $value = $this->normalize($target);
      }
      else {
        $this->addDependency($target);
        $value = $target->uuid();
      }
    }
    elseif ($property instanceof Uri) {
      $value = $property->getValue();
      $scheme = parse_url($value, PHP_URL_SCHEME);
      if ($scheme === 'entity') {
        // Normalize entity URI's as UUID, do not set the URI property.
        $path = parse_url($value, PHP_URL_PATH);
        [$target_entity_type_id, $target_id] = explode('/', $path);
        $target = $this->entityTypeManager->getStorage($target_entity_type_id)->load($target_id);
        $this->addDependency($target);
        $normalized_item['target_uuid'] = $target->uuid();
        $value = NULL;
      }
    }
    elseif ($property->getName() == 'pid' && $field_item instanceof PathItem) {
      // Ignore the pid attribute of path fields so that they are
      // correctly-created.
      return NULL;
    }
    elseif ($property instanceof PathautoState && $property->getValue() !== NULL) {
      // Explicitly include the pathauto state.
      $value = (int) $property->getValue();
    }
    elseif ($property instanceof PrimitiveInterface) {
      $value = $property->getCastedValue();
    }
    elseif (!$property->getDataDefinition()->isComputed()) {
      $value = $property->getValue();
    }
    return $value;
  }

  /**
   * Adds an entity dependency to the normalization root.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   */
  protected function addDependency(ContentEntityInterface $entity) {
    $this->dependencies[$entity->uuid()] = $entity->getEntityTypeId();
  }

  /**
   * Loads the entity dependency by its UUID.
   *
   * @param string $target_uuid
   *   The entity UUID.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   The loaded entity.
   */
  protected function loadEntityDependency(string $target_uuid) {
    if (isset($this->dependencies[$target_uuid])) {
      return $this->entityRepository->loadEntityByUuid($this->dependencies[$target_uuid], $target_uuid);
    }
    return NULL;
  }

  /**
   * Verifies that the site knows the default language of the normalized entity.
   *
   * Will attempt to switch to an alternative translation or just import it
   * with the site default language.
   *
   * @param array $data
   *   The normalized entity data.
   *
   * @return array
   *   The normalized entity data, possibly with altered default language
   *   and translations.
   */
  protected function verifyNormalizedLanguage(array $data) {
    // Check the language. If the default language isn't known, import as one
    // of the available translations if one exists with those values. If none
    // exists, create the entity in the default language.
    // During the installer, when installing with an alternative language,
    // EN is still when modules are installed so check the default language
    // instead.
    if (!$this->languageManager->getLanguage($data['_meta']['default_langcode']) || (InstallerKernel::installationAttempted() && $this->languageManager->getDefaultLanguage()->getId() != $data['_meta']['default_langcode'])) {
      $use_default = TRUE;
      if (isset($data['translations'])) {
        foreach ($data['translations'] as $langcode => $translation_data) {
          if ($this->languageManager->getLanguage($langcode)) {
            $data['_meta']['default_langcode'] = $langcode;
            $data['default'] = \array_merge($data['default'], $translation_data);
            unset($data['translations'][$langcode]);
            $use_default = FALSE;
            break;
          }
        }
      }

      if ($use_default) {
        $data['_meta']['default_langcode'] = $this->languageManager->getDefaultLanguage()->getId();
      }
    }
    return $data;
  }

  /**
   * Gets the names of all properties the plugin treats as serialized data.
   *
   * This allows the field storage definition or entity type to provide a
   * setting for serialized properties. This can be used for fields that
   * handle serialized data themselves and do not rely on the serialized schema
   * flag.
   *
   *
   * @param \Drupal\Core\Field\FieldItemInterface $field_item
   *   The field item.
   *
   * @return string[]
   *   The property names for serialized properties.
   *
   * @see \Drupal\serialization\Normalizer\SerializedColumnNormalizerTrait::getCustomSerializedPropertyNames
   */
  protected function getCustomSerializedPropertyNames(FieldItemInterface $field_item) {
    if ($field_item instanceof PluginInspectionInterface) {
      $definition = $field_item->getPluginDefinition();
      $serialized_fields = $field_item->getEntity()->getEntityType()->get('serialized_field_property_names');
      $field_name = $field_item->getFieldDefinition()->getName();
      if (is_array($serialized_fields) && isset($serialized_fields[$field_name]) && is_array($serialized_fields[$field_name])) {
        return $serialized_fields[$field_name];
      }
      if (isset($definition['serialized_property_names']) && is_array($definition['serialized_property_names'])) {
        return $definition['serialized_property_names'];
      }
    }
    return [];
  }

}
