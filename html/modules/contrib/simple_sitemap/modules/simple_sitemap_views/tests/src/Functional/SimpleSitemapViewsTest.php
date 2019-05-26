<?php

/**
 * @file
 * Contains functional tests for Simple XML Sitemap (Views).
 */

namespace Drupal\Tests\simple_sitemap_views\Functional;

/**
 * Tests Simple XML Sitemap (Views) functional integration.
 *
 * @group simple_sitemap_views
 */
class SimpleSitemapViewsTest extends SimpleSitemapViewsTestBase {

  /**
   * Tests Views URL generator availability.
   */
  public function testViewsUrlGeneratorAvailability() {
    $sitemap_types = $this->generator->getSitemapManager()->getSitemapTypes();
    $this->assertContains('views', $sitemap_types['default_hreflang']['urlGenerators']);
  }

  /**
   * Tests status of sitemap support for views.
   */
  public function testSitemapSupportForViews() {
    // Views support must be enabled after module installation.
    $this->assertTrue($this->sitemapViews->isEnabled());

    $this->sitemapViews->disable();
    $this->assertFalse($this->sitemapViews->isEnabled());

    $this->sitemapViews->enable();
    $this->assertTrue($this->sitemapViews->isEnabled());
  }

  /**
   * Tests indexable views.
   */
  public function testIndexableViews() {
    // Ensure that at least one indexable view exists.
    $indexable_views = $this->sitemapViews->getIndexableViews();
    $this->assertNotEmpty($indexable_views);

    $test_view = NULL;
    foreach ($indexable_views as &$view) {
      if ($view->id() == 'simple_sitemap_views_test_view' && $view->current_display == 'page_1') {
        $test_view = $view;
        break;
      }
    }
    // The test view should be in the list.
    $this->assertNotNull($test_view);

    // Check the indexing status of the argument.
    $indexable_arguments = $this->sitemapViews->getIndexableArguments($test_view);
    $this->assertContains('type', $indexable_arguments);
  }

}
