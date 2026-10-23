<?php

namespace Drupal\Tests\webform\FunctionalJavascript;

/**
 * Tests webform XSS JavaScript.
 *
 * @group webform_javascript
 */
class WebformXssJavaScriptTest extends WebformWebDriverTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = [
    'test_element_description',
  ];

  /**
   * Tests filtering HTML to editor-supported markup.
   */
  public function testFilter(): void {
    $this->drupalGet('/webform/test_element_description');
    $this->assertSession()->waitForElement('css', '.js-webform-tooltip-element');

    // Check that default editor-supported tags and safe attributes are preserved.
    $filtered_markup = $this->filterHtml('<h2 id="heading">Heading</h2><blockquote cite="https://www.drupal.org">Quote</blockquote><ol start="2" reversed type="A"><li><strong>Bold</strong> <em>italic</em> <sub>sub</sub> <sup>sup</sup> <a href="https://www.drupal.org" hreflang="en">Drupal</a></li></ol>');
    $this->assertEquals('<h2>Heading</h2><blockquote>Quote</blockquote><ol start="2" reversed=""><li><strong>Bold</strong> <em>italic</em> <sub>sub</sub> <sup>sup</sup> <a href="https://www.drupal.org">Drupal</a></li></ol>', $filtered_markup);

    // Check that unsupported tags, event attributes, and unsafe URLs are removed.
    $filtered_markup = $this->filterHtml('<webform-unsafe>Text</webform-unsafe><img src="javascript:alert(1)" onerror="window.webformXssExecuted = true"><a href="javascript:alert(1)" onclick="window.webformXssClicked = true">Unsafe link</a>');
    $this->assertEquals('Text<img><a>Unsafe link</a>', $filtered_markup);
    $this->assertFalse((bool) $this->getSession()->evaluateScript('window.webformXssExecuted === true || window.webformXssClicked === true'));

    // Check that configured allowed tags are used by the JavaScript filter.
    \Drupal::configFactory()
      ->getEditable('webform.settings')
      ->set('element.allowed_tags', 'a h2 span')
      ->save();
    $this->drupalGet('/webform/test_element_description');
    $this->assertSession()->waitForElement('css', '.js-webform-tooltip-element');
    $filtered_markup = $this->filterHtml('<span class="status">Text</span><em>Emphasis</em><a href="https://www.drupal.org" hreflang="en">Drupal</a>');
    \Drupal::configFactory()
      ->getEditable('webform.settings')
      ->set('element.allowed_tags', 'admin')
      ->save();
    $this->assertEquals('<span>Text</span>Emphasis<a href="https://www.drupal.org">Drupal</a>', $filtered_markup);
  }

  /**
   * Filters HTML using Webform's JavaScript XSS helper.
   *
   * @param string $html
   *   The HTML to filter.
   *
   * @return string
   *   The filtered HTML.
   */
  protected function filterHtml(string $html): string {
    $html = json_encode($html);
    $script = <<<JS
(function () {
  return Drupal.webform.xss.filter($html);
})();
JS;
    return $this->getSession()->evaluateScript($script);
  }

}
