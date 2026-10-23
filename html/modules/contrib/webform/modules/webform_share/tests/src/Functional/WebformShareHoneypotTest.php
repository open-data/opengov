<?php

namespace Drupal\Tests\webform_share\Functional;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformInterface;

/**
 * Tests honeypot protection on shared webforms.
 *
 * @group webform_share
 */
class WebformShareHoneypotTest extends WebformBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'honeypot',
    'webform',
    'webform_share',
  ];

  /**
   * Tests that Share URLs include Honeypot protection.
   */
  public function testSharePageIncludesHoneypotProtection(): void {
    $assert_session = $this->assertSession();
    $spam_message = 'There was a problem with your form submission. Please refresh the page and try again.';
    $bypassing_url_query = [
      '_wrapper_format' => 'drupal_ajax',
      'ajax_form' => 1,
    ];
    $valid_submission = [
      'name' => 'Example User',
      'email' => 'example@example.com',
      'subject' => 'Bypass test',
      'message' => 'This submission should demonstrate the bypass.',
    ];

    \Drupal::configFactory()
      ->getEditable('honeypot.settings')
      ->set('element_name', 'share_honeypot')
      ->set('time_limit', 0)
      ->set('protect_all_forms', FALSE)
      ->set('log', FALSE)
      ->save();

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('contact');
    $webform
      ->setSetting('share', TRUE)
      ->setSetting('ajax', TRUE)
      ->setThirdPartySetting('honeypot', 'honeypot', TRUE)
      ->save();
    $submit = $this->getWebformSubmitButtonLabel($webform);

    // Check that the standard webform URL triggers Honeypot SPAM protection.
    $this->drupalGet('/webform/contact');
    $assert_session->statusCodeEquals(200);
    $assert_session->fieldExists('share_honeypot');
    $this->submitForm(['share_honeypot' => 'SPAM'], $submit);
    $assert_session->pageTextContains($spam_message);
    $this->assertSame(0, $this->getSubmissionCount($webform));

    // Check that the Share URL triggers Honeypot SPAM protection.
    $this->drupalGet('/webform/contact/share');
    $assert_session->statusCodeEquals(200);
    $assert_session->fieldExists('share_honeypot');
    $assert_session->elementExists('css', '#webform-submission-contact-form-ajax');
    $this->submitForm(['share_honeypot' => 'SPAM'], $submit);
    $assert_session->pageTextContains($spam_message);
    $this->assertSame(0, $this->getSubmissionCount($webform));

    // Check that posting directly to the Share URL triggers Honeypot SPAM
    // protection.
    $this->drupalGet('/webform/contact/share');
    $assert_session->statusCodeEquals(200);
    $form_build_id = $assert_session->hiddenFieldExists('form_build_id')->getValue();
    $form_id = $assert_session->hiddenFieldExists('form_id')->getValue();
    $this->getSession()->getDriver()->getClient()->request('POST', $this->buildUrl('/webform/contact/share'), $valid_submission + [
      'form_build_id' => $form_build_id,
      'form_id' => $form_id,
      'op' => $submit,
      'share_honeypot' => 'SPAM',
      '_triggering_element_name' => 'op',
      '_triggering_element_value' => $submit,
    ]);
    $assert_session->pageTextContains($spam_message);
    $this->assertSame(0, $this->getSubmissionCount($webform));

    // Check that posting to the direct Ajax wrapper Share URL triggers
    // Honeypot SPAM protection.
    $this->drupalGet('/webform/contact/share');
    $assert_session->statusCodeEquals(200);
    $form_build_id = $assert_session->hiddenFieldExists('form_build_id')->getValue();
    $form_id = $assert_session->hiddenFieldExists('form_id')->getValue();
    $this->getSession()->getDriver()->getClient()->request('POST', $this->buildUrl('/webform/contact/share', [
      'query' => $bypassing_url_query,
    ]), $valid_submission + [
      'form_build_id' => $form_build_id,
      'form_id' => $form_id,
      'op' => $submit,
      'share_honeypot' => 'SPAM',
      '_triggering_element_name' => 'op',
      '_triggering_element_value' => $submit,
    ]);
    $assert_session->pageTextContains($spam_message);
    $this->assertSame(0, $this->getSubmissionCount($webform));
  }

  /**
   * Gets the number of submissions for a webform.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   *
   * @return int
   *   The number of submissions for a webform.
   */
  protected function getSubmissionCount(WebformInterface $webform): int {
    return (int) \Drupal::entityQuery('webform_submission')
      ->accessCheck(FALSE)
      ->condition('webform_id', $webform->id())
      ->count()
      ->execute();
  }

}
