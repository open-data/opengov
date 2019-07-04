<?php

namespace Drupal\webform_access;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a webform access group entity.
 */
interface WebformAccessGroupInterface extends ConfigEntityInterface {

  /**
   * Get webform access group type.
   *
   * @return string
   *   The webform access group type.
   */
  public function getType();

  /**
   * Get webform access group type label.
   *
   * @return string
   *   The webform access group type label.
   */
  public function getTypeLabel();

  /**
   * Set user ids assigned to webform access group.
   *
   * @param array $uids
   *   An array of user ids.
   *
   * @return $this
   */
  public function setUserIds(array $uids);

  /**
   * Get user ids assigned to webform access group.
   *
   * @return array
   *   An array of user ids.
   */
  public function getUserIds();

  /**
   * Set entities assigned to webform access group.
   *
   * @param array $entity_ids
   *   An array of entity ids.
   *   Formatted as 'node:type:field_name:webform'.
   *
   * @return $this
   */
  public function setEntityIds(array $entity_ids);

  /**
   * Get entities assigned to webform access group.
   *
   * @return array
   *   An array of entity ids.
   *   Formatted as 'node:type:field_name:webform'
   */
  public function getEntityIds();

  /**
   * Add entity id to webform access group.
   *
   * @param string $entity_type
   *   The source entity type.
   * @param string $entity_id
   *   The source entity id.
   * @param string $field_name
   *   The source entity webform field name.
   * @param string $webform_id
   *   The webform id.
   */
  public function addEntityId($entity_type, $entity_id, $field_name, $webform_id);

  /**
   * Remove entity id to webform access group.
   *
   * @param string $entity_type
   *   The source entity type.
   * @param string $entity_id
   *   The source entity id.
   * @param string $field_name
   *   The source entity webform field name.
   * @param string $webform_id
   *   The webform id.
   */
  public function removeEntityId($entity_type, $entity_id, $field_name, $webform_id);

  /**
   * Add user id to webform access group.
   *
   * @param int $uid
   *   A user id.
   *
   * @return $this
   */
  public function addUserId($uid);

  /**
   * Remove user id to webform access group.
   *
   * @param int $uid
   *   A user id.
   *
   * @return $this
   */
  public function removeUserId($uid);

  /**
   * Invalidates an entity's cache tags upon save.
   */
  public function invalidateTags();

}
