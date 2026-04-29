<?php

namespace Drupal\Tests\views_filters_summary\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\views\Entity\View;
use Drupal\views\Tests\ViewTestData;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests JS behavior of the views_filters_summary module.
 *
 * @group views_filters_summary
 */
#[Group('views_filters_summary')]
#[RunTestsInSeparateProcesses]
class ViewsFiltersSummaryJsTest extends WebDriverTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'user',
    'field',
    'text',
    'filter',
    'system',
    'taxonomy',
    'views',
    'views_filters_summary',
    'views_filters_summary_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_vfs_summary'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    ViewTestData::createTestViews(
      static::class,
      ['views_filters_summary_test']
    );

    // Create content types.
    $this->createContentType([
      'type' => 'article',
      'name' => 'Article',
    ]);
    $this->createContentType([
      'type' => 'page',
      'name' => 'Basic page',
    ]);

    // Create 5 published nodes: 3 articles and 2 pages.
    $this->createNode([
      'type' => 'article',
      'title' => 'Article one',
    ]);
    $this->createNode([
      'type' => 'article',
      'title' => 'Article two',
    ]);
    $this->createNode([
      'type' => 'article',
      'title' => 'Article three',
    ]);
    $this->createNode([
      'type' => 'page',
      'title' => 'Page one',
    ]);
    $this->createNode([
      'type' => 'page',
      'title' => 'Page two',
    ]);

    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);
  }

  /**
   * Tests summary renders and JS initializes correctly.
   */
  public function testSummaryRendersAndJsInitializes(): void {
    // Navigate with an active title filter via URL query.
    $this->drupalGet('test-vfs-summary', [
      'query' => ['title' => 'Article'],
    ]);

    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Verify the summary container is present.
    $summary = $assert->waitForElementVisible(
      'css',
      '.views-filters-summary'
    );
    $this->assertNotNull($summary);

    // Verify the filter value appears in the summary.
    $assert->pageTextContains('Contains Article');

    // Verify JS initialized: disabled class removed.
    $link = $assert->waitForElementVisible(
      'css',
      'a.remove-filter:not(.disabled)'
    );
    $this->assertNotNull($link);

    // Verify all remove links have disabled class removed.
    $links = $page->findAll(
      'css',
      '.views-filters-summary a.remove-filter'
    );
    $this->assertNotEmpty($links);
    foreach ($links as $removeLink) {
      $this->assertStringNotContainsString(
        'disabled',
        $removeLink->getAttribute('class')
      );
    }
  }

  /**
   * Tests removing a text filter via the remove link.
   */
  public function testRemoveTextFilter(): void {
    $this->drupalGet('test-vfs-summary');

    // Apply the title filter.
    $this->submitForm(['title' => 'Article'], 'Apply');

    $assert = $this->assertSession();

    // Wait for the page to load with the filter applied.
    $assert->waitForText('Contains Article');
    $assert->pageTextContains('Contains Article');
    $assert->pageTextContains('Displaying 3 results');

    // Click the remove-filter link for the title filter.
    $removeLink = $assert->waitForElementVisible(
      'css',
      'a.remove-filter:not(.disabled)'
    );
    $this->assertNotNull($removeLink);
    $removeLink->click();

    // Wait for page reload with all results.
    $assert->waitForText('Displaying 5 results');
    $assert->pageTextContains('Displaying 5 results');
    $assert->pageTextNotContains('Contains Article');
  }

  /**
   * Tests removing a select filter via the remove link.
   */
  public function testRemoveSelectFilter(): void {
    // Navigate with the type filter active.
    $this->drupalGet('test-vfs-summary', [
      'query' => ['type' => ['article']],
    ]);

    $assert = $this->assertSession();

    // Wait for the summary to show the bundle label.
    $value = $assert->waitForElementVisible(
      'css',
      '.views-filters-summary .value'
    );
    $this->assertNotNull($value);
    $this->assertEquals('Article', $value->getText());
    $assert->pageTextContains('Displaying 3 results');

    // Click the remove-filter link.
    $removeLink = $assert->waitForElementVisible(
      'css',
      'a.remove-filter:not(.disabled)'
    );
    $this->assertNotNull($removeLink);
    $removeLink->click();

    // Wait for page reload with all results.
    $assert->waitForText('Displaying 5 results');
    $assert->pageTextContains('Displaying 5 results');

    // Verify the summary is no longer present.
    $this->assertNull(
      $this->getSession()->getPage()->find(
        'css',
        '.views-filters-summary .value'
      )
    );
  }

  /**
   * Tests the Reset link clears all active filters.
   */
  public function testResetAllFilters(): void {
    // Enable the reset link on the view.
    $view = View::load('test_vfs_summary');
    $display = &$view->getDisplay('default');
    $display['display_options']['header']['views_filters_summary']['show_reset_link'] = TRUE;
    $view->save();

    // Navigate with both title and type filters active.
    $this->drupalGet('test-vfs-summary', [
      'query' => [
        'title' => 'Article',
        'type' => ['article'],
      ],
    ]);

    $assert = $this->assertSession();

    // Wait for the summary to render with both filters.
    $assert->waitForText('Contains Article');
    $assert->pageTextContains('Contains Article');
    $assert->pageTextContains('Displaying 3 results');

    // Verify the reset link is present and JS-enabled.
    $resetLink = $assert->waitForElementVisible(
      'css',
      'a.reset:not(.disabled)'
    );
    $this->assertNotNull($resetLink);

    // Click the Reset link.
    $resetLink->click();

    // Wait for page reload with all results.
    $assert->waitForText('Displaying 5 results');
    $assert->pageTextContains('Displaying 5 results');

    // Verify all filter summaries are cleared.
    $assert->pageTextNotContains('Contains Article');
    $this->assertNull(
      $this->getSession()->getPage()->find(
        'css',
        '.views-filters-summary'
      )
    );
  }

  /**
   * Tests removing a filter via AJAX without page reload.
   */
  public function testAjaxRemoveFilter(): void {
    // Enable AJAX on the view.
    $view = View::load('test_vfs_summary');
    $display = &$view->getDisplay('default');
    $display['display_options']['use_ajax'] = TRUE;
    $view->save();

    $this->drupalGet('test-vfs-summary');

    $assert = $this->assertSession();

    // Apply the title filter via AJAX form submission.
    $this->submitForm(['title' => 'Article'], 'Apply');
    $assert->assertWaitOnAjaxRequest();

    // Verify the filter summary is present.
    $assert->waitForText('Contains Article');
    $assert->pageTextContains('Contains Article');
    $assert->pageTextContains('Displaying 3 results');

    // Capture the views DOM ID to verify no page reload.
    $settingsBefore = $this->getDrupalSettings();
    $ajaxViewsBefore = $settingsBefore['views']['ajaxViews'];

    // Click the remove-filter link.
    $removeLink = $assert->waitForElementVisible(
      'css',
      'a.remove-filter:not(.disabled)'
    );
    $this->assertNotNull($removeLink);
    $removeLink->click();

    // Wait for the AJAX request to complete.
    $assert->assertWaitOnAjaxRequest();

    // Verify no page reload occurred (DOM ID unchanged).
    $settingsAfter = $this->getDrupalSettings();
    $ajaxViewsAfter = $settingsAfter['views']['ajaxViews'];
    $this->assertSame($ajaxViewsBefore, $ajaxViewsAfter);

    // Verify the filter is removed and all results shown.
    $assert->waitForText('Displaying 5 results');
    $assert->pageTextContains('Displaying 5 results');
    $assert->pageTextNotContains('Contains Article');
  }

}
