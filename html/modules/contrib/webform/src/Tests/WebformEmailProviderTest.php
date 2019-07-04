<?php

namespace Drupal\webform\Tests;

/**
 * Tests for webform email provider.
 *
 * @group Webform
 */
class WebformEmailProviderTest extends WebformTestBase {

  /**
   * Test webform email provider.
   */
  public function testEmailProvider() {
    // Revert system.mail back to  php_mail.
    $this->container->get('config.factory')
      ->getEditable('system.mail')
      ->set('interface.default', 'php_mail')
      ->save();

    /** @var \Drupal\webform\WebformEmailProviderInterface $email_provider */
    $email_provider = \Drupal::service('webform.email_provider');

    $this->drupalLogin($this->rootUser);

    // Check Default PHP mailer is enabled because we manually changed the
    // system.mail configuration.
    $this->drupalGet('/admin/reports/status');
    $this->assertRaw('Provided by php_mail mail plugin.');
    $this->assertNoRaw("Webform PHP mailer: Sends the message as plain text or HTML, using PHP's native mail() function.");
    $this->assertRaw('Default PHP mailer: Sends the message as plain text, using PHP\'s native mail() function.');

    // Check Webform PHP mailer enabled after email provider check.
    $email_provider->check();
    $this->drupalGet('/admin/reports/status');
    $this->assertRaw('Provided by the Webform module.');
    $this->assertRaw("Webform PHP mailer: Sends the message as plain text or HTML, using PHP's native mail() function.");

    // Check Mail System: Default PHP mailer after mailsystem module installed.
    \Drupal::service('module_installer')->install(['mailsystem']);
    $this->drupalGet('/admin/reports/status');
    $this->assertRaw('Provided by the Mail System module.');
    $this->assertNoRaw("Webform PHP mailer: Sends the message as plain text or HTML, using PHP's native mail() function.");
    $this->assertRaw('Default PHP mailer: Sends the message as plain text, using PHP\'s native mail() function.');

    // Check Webform PHP mailer enabled after mailsystem module uninstalled.
    \Drupal::service('module_installer')->uninstall(['mailsystem']);
    $this->drupalGet('/admin/reports/status');
    $this->assertRaw("Webform PHP mailer: Sends the message as plain text or HTML, using PHP's native mail() function.");
  }

}
