<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Entity\Webform;

/**
 * Tests for webform element private.
 *
 * @group Webform
 */
class WebformElementPrivateTest extends WebformElementTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_private'];

  /**
   * Test element access.
   */
  public function testElementAccess() {
    $normal_user = $this->drupalCreateUser();

    $webform = Webform::load('test_element_private');

    /**************************************************************************/

    $this->drupalLogin($normal_user);

    // Create a webform submission.
    $this->postSubmission($webform);

    // Check element with #private property hidden for normal user.
    $this->drupalGet('/webform/test_element_private');
    $this->assertNoFieldByName('private', '');

    $this->drupalLogin($this->rootUser);

    // Check element with #private property visible for admin user.
    $this->drupalGet('/webform/test_element_private');
    $this->assertFieldByName('private', '');
  }

}
