<?php

namespace Drupal\Tests\webform\FunctionalJavascript\Element;

use Drupal\Tests\webform\FunctionalJavascript\WebformWebDriverTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests webform color element.
 *
 * @group webform_javascript
 */
class WebformElementColorJavaScriptTest extends WebformWebDriverTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = [
    'test_element_color',
  ];

  /**
   * Tests color element output.
   */
  public function testColorElementOutput(): void {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    $webform = Webform::load('test_element_color');

    /* ********************************************************************** */

    $this->drupalGet($webform->toUrl());

    // Check that the color element has a synchronized output field.
    $this->assertTrue($session->wait(5000, "document.querySelector('#edit-color + label + .form-color-output') !== null"));
    $assert_session->fieldValueEquals('color', '#336699');
    $this->assertSame('#336699', $page->find('css', '#edit-color + label + .form-color-output')->getValue());
    $session->executeScript("document.querySelector('#edit-color').value = '#003366'; document.querySelector('#edit-color').dispatchEvent(new Event('input', { bubbles: true }));");
    $this->assertTrue($session->wait(5000, "document.querySelector('#edit-color + label + .form-color-output').value === '#003366'"));
    $this->assertSame('#003366', $page->find('css', '#edit-color + label + .form-color-output')->getValue());

    // Check that the output field is created without parsing unsafe attributes as HTML.
    $output = $page->find('css', '#edit-color-attributes-class-xss + label + .form-color-output');
    $this->assertFalse($output->hasAttribute('autofocus'));
    $this->assertFalse($output->hasAttribute('onfocus'));
  }

}
