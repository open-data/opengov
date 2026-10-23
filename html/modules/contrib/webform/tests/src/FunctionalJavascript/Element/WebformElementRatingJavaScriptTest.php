<?php

namespace Drupal\Tests\webform\FunctionalJavascript\Element;

use Drupal\Tests\webform\FunctionalJavascript\WebformWebDriverTestBase;

/**
 * Tests webform rating element JavaScript.
 *
 * @group webform_javascript
 */
class WebformElementRatingJavaScriptTest extends WebformWebDriverTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = [
    'test_element_rating',
  ];

  /**
   * Tests rating element JavaScript.
   */
  public function testRating(): void {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('/webform/test_element_rating');

    // Check that rating widgets are initialized.
    // cspell:disable-next-line
    $assert_session->waitForElement('css', '#edit-rating-basic + .rateit .rateit-range');
    // cspell:disable-next-line
    $assert_session->waitForElement('css', '#edit-rating-advanced + .rateit .rateit-range');
    $this->assertTrue($session->wait(5000, "jQuery('#edit-rating-basic + .rateit .rateit-reset').is(':hidden')"));
    $this->assertTrue($session->wait(5000, "jQuery('#edit-rating-advanced + .rateit .rateit-reset').is(':visible')"));

    // Check that changing the input updates the rating widget.
    $session->executeScript("jQuery('#edit-rating-basic').val('3').trigger('change');");
    $this->assertTrue($session->wait(5000, "jQuery('#edit-rating-basic + .rateit').rateit('value') === 3"));

    // Check that changing the rating widget updates the input.
    $session->executeScript("jQuery('#edit-rating-advanced + .rateit').rateit('value', 2.5);");
    $this->assertTrue($session->wait(5000, "jQuery('#edit-rating-advanced').val() === '2.5'"));

    // Check that disabling the input updates the rating widget.
    $session->executeScript("jQuery('#edit-rating-basic').prop('disabled', true).trigger('webform:disabled');");
    $this->assertTrue($session->wait(5000, "jQuery('#edit-rating-basic + .rateit').rateit('readonly') === true"));
    $session->executeScript("jQuery('#edit-rating-basic').prop('disabled', false).trigger('webform:disabled');");
    $this->assertTrue($session->wait(5000, "jQuery('#edit-rating-basic + .rateit').rateit('readonly') === false"));

    // Check that unsafe same-page RateIt markup cannot execute script.
    // cspell:disable
    $session->executeScript(<<<'JS'
window.webformRateItBackingFieldExecuted = false;
window.webformRateItBackingFieldException = false;

const container = document.createElement('div');
container.id = 'webform-rating-unsafe-backing-field-test';

const rateIt = document.createElement('div');
rateIt.className = 'rateit';
rateIt.setAttribute('data-rateit-backingfld', '<img src=x onerror="window.webformRateItBackingFieldExecuted = true">');

container.appendChild(rateIt);
document.querySelector('main').appendChild(container);
const throwError = Drupal.throwError;
Drupal.throwError = function () {
  window.webformRateItBackingFieldException = true;
};
Drupal.attachBehaviors(container);
Drupal.throwError = throwError;
JS);
    // cspell:enable
    $this->assertFalse($page->waitFor(1, function () use ($session) {
      return $session->evaluateScript('window.webformRateItBackingFieldException === true');
    }));
    $this->assertFalse($page->waitFor(1, function () use ($session) {
      return $session->evaluateScript('window.webformRateItBackingFieldExecuted === true');
    }));
  }

}
