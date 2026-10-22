<?php

namespace Drupal\search_api_solr\Plugin\DataType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\Attribute\DataType;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\Exception\MissingDataException;
use Drupal\Core\TypedData\TypedData;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api_solr\Plugin\DataType\Deriver\SolrDocumentDeriver;
use Drupal\search_api_solr\TypedData\SolrDocumentDefinition;
use Solarium\Core\Query\DocumentInterface;

/**
 * Defines the "Solr document" data type.
 *
 * Instances of this class wrap Search API Item objects and allow to deal with
 * items based upon the Typed Data API.
 */
#[DataType(
  id: 'solr_document',
  label: new TranslatableMarkup('Record from a Solr index'),
  definition_class: SolrDocumentDefinition::class,
  deriver: SolrDocumentDeriver::class
)]
class SolrDocument extends TypedData implements \IteratorAggregate, ComplexDataInterface {

  /**
   * Field name.
   *
   * @var string
   */
  protected $solrField = 'solr_field';

  /**
   * Document name.
   *
   * @var string
   */
  protected $solrDocument = 'solr_document';

  /**
   * The wrapped Search API Item.
   *
   * @var \Drupal\search_api\Item\ItemInterface|null
   */
  protected $item;

  /**
   * Creates an instance wrapping the given Item.
   *
   * @param \Drupal\search_api\Item\ItemInterface $item
   *   The Item object to wrap.
   *
   * @return static
   */
  public static function createFromItem(ItemInterface $item) {
    $definition = SolrDocumentDefinition::create($item->getIndex()->id());
    $instance = new static($definition);
    $instance->setValue($item);
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    return $this->item;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($item, $notify = TRUE) {
    $this->item = $item;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\TypedData\Exception\ReadOnlyException
   */
  public function get($property_name) {
    if (!isset($this->item)) {
      throw new MissingDataException("Unable to get Solr field $property_name as no item has been provided.");
    }

    // First, verify that this field actually exists in the Solr server. If we
    // can't get a definition for it, it doesn't exist.
    /** @var \Drupal\search_api_solr\Plugin\DataType\SolrField $plugin */
    $plugin = \Drupal::typedDataManager()->getDefinition($this->solrField)['class'];
    $field_manager = \Drupal::getContainer()->get($this->solrField . '.manager');
    $fields = $field_manager->getFieldDefinitions($this->item->getIndex());
    if (empty($fields[$property_name])) {
      throw new \InvalidArgumentException("The Solr field $property_name could not be found on the server.");
    }
    // Create a new typed data object from the item's field data.
    $property = $plugin::createInstance($fields[$property_name], $property_name, $this);

    // Now that we have the property, try to find its values. We first look at
    // the field values contained in the result item.
    $found = FALSE;
    foreach ($this->item->getFields(FALSE) as $field) {
      if (
        $field->getDatasourceId() === $this->solrDocument &&
        $field->getPropertyPath() === $property_name
      ) {
        $property->setValue($field->getValues());
        $found = TRUE;
        break;
      }
    }

    if (!$found) {
      // If that didn't work, maybe we can get the field from the Solr document?
      $document = $this->item->getExtraData('search_api_solr_document');
      if (
        $document instanceof DocumentInterface &&
        isset($document[$property_name])
      ) {
        $property->setValue($document[$property_name]);
      }
    }

    return $property;
  }

  /**
   * {@inheritdoc}
   */
  public function set($property_name, $value, $notify = TRUE) {
    // Do nothing because we treat Solr documents as read-only.
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getProperties($include_computed = FALSE) {
    if (!isset($this->item)) {
      return [];
    }

    $properties = [];
    /** @var \Drupal\search_api_solr\Plugin\DataType\SolrField $plugin */
    $plugin = \Drupal::typedDataManager()->getDefinition($this->solrField)['class'];
    $field_manager = \Drupal::getContainer()->get($this->solrField . '.manager');
    $fields = $field_manager->getFieldDefinitions($this->item->getIndex());

    foreach ($fields as $property_name => $definition) {
      $properties[$property_name] = $plugin::createInstance($definition, $property_name, $this);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    $values = [];

    foreach ($this->getProperties() as $property_name => $property) {
      $values[$property_name] = $property->getValue();
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return !isset($this->item);
  }

  /**
   * {@inheritdoc}
   */
  public function onChange($name) {
    // Do nothing.  Unlike content entities, Items don't need to be notified of
    // changes.
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator(): \Traversable {
    return new \ArrayIterator($this->toArray());
  }

}
