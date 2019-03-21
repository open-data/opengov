<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Target;

use Drupal\feeds\Feeds\Target\StringTarget;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Target\StringTarget
 * @group feeds
 */
class StringTargetTest extends FieldTargetTestBase {

  /**
   * {@inheritdoc}
   */
  protected function getTargetClass() {
    return StringTarget::class;
  }

}
