<?php

/**
 * @file
 * Contains a base class for Simple XML Sitemap (Views) functional testing.
 */

namespace Drupal\Tests\simple_sitemap_views\Functional;

use Drupal\Tests\simple_sitemap\Functional\SimplesitemapTestBase;

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
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->sitemapViews = $this->container->get('simple_sitemap.views');
  }

}
