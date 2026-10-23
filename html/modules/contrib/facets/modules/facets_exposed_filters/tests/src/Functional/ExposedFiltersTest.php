<?php

declare(strict_types=1);

namespace Drupal\Tests\facets_exposed_filters\Functional;

use Drupal\views\Entity\View;
use Drupal\Tests\facets\Functional\FacetsTestBase;

/**
 * Tests the overall functionality of the Facets admin UI.
 *
 * @group facets
 */
class ExposedFiltersTest extends FacetsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'views_ui',
    'node',
    'search_api',
    'facets',
    'facets_exposed_filters',
    'facets_exposed_filters_test',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->rootUser);

    $this->setUpExampleStructure();
    $this->insertExampleContent();
    $this->assertEquals(5, $this->indexItems($this->indexId), '5 items were indexed.');
  }

  /**
   * Tests slider widget.
   */
  public function testExposedFilters() {
    // Test non-filtered page.
    $this->drupalGet('test-facets-exposed-filters');
    $this->assertSession()->pageTextContains('Keywords');
    $this->assertSession()->pageTextContains('entity:entity_test_mulrev_changed/3:en');
    $this->assertSession()->pageTextContains('strawberry');

    // Test filtered page.
    $this->drupalGet('test-facets-exposed-filters', ['query' => ['keywords[]' => 'apple']]);
    $this->assertSession()->pageTextContains('Keywords');
    $this->assertSession()->pageTextNotContains('entity:entity_test_mulrev_changed/3:en');
    $this->assertSession()->pageTextContains('strawberry');

    // Test if facet item disappears when non-matching category is selected.
    $this->drupalGet('test-facets-exposed-filters', ['query' => ['category[]' => 'item_category']]);
    $this->assertSession()->pageTextContains('Keywords');
    $this->assertSession()->pageTextNotContains('strawberry');

    // Test if facet item remains when matching category is selected.
    $this->drupalGet('test-facets-exposed-filters', ['query' => ['category[]' => 'article_category']]);
    $this->assertSession()->pageTextContains('Keywords');
    $this->assertSession()->pageTextContains('strawberry');
  }

  /**
   * Tests show-only-one-result behavior for exposed filters.
   */
  public function testShowOnlyOneResultForExposedFilters() {
    $view = View::load('test_facets_exposed_filters');
    $displays = $view->get('display');
    $displays['default']['display_options']['filters']['facets_keywords']['facet']['show_only_one_result'] = TRUE;
    $view->set('display', $displays);
    $view->save();

    $this->drupalGet('test-facets-exposed-filters');
    $this->assertSession()->pageTextContains('apple');
    $this->assertSession()->pageTextContains('strawberry');

    $this->drupalGet('test-facets-exposed-filters', ['query' => ['keywords[]' => 'apple']]);
    $this->assertSession()->pageTextContains('apple');
    $this->assertSession()->pageTextNotContains('strawberry');

    $this->drupalGet('test-facets-exposed-filters');
    $this->assertSession()->pageTextContains('strawberry');
  }

  /**
   * Tests that hierarchy-related processors are available in the Views UI.
   */
  public function testHierarchyProcessorsAreAvailableInViewsUi(): void {
    $this->drupalGet('admin/structure/views/nojs/handler-extra/test_facets_exposed_filters/default/filter/facets_category');

    $this->assertSession()->pageTextContains('Hide inactive siblings');
    $this->assertSession()->pageTextContains('Show siblings');
    $this->assertSession()->pageTextContains('Show only deepest item levels');
  }

  /**
   * Tests hide inactive siblings behavior on exposed filters.
   */
  public function testHideInactiveSiblingsProcessorForExposedFilters(): void {
    $view = View::load('test_facets_exposed_filters');
    $displays = $view->get('display');
    $displays['default']['display_options']['filters']['facets_keywords']['facet']['processor_configs']['hide_inactive_siblings_processor'] = [
      'processor_id' => 'hide_inactive_siblings_processor',
      'settings' => [],
      'weights' => [
        'build' => 10,
      ],
    ];
    $view->set('display', $displays);
    $view->save();

    $this->drupalGet('test-facets-exposed-filters');
    $this->assertSession()->pageTextContains('apple');
    $this->assertSession()->pageTextContains('strawberry');

    $this->drupalGet('test-facets-exposed-filters', ['query' => ['keywords[]' => 'apple']]);
    $this->assertSession()->pageTextContains('apple');
    $this->assertSession()->pageTextNotContains('strawberry');

    $this->drupalGet('test-facets-exposed-filters');
    $this->assertSession()->pageTextContains('strawberry');
  }

}
