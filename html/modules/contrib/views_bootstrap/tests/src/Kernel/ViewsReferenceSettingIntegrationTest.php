<?php

namespace Drupal\Tests\views_bootstrap\Kernel;

use Symfony\Component\Yaml\Yaml;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\views\Entity\View;

/**
 * Kernel tests for ViewsReferenceSetting plugins integration.
 *
 * @group views_bootstrap
 */
class ViewsReferenceSettingIntegrationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'node',
    'text',
    'filter',
    'views',
    'views_bootstrap',
    'viewsreference',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['field', 'node', 'views', 'filter']);

    // Create content types and test content.
    $this->createContentTypes();
    $this->createTestContent();
  }

  /**
   * Tests ViewsBootstrapGrid plugin integration.
   */
  public function testViewsBootstrapGridIntegration() {
    // Import test view fixture.
    $this->installViewFromFixture('views.view.test_bootstrap_grid_view.yml');

    // Create ViewsReference field.
    $this->createViewsReferenceField();

    // Test that the Grid view renders correctly.
    $view = View::load('test_bootstrap_grid_view');
    $this->assertNotNull($view, 'Test Bootstrap Grid view was loaded.');

    $executable = $view->getExecutable();
    $executable->execute();

    // Verify the view uses Bootstrap Grid style.
    $style_plugin = $executable->getStyle();
    $this->assertEquals('views_bootstrap_grid', $style_plugin->getPluginId());

    // Test ViewsReferenceSetting plugin is discovered.
    $plugin_manager = \Drupal::service('plugin.manager.viewsreference.setting');
    $definitions = $plugin_manager->getDefinitions();
    $this->assertArrayHasKey('bootstrap_grid', $definitions);

    // Test plugin instantiation.
    $plugin = $plugin_manager->createInstance('bootstrap_grid');
    $this->assertInstanceOf('Drupal\views_bootstrap\Plugin\ViewsReferenceSetting\ViewsBootstrapGrid', $plugin);
  }

  /**
   * Tests ViewsBootstrapCards plugin integration.
   */
  public function testViewsBootstrapCardsIntegration() {
    // Import test view fixture.
    $this->installViewFromFixture('views.view.test_bootstrap_cards_view.yml');

    // Test that the Cards view renders correctly.
    $view = View::load('test_bootstrap_cards_view');
    $this->assertNotNull($view, 'Test Bootstrap Cards view was loaded.');

    $executable = $view->getExecutable();
    $executable->execute();

    // Verify the view uses Bootstrap Cards style.
    $style_plugin = $executable->getStyle();
    $this->assertEquals('views_bootstrap_cards', $style_plugin->getPluginId());

    // Test ViewsReferenceSetting plugin is discovered.
    $plugin_manager = \Drupal::service('plugin.manager.viewsreference.setting');
    $definitions = $plugin_manager->getDefinitions();
    $this->assertArrayHasKey('bootstrap_cards', $definitions);

    // Test plugin instantiation.
    $plugin = $plugin_manager->createInstance('bootstrap_cards');
    $this->assertInstanceOf('Drupal\views_bootstrap\Plugin\ViewsReferenceSetting\ViewsBootstrapCards', $plugin);
  }

  /**
   * Tests ViewsBootstrapGrid settings override functionality.
   */
  public function testGridSettingsOverride() {
    // Import test view fixture.
    $this->installViewFromFixture('views.view.test_bootstrap_grid_view.yml');

    $view = View::load('test_bootstrap_grid_view');
    $this->assertNotNull($view, 'Test Bootstrap Grid view was loaded.');

    $executable = $view->getExecutable();
    $executable->execute();

    // Get the original style options.
    $style_plugin = $executable->getStyle();
    $original_options = $style_plugin->options;

    // Test settings override.
    $plugin_manager = \Drupal::service('plugin.manager.viewsreference.setting');
    $plugin = $plugin_manager->createInstance('bootstrap_grid');

    $override_values = [
      'grid_class' => 'custom-grid-class',
      'col_md' => 'col-md-6',
      'col_lg' => 'col-lg-4',
    ];

    // Apply the overrides.
    $plugin->alterView($executable, $override_values);

    // Verify that style options were updated.
    $updated_style_plugin = $executable->getStyle();
    $updated_options = $updated_style_plugin->options;

    $this->assertEquals('custom-grid-class', $updated_options['grid_class']);
    $this->assertEquals('col-md-6', $updated_options['col_md']);
    $this->assertEquals('col-lg-4', $updated_options['col_lg']);

    // Verify that unspecified overrides remain unchanged.
    $this->assertNotEquals('custom-grid-class', $original_options['grid_class'] ?? '');
  }

  /**
   * Tests ViewsBootstrapCards settings override functionality.
   */
  public function testCardsSettingsOverride() {
    // Import test view fixture.
    $this->installViewFromFixture('views.view.test_bootstrap_cards_view.yml');

    $view = View::load('test_bootstrap_cards_view');
    $this->assertNotNull($view, 'Test Bootstrap Cards view was loaded.');

    $executable = $view->getExecutable();
    $executable->execute();

    // Get the original style options.
    $style_plugin = $executable->getStyle();
    $original_options = $style_plugin->options;

    // Test settings override.
    $plugin_manager = \Drupal::service('plugin.manager.viewsreference.setting');
    $plugin = $plugin_manager->createInstance('bootstrap_cards');

    $override_values = [
      'card_group' => TRUE,
      'columns' => 4,
      'row_class_custom' => 'custom-row-class',
      'card_group_class_custom' => 'custom-card-group',
    ];

    // Apply the overrides.
    $plugin->alterView($executable, $override_values);

    // Verify that style options were updated.
    $updated_style_plugin = $executable->getStyle();
    $updated_options = $updated_style_plugin->options;

    $this->assertTrue($updated_options['card_group']);
    $this->assertEquals(4, $updated_options['columns']);
    $this->assertEquals('custom-row-class', $updated_options['row_class_custom']);
    $this->assertEquals('custom-card-group', $updated_options['card_group_class_custom']);

    // Verify that overrides were applied successfully.
    $this->assertNotEquals($original_options['card_group'] ?? FALSE, $updated_options['card_group']);
  }

  /**
   * Tests that plugins only apply to appropriate view styles.
   */
  public function testPluginStyleRestriction() {
    // Import test view fixture.
    $this->installViewFromFixture('views.view.test_bootstrap_grid_view.yml');

    $view = View::load('test_bootstrap_grid_view');
    $executable = $view->getExecutable();

    // Change to a different style plugin (not Bootstrap).
    $executable->setDisplay('default');
    $executable->display_handler->setOption('style', [
      'type' => 'html_list',
      'options' => ['type' => 'ul'],
    ]);

    // Re-initialize the executable to pick up the new style.
    $executable->destroy();
    $executable->setDisplay('default');
    $executable->initStyle();

    $plugin_manager = \Drupal::service('plugin.manager.viewsreference.setting');
    $plugin = $plugin_manager->createInstance('bootstrap_grid');

    // Get original options before applying overrides.
    $original_options = $executable->getStyle()->options;

    $override_values = [
      'grid_class' => 'should-not-apply',
      'col_md' => 'col-md-6',
    ];

    // Apply overrides - should be ignored for non-Bootstrap style.
    $plugin->alterView($executable, $override_values);

    // Verify that options were not changed for non-Bootstrap style.
    $updated_options = $executable->getStyle()->options;
    $this->assertEquals($original_options, $updated_options);

    // Verify the override values are not present.
    $this->assertArrayNotHasKey('grid_class', $updated_options);
    $this->assertArrayNotHasKey('col_md', $updated_options);
  }

  /**
   * Creates content types for testing.
   */
  protected function createContentTypes() {
    // Create page content type.
    NodeType::create([
      'type' => 'page',
      'name' => 'Basic page',
    ])->save();

    // Create article content type.
    NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ])->save();
  }

  /**
   * Creates test content.
   */
  protected function createTestContent() {
    // Create some test page nodes.
    for ($i = 1; $i <= 5; $i++) {
      Node::create([
        'type' => 'page',
        'title' => "Test Page $i",
        'status' => 1,
      ])->save();
    }
  }

  /**
   * Creates a ViewsReference field for testing.
   */
  protected function createViewsReferenceField() {
    // Import field storage and config from fixtures.
    $this->installConfigFromFixture('field.storage.node.field_test_view_reference.yml');
    $this->installConfigFromFixture('field.field.node.article.field_test_view_reference.yml');
  }

  /**
   * Installs a view from a fixture file.
   *
   * @param string $fixture_name
   *   The fixture filename.
   */
  protected function installViewFromFixture($fixture_name) {
    $this->installConfigFromFixture($fixture_name);
  }

  /**
   * Installs config from a fixture file.
   *
   * @param string $fixture_name
   *   The fixture filename.
   */
  protected function installConfigFromFixture($fixture_name) {
    $fixture_path = __DIR__ . '/../../fixtures/' . $fixture_name;
    $this->assertFileExists($fixture_path, "Fixture file $fixture_name exists.");

    $config_data = Yaml::parseFile($fixture_path);
    $config_name = str_replace('.yml', '', $fixture_name);

    \Drupal::configFactory()
      ->getEditable($config_name)
      ->setData($config_data)
      ->save();
  }

}
