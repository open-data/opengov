<?php

namespace Drupal\feeds\Feeds\Processor;

/**
 * Provides a generic content entity processor.
 *
 * @FeedsProcessor(
 *   id = "entity",
 *   arguments = {
 *     "@entity_type.manager",
 *     "@entity.query",
 *     "@entity_type.bundle.info"
 *   },
 *   form = {
 *     "configuration" = "Drupal\feeds\Feeds\Processor\Form\DefaultEntityProcessorForm",
 *     "option" = "Drupal\feeds\Feeds\Processor\Form\EntityProcessorOptionForm",
 *   },
 *   deriver = "Drupal\feeds\Plugin\Derivative\GenericContentEntityProcessor",
 * )
 */
class GenericContentEntityProcessor extends EntityProcessorBase {

}
