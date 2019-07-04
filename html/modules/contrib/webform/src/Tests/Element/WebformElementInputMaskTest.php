<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Entity\Webform;

/**
 * Tests for element input mask.
 *
 * @group Webform
 */
class WebformElementInputMaskTest extends WebformElementTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_input_mask'];

  /**
   * Test element input mask.
   */
  public function testInputMask() {
    $webform = Webform::load('test_element_input_mask');

    // Check default values.
    $this->postSubmission($webform);
    $this->assertRaw("currency: ''
datetime: ''
decimal: ''
email: ''
ip: ''
license_plate: ''
mac: ''
percentage: ''
phone: ''
ssn: ''
vin: ''
zip: ''
uppercase: ''
lowercase: ''
custom: ''");

    // Check patterns.
    $edit = [
      'email' => 'example@example.com',
      'datetime' => '2007-06-09\'T\'17:46:21',
      'decimal' => '9.9',
      'ip' => '255.255.255.255',
      'currency' => '$ 9.99',
      'percentage' => '99 %',
      'phone' => '(999) 999-9999',
      'license_plate' => '9-AAA-999',
      'mac' => '99-99-99-99-99-99',
      'ssn' => '999-99-9999',
      'vin' => 'JA3AY11A82U020534',
      'zip' => '99999-9999',
      'uppercase' => 'UPPERCASE',
      'lowercase' => 'lowercase',
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw("currency: '$ 9.99'
datetime: '2007-06-09''T''17:46:21'
decimal: '9.9'
email: example@example.com
ip: 255.255.255.255
license_plate: 9-AAA-999
mac: 99-99-99-99-99-99
percentage: '99 %'
phone: '(999) 999-9999'
ssn: 999-99-9999
vin: JA3AY11A82U020534
zip: 99999-9999
uppercase: UPPERCASE
lowercase: lowercase
custom: ''");

    // Check pattern validation error messages.
    $edit = [
      'currency' => '$ 9.9_',
      'decimal' => '9._',
      'ip' => '255.255.255.__',
      'mac' => '99-99-99-99-99-_)',
      'percentage' => '_ %',
      'phone' => '(999) 999-999_',
      'ssn' => '999-99-999_',
      'zip' => '99999-999_',
    ];
    $this->postSubmission($webform, $edit);
    foreach ($edit as $name => $value) {
      $this->assertRaw('<em class="placeholder">' . $name . '</em> field is not in the right format.');
    }
  }

}
