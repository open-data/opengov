<?php

namespace Drupal\Tests\webform\FunctionalJavascript\Element;

use Drupal\Tests\webform\FunctionalJavascript\WebformWebDriverTestBase;

/**
 * Tests webform counter element JavaScript.
 *
 * @group webform_javascript
 */
class WebformElementCounterJavaScriptTest extends WebformWebDriverTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = [
    'test_element_counter',
  ];

  /**
   * Tests counter behavior.
   */
  public function testCounter(): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    /* ********************************************************************** */

    $this->drupalGet('/webform/test_element_counter');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.text-count-wrapper'));

    // Check that the counter displays the entered character count.
    $page->fillField('counter_characters_min_message', 'abc');
    $assert_session->waitForText('3 character(s) entered. This is custom text');

    // Check that the counter displays the remaining character count.
    $page->fillField('counter_characters_max_message', 'abc');
    $assert_session->waitForText('7 character(s) remaining. This is custom text');

    // Check that markup from counter message attributes is not injected into the DOM.
    $assert_session->elementTextContains('css', '#edit-counter-characters-xss + .text-count-wrapper', "alert('XSS');<em>10</em> character(s) remaining.");
    $assert_session->elementNotExists('css', '#edit-counter-characters-xss + .text-count-wrapper em');
    $assert_session->elementNotExists('css', '#edit-counter-characters-xss + .text-count-wrapper script');

    // Check that markup from counter data options is not injected into the DOM.
    $assert_session->elementTextContains('css', '#edit-counter-characters-options-xss + .text-count-wrapper', '<strong>0</strong> option character(s) entered.');
    $assert_session->elementExists('css', '#edit-counter-characters-options-xss + div.text-count-wrapper');
    $assert_session->elementNotExists('css', '#edit-counter-characters-options-xss + em.text-count-wrapper');
    $assert_session->elementNotExists('css', '#edit-counter-characters-options-xss + .text-count-wrapper strong');
    $assert_session->elementNotExists('css', '#edit-counter-characters-options-xss + .text-count-wrapper script');
  }

}
