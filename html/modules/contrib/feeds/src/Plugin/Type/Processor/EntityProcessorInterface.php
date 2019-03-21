<?php

namespace Drupal\feeds\Plugin\Type\Processor;

use Drupal\feeds\Plugin\Type\CleanableInterface;
use Drupal\feeds\Plugin\Type\ClearableInterface;
use Drupal\feeds\Plugin\Type\LockableInterface;

/**
 * Interface for Feeds entity processor plugins.
 */
interface EntityProcessorInterface extends ProcessorInterface, ClearableInterface, CleanableInterface, LockableInterface {

}
