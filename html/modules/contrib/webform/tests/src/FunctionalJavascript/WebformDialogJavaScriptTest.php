<?php

namespace Drupal\Tests\webform\FunctionalJavascript;

/**
 * Tests webform dialog JavaScript.
 *
 * @group webform_javascript
 */
class WebformDialogJavaScriptTest extends WebformWebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['webform', 'help'];

  /**
   * Tests programmatic dialog link construction.
   */
  public function testWebformOpenDialog(): void {
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('settings.dialog', TRUE)
      ->save();
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/admin/help/webform');
    $this->assertJsCondition('typeof Drupal !== "undefined" && typeof Drupal.webformOpenDialog === "function"');

    $results = $this->getSession()->evaluateScript(<<<'JS'
(function () {
  var originalAttach = Drupal.behaviors.webformDialog.attach;
  var results = [];

  Drupal.behaviors.webformDialog.attach = function (context) {
    results.push({
      links: context.querySelectorAll('a').length,
      images: context.querySelectorAll('img').length,
      payloadElements: context.querySelectorAll('[data-webform-dialog-test]').length
    });
  };

  Drupal.webformOpenDialog('"><img src=x data-webform-dialog-test="url">', 'webform-dialog-normal');
  Drupal.webformOpenDialog('/webform/contact', '"><img src=x data-webform-dialog-test="type">');

  Drupal.behaviors.webformDialog.attach = originalAttach;
  return results;
}());
JS);

    // Check that the URL payload does not inject markup.
    $this->assertSame(1, $results[0]['links']);
    $this->assertSame(0, $results[0]['images']);
    $this->assertSame(0, $results[0]['payloadElements']);

    // Check that the dialog type payload does not inject markup.
    $this->assertSame(1, $results[1]['links']);
    $this->assertSame(0, $results[1]['images']);
    $this->assertSame(0, $results[1]['payloadElements']);
  }

}
