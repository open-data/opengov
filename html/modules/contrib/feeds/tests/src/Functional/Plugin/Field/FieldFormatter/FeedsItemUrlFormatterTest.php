<?php

namespace Drupal\Tests\feeds\Functional\Plugin\Field\FieldFormatter;

/**
 * Tests feeds_item_url field formatter.
 *
 * @group feeds
 */
class FeedsItemUrlFormatterTest extends FeedsItemFormatterTestBase {

  /**
   * Test the feeds item url formatter.
   *
   * @covers \Drupal\feeds\Plugin\Field\FieldFormatter\FeedsItemUrlFormatter::viewElements
   *
   * @dataProvider providerUrls
   */
  public function testFeedsItemUrlFormatter($input, $expected) {
    // Set display mode for feeds_item to feeds_item_url on article content
    // type.
    entity_get_display('node', 'article', 'default')
      ->setComponent('feeds_item', [
        'type' => 'feeds_item_url',
        'settings' => ['url_plain' => FALSE],
        'weight' => 1,
      ])
      ->save();

    $feed = $this->createCsvFeed();

    // Setup the article with test url.
    $article = $this->createNodeWithFeedsItem($feed);
    $article->feeds_item->url = $input;

    // Display the article and test we are getting correct output for url.
    $display = entity_get_display($article->getEntityTypeId(), $article->bundle(), 'default');
    $content = $display->build($article);
    $rendered_content = $this->container->get('renderer')->renderRoot($content);
    if ($expected) {
      $this->assertContains($expected, (string) $rendered_content);
    }
    else {
      // If nothing is expected to be displayed, check if the field is rendered
      // at all.
      $this->assertFeedsItemFieldNotDisplayed($rendered_content, $input);
    }
  }

  /**
   * Data provider for ::testFeedsItemUrlFormatter().
   */
  public function providerUrls() {
    return [
      'empty url' => ['', NULL],
      'http url' => ['http://en.wikipedia.org/wiki/Star_Control', '<div class="field__item"><a href="http://en.wikipedia.org/wiki/Star_Control">http://en.wikipedia.org/wiki/Star_Control</a></div>'],
      'https url' => ['https://en.wikipedia.org/wiki/Star_Control_II', '<div class="field__item"><a href="https://en.wikipedia.org/wiki/Star_Control_II">https://en.wikipedia.org/wiki/Star_Control_II</a></div>'],
      'non http or https html url' => ['<strong>SkyNet activated</strong>', NULL],
    ];
  }

  /**
   * Test that the plain text URL display setting works.
   *
   * @covers \Drupal\feeds\Plugin\Field\FieldFormatter\FeedsItemUrlFormatter::viewElements
   */
  public function testOutputUrlAsPlainText() {
    $input = 'https://en.wikipedia.org/wiki/Star_Control_3';
    $expected = '<div class="field__item">https://en.wikipedia.org/wiki/Star_Control_3</div>';

    // Set display mode for feeds_item to feeds_item_url on article content
    // type with plain_text_url setting on.
    entity_get_display('node', 'article', 'default')
      ->setComponent('feeds_item', [
        'type' => 'feeds_item_url',
        'settings' => ['url_plain' => TRUE],
        'weight' => 1,
      ])
      ->save();

    $feed = $this->createCsvFeed();

    // Create an article and set the 'url' property on the feeds_item field.
    $article = $this->createNodeWithFeedsItem($feed);
    $article->feeds_item->url = $input;

    $display = entity_get_display($article->getEntityTypeId(), $article->bundle(), 'default');
    $content = $display->build($article);
    $rendered_content = $this->container->get('renderer')->renderRoot($content);

    $this->assertContains($expected, (string) $rendered_content);
  }

}
