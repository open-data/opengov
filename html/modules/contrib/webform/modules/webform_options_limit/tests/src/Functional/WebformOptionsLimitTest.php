<?php

namespace Drupal\Tests\webform_options_limit\Functional;

use Drupal\webform\Entity\Webform;
use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Webform options limit test.
 *
 * @group webform_browser
 */
class WebformOptionsLimitTest extends WebformBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'webform',
    'webform_options_limit',
    'webform_options_limit_test',
  ];

  /**
   * Test options limit.
   */
  public function testOptionsLimit() {
    $webform = Webform::load('test_handler_options_limit');

    $this->drupalGet('/webform/test_handler_options_limit');

    // Check that option A is available.
    $this->assertRaw('<input data-drupal-selector="edit-options-limit-default-a" type="checkbox" id="edit-options-limit-default-a" name="options_limit_default[A]" value="A" checked="checked" class="form-checkbox" />');
    $this->assertRaw('A [1 remaining]');

    // Check that option D is available.
    $this->assertRaw('<input data-drupal-selector="edit-options-limit-messages-d" aria-describedby="edit-options-limit-messages-d--description" type="checkbox" id="edit-options-limit-messages-d" name="options_limit_messages[D]" value="D" checked="checked" class="form-checkbox" />');
    $this->assertRaw('1 option remaining / 1 limit / 0 total');

    // Check that option H is available.
    $this->assertRaw('<option value="H" selected="selected">H [1 remaining]</option>');

    // Check that option K is available.
    $this->assertRaw('<option value="K" selected="selected">K [1 remaining]</option>');

    // Check that option O is available.
    $this->assertRaw('<option value="O" selected="selected">O [1 remaining]</option>');

    // Post first submission.
    $sid_1 = $this->postSubmission($webform);

    // Check that option A is disabled with 0 remaining.
    $this->assertRaw('<input data-drupal-selector="edit-options-limit-default-a" disabled="disabled" type="checkbox" id="edit-options-limit-default-a" name="options_limit_default[A]" value="A" class="form-checkbox" />');
    $this->assertRaw('A [0 remaining]');

    // Check that option B is disabled with custom remaining message.
    $this->assertRaw('<input data-drupal-selector="edit-options-limit-messages-d" aria-describedby="edit-options-limit-messages-d--description" disabled="disabled" type="checkbox" id="edit-options-limit-messages-d" name="options_limit_messages[D]" value="D" class="form-checkbox" />');
    $this->assertRaw('No options remaining / 1 limit / 1 total');

    // Check that option H is no longer selected and disabled via JavaScript.
    $this->assertRaw('<option value="H">H [0 remaining]</option>');
    $this->assertRaw('data-webform-select-options-disabled="H"');

    // Check that option K was removed.
    $this->assertNoRaw('<option value="K"');

    // Check that option O was not changed but is not selected.
    $this->assertRaw('<option value="O">O [0 remaining]</option>');

    // Check that option O being selected triggers validation error.
    $this->postSubmission($webform, ['options_limit_select_none[]' => 'O']);
    $this->assertRaw('options_limit_select_none: O is unavailable.');

    // Chech that unavailable option can't be prepopulated.
    $this->drupalGet('/webform/test_handler_options_limit', ['query' => ['options_limit_default[]' => 'A']]);
    $this->assertNoFieldChecked('edit-options-limit-default-a');
    $this->drupalGet('/webform/test_handler_options_limit', ['query' => ['options_limit_default[]' => 'B']]);
    $this->assertFieldChecked('edit-options-limit-default-b');

    // Post two more submissions.
    $this->postSubmission($webform);
    $this->postSubmission($webform);

    // Change that 'options_limit_default' is disabled and not available.
    $this->assertRaw('A [0 remaining]');
    $this->assertRaw('B [0 remaining]');
    $this->assertRaw('C [0 remaining]');
    $this->assertRaw('options_limit_default is not available.');

    // Login as an admin.
    $this->drupalLogin($this->rootUser);

    // Check that random test values are only available options.
    $this->drupalGet('/webform/test_handler_options_limit/test');
    $this->assertRaw('<option value="J" selected="selected">J [Unlimited]</option>');
    $this->drupalGet('/webform/test_handler_options_limit/test');
    $this->assertRaw('<option value="J" selected="selected">J [Unlimited]</option>');
    $this->drupalGet('/webform/test_handler_options_limit/test');
    $this->assertRaw('<option value="J" selected="selected">J [Unlimited]</option>');

    // Check that existing submission values are not disabled.
    $this->drupalGet("/admin/structure/webform/manage/test_handler_options_limit/submission/$sid_1/edit");
    $this->assertRaw('<input data-drupal-selector="edit-options-limit-default-a" type="checkbox" id="edit-options-limit-default-a" name="options_limit_default[A]" value="A" checked="checked" class="form-checkbox" />');
    $this->assertRaw('A [0 remaining]');
    $this->assertRaw('<input data-drupal-selector="edit-options-limit-messages-d" aria-describedby="edit-options-limit-messages-d--description" type="checkbox" id="edit-options-limit-messages-d" name="options_limit_messages[D]" value="D" checked="checked" class="form-checkbox" />');
    $this->assertRaw('No options remaining / 1 limit / 1 total');
    $this->assertRaw('<option value="H" selected="selected">H [0 remaining]</option>');
    $this->assertRaw('<option value="K" selected="selected">K [0 remaining]</option>');
    $this->assertRaw('<option value="O" selected="selected">O [0 remaining]</option>');

    // Check handler element error messages.
    $webform->deleteElement('options_limit_default');
    $webform->save();
    $this->drupalGet('/admin/structure/webform/manage/test_handler_options_limit/handlers');
    $this->assertRaw('<b class="color-error">\'options_limit_default\' is missing.</b>');
  }

}
