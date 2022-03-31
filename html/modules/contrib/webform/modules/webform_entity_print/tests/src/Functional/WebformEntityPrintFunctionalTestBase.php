<?php

namespace Drupal\Tests\webform_entity_print\Functional;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Webform entity print test base.
 */
abstract class WebformEntityPrintFunctionalTestBase extends WebformBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_print_test',
    'webform',
    'webform_entity_print',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    if (floatval(\Drupal::VERSION) >= 9) {
      $this->markTestSkipped('Issue #3110478: [Webform 8.x-6.x] Track the D9 readiness state of the Webform module\'s (optional) dependencies');
    }

    parent::setUp();

    // Use test print engine.
    \Drupal::configFactory()
      ->getEditable('entity_print.settings')
      ->set('print_engines.pdf_engine', 'testprintengine')
      ->save();
  }

}
