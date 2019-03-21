<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Item;

use Drupal\feeds\Feeds\Item\BaseItem;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Item\BaseItem
 * @group feeds
 */
class BaseItemTest extends ItemTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->item = $this->getMockForAbstractClass(BaseItem::class);
  }

}
