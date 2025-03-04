<?php

namespace Drupal\Tests\advanced_email_validation\Functional;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests for advanced email webform validation handler functionality.
 *
 * @group advanced_email_validation
 */
class WebformHandlerEmailValidationTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'webform',
    'advanced_email_validation',
    'advanced_email_validation_test',
  ];

  /**
   * Test basic email handler.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testBasicEmailHandler() {
    $assert_session = $this->assertSession();
    $config = $this->config('advanced_email_validation.settings');

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('test_aev_webform_validation_hdlr');

    /* ********************************************************************** */

    $accountName = $this->randomMachineName();

    // Check that validation fails when the rules are configured on.
    $config
      ->set('rules.mx_lookup', TRUE)
      ->set('rules.disposable', TRUE)
      ->set('rules.free', TRUE)
      ->save();

    // Check email mx lookup handling.
    $edit = ['email' => $accountName . '@invalidinvalidinvalid.com'];
    $this->postSubmission($webform, $edit);
    $assert_session->responseContains('Not a valid email address');

    // Check disposable email address handling.
    $edit = ['email' => $accountName . '@mailinator.com'];
    $this->postSubmission($webform, $edit);
    $assert_session->responseContains('Disposable emails are not allowed');

    // Check free email address handling.
    $edit = ['email' => $accountName . '@gmail.com'];
    $this->postSubmission($webform, $edit);
    $assert_session->responseContains('Free public email providers are not allowed');

    // Check that validation passes when the rules are configured off.
    $config
      ->set('rules.mx_lookup', FALSE)
      ->set('rules.disposable', FALSE)
      ->set('rules.free', FALSE)
      ->save();

    // Check email mx lookup handling.
    $edit = ['email' => $accountName . '@invalidinvalidinvalid.com'];
    $this->postSubmission($webform, $edit);
    $assert_session->responseNotContains('Not a valid email address');

    // Check disposable email address handling.
    $edit = ['email' => $accountName . '@mailinator.com'];
    $this->postSubmission($webform, $edit);
    $assert_session->responseNotContains('Disposable emails are not allowed');

    // Check free email address handling.
    $edit = ['email' => $accountName . '@gmail.com'];
    $this->postSubmission($webform, $edit);
    $assert_session->responseNotContains('Free public email providers are not allowed');
  }

}
