<?php

namespace Drupal\Tests\feeds\Functional\Plugin\Field\FieldFormatter;

/**
 * Tests feeds_item_imported field formatter.
 *
 * @group feeds
 */
class FeedsItemImportedFormatterTest extends FeedsItemFormatterTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Set display mode for feeds_item to feeds_item_imported on article content
    // type.
    entity_get_display('node', 'article', 'default')
      ->setComponent('feeds_item', [
        'type' => 'feeds_item_imported',
        'weight' => 1,
      ])
      ->save();
  }

  /**
   * Switch the date format to m-d-Y for testing.
   */
  protected function switchToCustomDateFormat() {
    $settings = [
      'date_format' => 'custom',
      'custom_date_format' => 'm-d-Y',
    ];
    // Set display mode for feeds_item to feeds_item_imported on article content
    // type with custom date format.
    entity_get_display('node', 'article', 'default')
      ->setComponent('feeds_item', [
        'type' => 'feeds_item_imported',
        'settings' => $settings,
        'weight' => 1,
      ])
      ->save();
  }

  /**
   * Test the feeds item imported formatter.
   *
   * @covers \Drupal\feeds\Plugin\Field\FieldFormatter\FeedsItemImportedFormatter::viewElements
   *
   * @dataProvider providerImported
   */
  public function testFeedsItemImportedFormatter($input, $expected) {
    $feed = $this->createCsvFeed();

    // Create an article and set imported time.
    $article = $this->createNodeWithFeedsItem($feed);
    $article->feeds_item->imported = $input;

    // Set custom date format for last test to m-d-Y.
    if ($input == '1543370515') {
      $this->switchToCustomDateFormat();
    }

    // Display the article and test we are getting correct output for
    // 'imported'.
    $display = entity_get_display($article->getEntityTypeId(), $article->bundle(), 'default');
    $content = $display->build($article);
    $rendered_content = $this->container->get('renderer')->renderRoot($content);
    $this->assertContains($expected, (string) $rendered_content);
  }

  /**
   * Data provider for ::testFeedsItemImportedFormatter().
   */
  public function providerImported() {
    return [
      'timestamp default date format' => ['1543374515', '<div class="field__item">Wed, 11/28/2018 - 14:08</div>'],
      'timestamp custom date format' => ['1543370515', '<div class="field__item">11-28-2018</div>'],
    ];
  }

}
