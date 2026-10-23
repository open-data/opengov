<?php

namespace Drupal\Tests\recaptcha_v3\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the reCAPTCHA v3 settings form.
 *
 * @group recaptcha_v3
 */
class ReCaptchaSettingsFormTest extends BrowserTestBase {

  /**
   * The default theme.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'captcha',
    'recaptcha_v3',
  ];

  /**
   * Tests saving settings when library-related values change.
   */
  public function testSettingsFormSave() {
    $this->drupalLogIn($this->createUser(['administer CAPTCHA settings']));
    $this->drupalGet('/admin/config/people/captcha/recaptcha-v3');

    $this->submitForm([
      'site_key' => '1234567890123456789012345678901234567890',
      'secret_key' => 'abcdefghijklmnopqrstuvwxyz1234567890ABCD',
      'hide_badge' => TRUE,
      'verify_hostname' => TRUE,
      'default_challenge' => '',
      'error_message' => 'Test verification message.',
      'cacheable' => TRUE,
      'library_use_recaptcha_net' => TRUE,
    ], 'Save configuration');

    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    $config = $this->config('recaptcha_v3.settings');
    $this->assertSame('1234567890123456789012345678901234567890', $config->get('site_key'));
    $this->assertSame('abcdefghijklmnopqrstuvwxyz1234567890ABCD', $config->get('secret_key'));
    $this->assertTrue($config->get('hide_badge'));
    $this->assertTrue($config->get('verify_hostname'));
    $this->assertTrue($config->get('cacheable'));
    $this->assertTrue($config->get('library_use_recaptcha_net'));
  }

}
