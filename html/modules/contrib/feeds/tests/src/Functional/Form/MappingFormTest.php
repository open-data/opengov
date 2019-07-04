<?php

namespace Drupal\Tests\feeds\Functional\Form;

use Drupal\Tests\feeds\Functional\FeedsBrowserTestBase;

/**
 * @coversDefaultClass \Drupal\feeds\Form\MappingForm
 * @group feeds
 */
class MappingFormTest extends FeedsBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['feeds_test_plugin'];

  /**
   * Tests that plugins can alter the mapping form.
   */
  public function testPluginWithMappingForm() {
    $feed_type = $this->createFeedType([
      'parser' => 'parser_with_mapping_form',
    ]);

    $edit = [
      'dummy' => 'dummyValue',
    ];

    $this->drupalPostForm('/admin/structure/feeds/manage/' . $feed_type->id() . '/mapping', $edit, 'Save');

    // Assert that the dummy value was saved for the parser.
    $feed_type = $this->reloadEntity($feed_type);
    $config = $feed_type->getParser()->getConfiguration();
    $this->assertEquals('dummyValue', $config['dummy']);
  }

  /**
   * Tests that plugins validate the mapping form.
   */
  public function testPluginWithMappingFormValidate() {
    $feed_type = $this->createFeedType([
      'parser' => 'parser_with_mapping_form',
    ]);

    // ParserWithMappingForm::mappingFormValidate() doesn't accept the value
    // 'invalid'.
    $edit = [
      'dummy' => 'invalid',
    ];

    $this->drupalPostForm('/admin/structure/feeds/manage/' . $feed_type->id() . '/mapping', $edit, 'Save');
    $this->assertText('Invalid value.');

    // Assert that the dummy value was *not* saved for the parser.
    $feed_type = $this->reloadEntity($feed_type);
    $config = $feed_type->getParser()->getConfiguration();
    $this->assertEquals('', $config['dummy']);
  }

}
