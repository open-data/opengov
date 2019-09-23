<?php

namespace Drupal\webform\Tests\Settings;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for webform submission form ajax.
 *
 * @group Webform
 */
class WebformSettingsAjaxTest extends WebformTestBase {

  /**
   * Test webform submission form Ajax setting.
   */
  public function testAjax() {
    $webform = Webform::load('contact');

    // Check that Ajax is not enabled.
    $this->drupalGet('/webform/contact');
    $this->assertNoRaw('<div id="webform_submission_contact_add_form-ajax" class="webform-ajax-form-wrapper" data-effect="fade" data-progress-type="throbber">');

    // Set 'Use Ajax' for the individual webform.
    $webform->setSetting('ajax', TRUE);
    $webform->save();

    // Check that Ajax is enabled for the individual webform.
    $this->drupalGet('/webform/contact');
    $this->assertRaw('<div id="webform_submission_contact_add_form-ajax" class="webform-ajax-form-wrapper" data-effect="fade" data-progress-type="throbber">');
    $this->assertRaw('"effect":"fade","speed":500');

    // Unset 'Use Ajax' for the individual webform.
    $webform->setSetting('ajax', FALSE);
    $webform->save();

    // Check that Ajax is not enabled for the individual webform.
    $this->drupalGet('/webform/contact');
    $this->assertNoRaw('<div id="webform_submission_contact_add_form-ajax" class="webform-ajax-form-wrapper" data-effect="fade" data-progress-type="throbber">');

    // Globally enable Ajax for all webforms.
   \Drupal::configFactory()->getEditable('webform.settings')
      ->set('settings.default_ajax', TRUE)
      ->set('settings.default_ajax_progress_type', 'fullscreen')
      ->set('settings.default_ajax_effect', 'slide')
      ->set('settings.default_ajax_speed', 1500)
      ->save();

    // Check that Ajax is enabled for all webforms.
    $this->drupalGet('/webform/contact');
    $this->assertRaw('<div id="webform_submission_contact_add_form-ajax" class="webform-ajax-form-wrapper" data-effect="slide" data-progress-type="fullscreen">');
    $this->assertRaw('"effect":"slide","speed":1500');
  }

}
