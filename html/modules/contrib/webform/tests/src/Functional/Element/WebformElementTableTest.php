<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for table elements.
 *
 * @group Webform
 */
class WebformElementTableTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_table'];

  /**
   * Tests table elements.
   */
  public function testTable() {
    // Check display elements within a table.
    $this->drupalGet('/webform/test_element_table');
    $this->assertRaw('<table class="js-form-wrapper responsive-enabled" data-drupal-selector="edit-table" id="edit-table" data-striping="1">');
    $this->assertRaw('<th>First Name</th>');
    $this->assertRaw('<th>Last Name</th>');
    $this->assertRaw('<th>Gender</th>');
    $this->assertRaw('<tr data-drupal-selector="edit-table-1" class="odd">');
    $this->assertRaw('<td><div class="js-form-item form-item js-form-type-textfield form-type-textfield js-form-item-table__1__first-name form-item-table__1__first-name form-no-label">');
    $this->assertRaw('<input data-drupal-selector="edit-table-1-first-name" type="text" id="edit-table-1-first-name" name="table__1__first_name" value="John" size="20" maxlength="255" class="form-text" />');

    // Check rendering.
    $this->drupalPostForm('/webform/test_element_table', [], t('Preview'));
    $this->assertRaw('<th>First Name</th>');
    $this->assertRaw('<th>Last Name</th>');
    $this->assertRaw('<th>Gender</th>');
    $this->assertRaw('<th>Markup</th>');
    $this->assertRaw('<td>John</td>');
    $this->assertRaw('<td>Smith</td>');
    $this->assertRaw('<td>Male</td>');
    $this->assertRaw('<td>{markup_1}</td>');
    $this->assertRaw('<td>Jane</td>');
    $this->assertRaw('<td>Doe</td>');
    $this->assertRaw('<td>Female</td>');
    $this->assertRaw('<td>{markup_2}</td>');
  }

}
