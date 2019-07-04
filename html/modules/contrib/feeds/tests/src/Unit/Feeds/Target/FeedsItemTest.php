<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Target;

use Drupal\feeds\Feeds\Target\FeedsItem;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Target\FeedsItem
 * @group feeds
 */
class FeedsItemTest extends FieldTargetTestBase {

  /**
   * {@inheritdoc}
   */
  protected function getTargetClass() {
    return FeedsItem::class;
  }

}
