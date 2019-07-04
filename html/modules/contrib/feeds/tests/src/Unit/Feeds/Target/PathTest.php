<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Target;

use Drupal\feeds\Feeds\Target\Path;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Target\Path
 * @group feeds
 */
class PathTest extends FieldTargetTestBase {

  /**
   * {@inheritdoc}
   */
  protected function getTargetClass() {
    return Path::class;
  }

  /**
   * @covers ::prepareValue
   */
  public function testPrepareValue() {
    $method = $this->getMethod('Drupal\feeds\Feeds\Target\Path', 'prepareTarget')->getClosure();

    $configuration = [
      'feed_type' => $this->getMock('Drupal\feeds\FeedTypeInterface'),
      'target_definition' => $method($this->getMockFieldDefinition()),
    ];
    $target = new Path($configuration, 'path', []);

    $method = $this->getProtectedClosure($target, 'prepareValue');

    $values = ['alias' => 'path '];
    $method(0, $values);
    $this->assertSame($values['alias'], 'path');
  }

}
