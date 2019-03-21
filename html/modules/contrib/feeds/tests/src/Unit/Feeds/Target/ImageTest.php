<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Target;

use Drupal\feeds\Feeds\Target\Image;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Target\Image
 * @group feeds
 */
class ImageTest extends FieldTargetTestBase {

  /**
   * {@inheritdoc}
   */
  protected function getTargetClass() {
    return Image::class;
  }

}
