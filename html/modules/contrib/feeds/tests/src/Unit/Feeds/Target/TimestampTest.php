<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Target;

use Drupal\feeds\Feeds\Target\Timestamp;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Target\Timestamp
 * @group feeds
 */
class TimestampTest extends FieldTargetWithContainerTestBase {

  /**
   * {@inheritdoc}
   */
  protected function getTargetClass() {
    return Timestamp::class;
  }

  /**
   * @covers ::prepareValue
   */
  public function testPrepareValue() {
    $method = $this->getMethod(Timestamp::class, 'prepareTarget')->getClosure();
    $target_definition = $method($this->getMockFieldDefinition());

    $configuration = [
      'feed_type' => $this->getMock('Drupal\feeds\FeedTypeInterface'),
      'target_definition' => $target_definition,
    ];
    $target = new Timestamp($configuration, 'timestamp', []);
    $method = $this->getProtectedClosure($target, 'prepareValue');

    // Test valid timestamp.
    $values = ['value' => 1411606273];
    $method(0, $values);
    $this->assertSame($values['value'], 1411606273);

    // Test year value.
    $values = ['value' => 2000];
    $method(0, $values);
    $this->assertSame($values['value'], strtotime('2000-01-01T00:00:00Z'));

    // Test invalid value.
    $values = ['value' => 'abc'];
    $method(0, $values);
    $this->assertSame($values['value'], '');
  }

}
