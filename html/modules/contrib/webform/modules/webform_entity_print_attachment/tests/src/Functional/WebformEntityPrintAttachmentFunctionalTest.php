<?php

namespace Drupal\Tests\webform_entity_print_attachment\Functional;

use Drupal\Tests\webform_entity_print\Functional\WebformEntityPrintFunctionalTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Webform entity print attachment test.
 *
 * @group webform_browser
 */
class WebformEntityPrintAttachmentFunctionalTest extends WebformEntityPrintFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['webform_entity_print_attachment_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Use a test print engine that returns the rendered attachment contents.
    \Drupal::configFactory()
      ->getEditable('entity_print.settings')
      ->set('print_engines.pdf_engine', 'webform_entity_print_test')
      ->save();
  }

  /**
   * Test entity print attachment.
   */
  public function testEntityPrintAttachment(): void {
    $webform = Webform::load('test_entity_print_attachment');

    $this->drupalLogin($this->rootUser);

    /* ********************************************************************** */

    // Check that the PDF attachment is added to the sent email.
    $this->postSubmission($webform);
    $sent_email = $this->getLastEmail();
    // cspell:ignore filecontent
    $attachments = array_column($sent_email['params']['attachments'], 'filecontent', 'filename');

    // Check that the HTML attachment uses the HTML view mode.
    $this->assertArrayHasKey('entity_print_pdf_html.pdf', $attachments);
    $this->assertStringContainsString('webform-submission-data--view-mode-html', $attachments['entity_print_pdf_html.pdf']);

    // Check that the table attachment uses the table view mode.
    $this->assertArrayHasKey('entity_print_pdf_table.pdf', $attachments);
    $this->assertStringContainsString('webform-submission-table', $attachments['entity_print_pdf_table.pdf']);

    // Check that the Twig attachment uses its configured template.
    $this->assertArrayHasKey('entity_print_pdf_custom.pdf', $attachments);
    $this->assertStringContainsString('This is a custom template', $attachments['entity_print_pdf_custom.pdf']);
  }

}
