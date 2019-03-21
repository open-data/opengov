<?php

namespace Drupal\Tests\feeds\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests module uninstallation.
 *
 * @group feeds
 */
class FeedsUninstallTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['feeds'];

  /**
   * Tests module uninstallation.
   */
  public function testUninstall() {
    // Confirm that Feeds has been installed.
    $module_handler = $this->container->get('module_handler');
    $this->assertTrue($module_handler->moduleExists('feeds'));

    // Uninstall Feeds.
    $this->container->get('module_installer')->uninstall(['feeds']);
    $this->assertFalse($module_handler->moduleExists('feeds'));
  }

}
