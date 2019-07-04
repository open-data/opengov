<?php

namespace Drupal\webform\Tests\Settings;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for disable tracking of remote IP address.
 *
 * @group Webform
 */
class WebformSettingsRemoteAddrTest extends WebformTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_remote_addr'];

  /**
   * Tests webform disable remote IP address.
   */
  public function testRemoteAddr() {
    $this->drupalLogin($this->rootUser);

    $webform = Webform::load('test_form_remote_addr');
    $sid = $this->postSubmission($webform, ['name' => 'John']);
    $webform_submission = WebformSubmission::load($sid);
    $this->assertEqual($webform_submission->getRemoteAddr(), t('(unknown)'));
    $this->assertEqual($webform_submission->getOwnerId(), 1);
  }

}
