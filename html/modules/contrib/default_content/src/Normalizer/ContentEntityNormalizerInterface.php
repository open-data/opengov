<?php

namespace Drupal\default_content\Normalizer;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Normalizes and denormalizes content entities.
 */
interface ContentEntityNormalizerInterface {

  /**
   * Normalizes the entity into an array structure.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity.
   *
   * @return array
   *   The normalized values, top level keys must include _meta with at
   *   least the entity_type and uuid keys, as well as the values for the
   *   default language in the default key and optionally translations.
   */
  public function normalize(ContentEntityInterface $entity);

  /**
   * Converts the normalized data back into a content entity.
   *
   * @param array $data
   *   The normalized data.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The denormalized content entity.
   */
  public function denormalize(array $data);

}
