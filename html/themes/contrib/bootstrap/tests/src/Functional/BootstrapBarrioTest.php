<?php

namespace Drupal\Tests\bootstrap\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Bootstrap theme.
 *
 * @group claro
 */
class BootstrapBootstrapTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * Install the shortcut module so that bootstrap_bario.settings has its schema
   * checked. There's currently no way for Bootstrap to provide a default
   * and have valid configuration as themes cannot react to a module install.
   *
   * @var string[]
   */
  protected static $modules = ['shortcut'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'bootstrap';


  /**
   * Test Bootstrap's configuration schema.
   */
  public function testConfigSchema() {
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('admin/appearance/settings/' . $this->defaultTheme);
    $this->submitForm([], 'Save configuration');
    $this->assertSession()->statusCodeEquals(200);
  }

}
