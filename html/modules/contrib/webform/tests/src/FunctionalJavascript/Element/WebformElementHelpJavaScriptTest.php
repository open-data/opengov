<?php

namespace Drupal\Tests\webform\FunctionalJavascript\Element;

use Drupal\Component\Serialization\Json;
use Drupal\Tests\webform\FunctionalJavascript\WebformWebDriverTestBase;

/**
 * Tests webform element help JavaScript.
 *
 * @group webform_javascript
 */
class WebformElementHelpJavaScriptTest extends WebformWebDriverTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = [
    'test_element_help',
  ];

  /**
   * Tests element help content is escaped.
   */
  public function testElementHelpContentIsEscaped(): void {
    $assert_session = $this->assertSession();

    $this->drupalGet('/webform/test_element_help');
    $assert_session->waitForElement('css', '.js-webform-element-help');
    $assert_session->elementExists('css', 'label[for="edit-help-xss"] .js-webform-element-help');
    $this->assertTippyInitialized('label[for="edit-help-xss"] .js-webform-element-help');

    $this->getSession()->executeScript('window.webformElementHelpExecuted = false;');
    $this->showTippy('label[for="edit-help-xss"] .js-webform-element-help');
    $assert_session->waitForElementVisible('css', '.tippy-box');

    // Check that element help event attributes are not injected into the DOM.
    $assert_session->elementNotExists('css', '.tippy-box img[onerror]');
    $this->assertFalse((bool) $this->getSession()->evaluateScript('window.webformElementHelpExecuted === true'));
  }

  /**
   * Asserts a Tippy instance has been initialized.
   *
   * @param string $selector
   *   The element selector.
   */
  protected function assertTippyInitialized(string $selector): void {
    $selector = Json::encode($selector);
    $this->assertTrue((bool) $this->getSession()->wait(5000, "!!(document.querySelector($selector) && document.querySelector($selector)._tippy)"));
  }

  /**
   * Shows a Tippy tooltip.
   *
   * @param string $selector
   *   The element selector.
   */
  protected function showTippy(string $selector): void {
    $selector = Json::encode($selector);
    $this->getSession()->executeScript("document.querySelector($selector)._tippy.show();");
  }

}
