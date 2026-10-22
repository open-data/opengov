<?php

namespace Drupal\Tests\webform\FunctionalJavascript\Element;

use Drupal\Component\Serialization\Json;
use Drupal\Tests\webform\FunctionalJavascript\WebformWebDriverTestBase;

/**
 * Tests webform tooltip JavaScript.
 *
 * @group webform_javascript
 */
class WebformElementTooltipJavaScriptTest extends WebformWebDriverTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = [
    'test_element_description',
  ];

  /**
   * Tests tooltip link content is escaped.
   */
  public function testTooltipLinkContentIsEscaped(): void {
    $assert_session = $this->assertSession();

    $this->drupalGet('/webform/test_element_description');
    $assert_session->waitForElement('css', '.js-webform-tooltip-element');
    $this->createTooltipFixtures();
    $assert_session->elementExists('css', '.js-webform-tooltip-link');
    $this->assertTippyInitialized('.js-webform-tooltip-link');

    $this->getSession()->executeScript('window.webformTooltipTitleExecuted = false;');
    $this->showTippy('.js-webform-tooltip-link');
    $assert_session->waitForElementVisible('css', '.tippy-box');

    // Check that tooltip title markup is not injected into the DOM.
    $assert_session->elementNotExists('css', '.tippy-box .webform-test-tooltip-link-injected');
    $this->assertFalse((bool) $this->getSession()->evaluateScript('window.webformTooltipTitleExecuted === true'));

    $this->getSession()->executeScript('document.querySelector(".js-webform-tooltip-link")._tippy.hide();');
    $this->getSession()->executeScript('window.webformTooltipDescriptionExecuted = false;');
    $this->showTippy('.form-item-description-tooltip-html');
    $assert_session->waitForElementVisible('css', '.tippy-box');

    // Check that safe description markup is preserved and unsafe attributes are removed.
    $assert_session->elementTextContains('css', '.tippy-box strong', 'Strong tooltip');
    $assert_session->elementAttributeContains('css', '.tippy-box a', 'href', 'https://www.drupal.org');
    $assert_session->elementNotExists('css', '.tippy-box img[onerror]');
    $this->assertFalse((bool) $this->getSession()->evaluateScript('window.webformTooltipDescriptionExecuted === true'));
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

  /**
   * Creates tooltip fixtures and attaches Drupal behaviors.
   */
  protected function createTooltipFixtures(): void {
    $this->getSession()->executeScript(<<<'JS'
(function () {
  document.querySelector('form').insertAdjacentHTML(
    'beforeend',
    '<a href="#" class="js-webform-tooltip-link" title="&lt;span class=&quot;webform-test-tooltip-link-injected&quot;&gt;Injected tooltip link&lt;/span&gt;&lt;img src=&quot;x&quot; onerror=&quot;window.webformTooltipTitleExecuted = true&quot;&gt;">Tooltip link</a>' +
    '<div class="js-webform-tooltip-element webform-tooltip-element form-item-description-tooltip-html">' +
    '<div class="description visually-hidden">' +
    '<div class="webform-element-description visually-hidden">' +
    '<strong>Strong tooltip</strong> <a href="https://www.drupal.org">Drupal</a><img src="x" onerror="window.webformTooltipDescriptionExecuted = true">' +
    '</div>' +
    '</div>' +
    '</div>',
  );
  Drupal.attachBehaviors(document.querySelector('form'));
})();
JS);
  }

}
