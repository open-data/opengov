<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Target;

use Drupal\feeds\Feeds\Target\Telephone;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Target\Telephone
 * @group feeds
 */
class TelephoneTest extends FieldTargetTestBase {

  /**
   * {@inheritdoc}
   */
  protected function getTargetClass() {
    return Telephone::class;
  }

  /**
   * @covers ::prepareValue
   * @dataProvider dataProviderPrepareValue
   */
  public function testPrepareValue($expected, $value) {
    $method = $this->getMethod('Drupal\feeds\Feeds\Target\Telephone', 'prepareTarget')->getClosure();
    $field_definition = $this->getMockFieldDefinition();
    $field_definition->expects($this->any())
      ->method('getType')
      ->will($this->returnValue('string'));
    $configuration = [
      'feed_type' => $this->getMock('Drupal\feeds\FeedTypeInterface'),
      'target_definition' => $method($field_definition),
    ];
    $target = new Telephone($configuration, 'telephone', []);

    $method = $this->getProtectedClosure($target, 'prepareValue');

    $values = ['value' => $value];
    $method(0, $values);
    $this->assertSame($expected, $values['value']);
  }

  /**
   * Data provider for testPrepareValue().
   */
  public function dataProviderPrepareValue() {
    return [
      // Custom string.
      ['string', 'string'],
      // Empty string.
      ['', ''],
      // Test telephone number in default format.
      ['+49123456789', '+49123456789'],
      // Test telephone number with special characters.
      ['+49 123 456789', '+49 123 456789'],
      ['+49 123 456789-0', '+49 123 456789-0'],
      ['+49 (0)123 456789-0', '+49 (0)123 456789-0'],
      // Test long number.
      ['+123456789123456789', '+123456789123456789'],
      // Test number with 7 digits.
      ['+41 1234567', '+41 1234567'],
      ['+41 10000000000000000000', '+41 10000000000000000000'],
    ];
  }

}
