<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Target;

use Drupal\feeds\Feeds\Target\DateRange;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Target\DateRange
 * @group feeds
 */
class DateRangeTest extends FieldTargetWithContainerTestBase {

  /**
   * The mocked feed type entity.
   *
   * @var \Drupal\feeds\FeedTypeInterface
   */
  protected $feedType;

  /**
   * The target definition.
   *
   * @var \Drupal\feeds\TargetDefinitionInterface
   */
  protected $targetDefinition;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->feedType = $this->getMock('Drupal\feeds\FeedTypeInterface');
    $method = $this->getMethod('Drupal\feeds\Feeds\Target\DateRange', 'prepareTarget')->getClosure();
    $this->targetDefinition = $method($this->getMockFieldDefinition(['datetime_type' => 'date']));
  }

  /**
   * {@inheritdoc}
   */
  protected function getTargetClass() {
    return DateRange::class;
  }

  /**
   * @covers ::prepareValue
   */
  public function testPrepareValue() {
    $configuration = [
      'feed_type' => $this->feedType,
      'target_definition' => $this->targetDefinition,
    ];
    $target = new DateRange($configuration, 'daterange', []);
    $method = $this->getProtectedClosure($target, 'prepareValue');

    $values = [
      'value' => 1411606273,
      'end_value' => 1489582776,
    ];
    $method(0, $values);
    $this->assertSame(date(DATETIME_DATE_STORAGE_FORMAT, 1411606273), $values['value']);
    $this->assertSame(date(DATETIME_DATE_STORAGE_FORMAT, 1489582776), $values['end_value']);
  }

}
