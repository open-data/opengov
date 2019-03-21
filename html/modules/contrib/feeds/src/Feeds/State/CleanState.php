<?php

namespace Drupal\feeds\Feeds\State;

use ArrayIterator;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\feeds\State;
use RuntimeException;

/**
 * State for the clean stage.
 */
class CleanState extends State implements CleanStateInterface {

  /**
   * Whether or not the list was initiated or not.
   *
   * @var bool
   */
  protected $initiated = FALSE;

  /**
   * A list of entity ID's that may be cleaned after processing.
   *
   * @var array
   */
  protected $cleanList = [];

  /**
   * The type of the entity ID's on the list.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * {@inheritdoc}
   */
  public function progress($total, $progress) {
    if (!$this->count()) {
      $this->setCompleted();
    }
    return parent::progress($total, $progress);
  }

  /**
   * {@inheritdoc}
   */
  public function initiated() {
    return $this->initiated;
  }

  /**
   * {@inheritdoc}
   */
  public function setList(array $ids) {
    $this->cleanList = array_combine($ids, $ids);
    $this->initiated = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getList() {
    return $this->cleanList;
  }

  /**
   * {@inheritdoc}
   */
  public function removeItem($entity_id) {
    unset($this->cleanList[$entity_id]);
    $this->total--;
    $this->progress($this->total, $this->updated);
  }

  /**
   * {@inheritdoc}
   */
  public function nextEntity(EntityStorageInterface $storage = NULL) {
    if (!$this->initiated()) {
      return;
    }

    $entity_id = array_shift($this->cleanList);
    if (!$entity_id) {
      return;
    }

    if (!$storage) {
      $entity_type_id = $this->getEntityTypeId();
      if (!$entity_type_id) {
        throw new RuntimeException('The clean state does not have an entity type assigned.');
      }
      $storage = \Drupal::entityTypeManager()->getStorage($this->getEntityTypeId());
    }

    $entity = $storage->load($entity_id);
    if ($entity instanceof EntityInterface) {
      return $entity;
    }
    else {
      return $this->nextEntity($storage);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setEntityTypeId($entity_type_id) {
    // @todo check for valid entity type id.
    $this->entityTypeId = $entity_type_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId() {
    return $this->entityTypeId;
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    return new ArrayIterator($this->cleanList);
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return count($this->cleanList);
  }

}
