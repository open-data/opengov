<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\Core\Form\FormState;
use Drupal\webform\Element\WebformElementAttributes;

/**
 * Tests for webform element attributes.
 *
 * @group webform
 */
class WebformElementAttributesTest extends WebformElementBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform_ui'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_attributes'];

  /**
   * Tests element attributes.
   */
  public function testAttributes(): void {
    $assert_session = $this->assertSession();

    /* Access */

    // Check that the attributes editor is hidden without source access.
    $admin_webform_account = $this->drupalCreateUser(['administer webform']);
    $this->drupalLogin($admin_webform_account);
    $element = [
      '#id' => 'webform-element-attributes',
      '#title' => 'webform_element_attributes',
      '#classes' => '',
      '#default_value' => [
        'class' => [],
        'style' => '',
        'custom' => 'test',
      ],
    ];
    $complete_form = [];
    $form_state = new FormState();
    WebformElementAttributes::processWebformElementAttributes($element, $form_state, $complete_form);
    $this->assertArrayHasKey('#access', $element['attributes']);
    $this->assertFalse($element['attributes']['#access']);

    // Check that the attributes editor is visible with source access.
    $source_webform_account = $this->drupalCreateUser(['administer webform', 'edit webform source']);
    $this->drupalLogin($source_webform_account);
    $element = [
      '#id' => 'webform-element-attributes',
      '#title' => 'webform_element_attributes',
      '#classes' => '',
      '#default_value' => [
        'class' => [],
        'style' => '',
        'custom' => 'test',
      ],
    ];
    $complete_form = [];
    $form_state = new FormState();
    WebformElementAttributes::processWebformElementAttributes($element, $form_state, $complete_form);
    $this->assertArrayHasKey('#access', $element['attributes']);
    $this->assertTrue($element['attributes']['#access']);

    /* Rendering */

    $this->drupalGet('/webform/test_element_attributes');

    // Check four and five are merged in class select other text field.
    $assert_session->fieldValueEquals('webform_element_attributes[class][other]', 'four five');

    // Check one, two, four, and five are merged in class text field.
    $assert_session->fieldValueEquals('webform_element_attributes_no_classes[class]', 'one two four five');

    /* Validation */

    $this->drupalGet('/webform/test_element_attributes');
    $this->submitForm(['webform_element_attributes[attributes]' => "'not: valid"], 'Submit');
    $assert_session->responseContains('<em class="placeholder">webform_element_attributes custom attributes (YAML)</em> is not valid.');
    $assert_session->responseContains('<ul><li>Malformed inline YAML string at line 1 (near &quot;&#039;not: valid&quot;).</li></ul>');

    /* Submit */

    // Check default value handling.
    $this->drupalGet('/webform/test_element_attributes');
    $this->submitForm([], 'Submit');
    $assert_session->responseContains("webform_element_attributes:
  class:
    - one
    - two
    - four
    - five
  style: 'color: red'
  custom: test
webform_element_attributes_no_classes:
  class:
    - one
    - two
    - four
    - five");
  }

}
