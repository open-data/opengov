<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Target;

use Drupal\feeds\FeedTypeInterface;
use Drupal\feeds\Feeds\Target\Boolean;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Target\Boolean
 * @group feeds
 */
class BooleanTest extends FieldTargetTestBase {

  /**
   * {@inheritdoc}
   */
  protected function getTargetClass() {
    return Boolean::class;
  }

  /**
   * @covers ::prepareValue
   */
  public function testPrepareValue() {
    $method = $this->getMethod(Boolean::class, 'prepareTarget')->getClosure();

    $configuration = [
      'feed_type' => $this->getMock(FeedTypeInterface::class),
      'target_definition' => $method($this->getMockFieldDefinition()),
    ];

    $target = new Boolean($configuration, 'boolean', []);
    $values = ['value' => 'string'];

    $method = $this->getProtectedClosure($target, 'prepareValue');
    $method(0, $values);
    $this->assertSame(1, $values['value']);
  }

}
