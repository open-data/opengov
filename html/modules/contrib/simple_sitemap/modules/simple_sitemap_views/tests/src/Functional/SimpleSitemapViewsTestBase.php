<?php

namespace Drupal\Tests\simple_sitemap_views\Functional;

use Drupal\Tests\simple_sitemap\Functional\SimplesitemapTestBase;
use Drupal\views\Views;

/**
 * Defines a base class for Simple XML Sitemap (Views) functional testing.
 */
abstract class SimpleSitemapViewsTestBase extends SimplesitemapTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'simple_sitemap_views',
    'simple_sitemap_views_test',
  ];

  /**
   * Views sitemap data.
   *
   * @var \Drupal\simple_sitemap_views\SimpleSitemapViews
   */
  protected $sitemapViews;

  /**
   * Test view.
   *
   * @var \Drupal\views\ViewExecutable
   */
  protected $testView;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->sitemapViews = $this->container->get('simple_sitemap.views');
    $this->testView = Views::getView('simple_sitemap_views_test_view');
    $this->testView->setDisplay('page_1');
  }

}
