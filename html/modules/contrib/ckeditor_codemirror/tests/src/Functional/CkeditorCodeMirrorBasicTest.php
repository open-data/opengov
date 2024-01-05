<?php

namespace Drupal\Tests\ckeditor_codemirror\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\UserInterface;

/**
 * Test enabling the module and adding source highlighting to a text format.
 *
 * @ingroup ckeditor_codemirror
 *
 * @group ckeditor_codemirror
 */
class CkeditorCodeMirrorBasicTest extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'filter',
    'node',
    'editor',
    'ckeditor5',
    'ckeditor_codemirror',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The user for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $privilegedUser;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Create text format, associate CKEditor 5.
    $full_html_format = FilterFormat::create([
      'format' => 'full_html',
      'name' => 'Full HTML',
      'weight' => 0,
      'filters' => [],
    ]);
    $full_html_format->save();
    $editor = Editor::create([
      'format' => 'full_html',
      'editor' => 'ckeditor5',
    ]);
    $editor->save();

    // Create node type.
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    $this->privilegedUser = $this->drupalCreateUser([
      'administer site configuration',
      'administer filters',
      'create article content',
      'edit any article content',
      'use text format full_html',
    ]);
    $this->drupalLogin($this->privilegedUser);
  }

  /**
   * Check the library status on "Status report" page.
   */
  public function testCheckStatusReportPage() {
    $this->container->get('module_handler')->loadInclude('install', 'ckeditor_codemirror');

    $this->drupalLogin($this->privilegedUser);
    $this->drupalGet('admin/reports/status');

    $library_path = _ckeditor_codemirror_get_library_path('codemirror');
    if (file_exists("$library_path/package.json")) {
      $this->assertSession()->responseContains(
        $this->t('CodeMirror version %version installed at %path.',
          [
            '%path' => base_path() . $library_path,
            '%version' => _ckeditor_codemirror_get_library_version('codemirror'),
          ])
      );
    }
    else {
      $this->assertSession()->pageTextContains($this->t('CodeMirror was not found.'));
    }
  }

  /**
   * Enable CKEditor CodeMirror plugin.
   */
  public function testEnableCkeditorCodeMirrorPlugin() {
    $this->drupalLogin($this->privilegedUser);
    $this->drupalGet('admin/config/content/formats/manage/full_html');
    $this->assertSession()->pageTextContains($this->t('Enable CodeMirror source view syntax highlighting.'));
    $this->assertSession()->checkboxNotChecked('edit-editor-settings-plugins-ckeditor-codemirror-source-editing-enable');

    // Enable the plugin.
    $edit = ['editor[settings][plugins][ckeditor_codemirror_source_editing][enable]' => '1'];
    $this->submitForm($edit, $this->t('Save configuration'));
    $this->assertSession()->pageTextContains($this->t('The text format Full HTML has been updated.'));

    // Check for the plugin on node add page.
    $this->drupalGet('node/add/article');
    $editor_settings = $this->getDrupalSettings()['editor']['formats']['full_html']['editorSettings'];

    // Ensure the plugin is loaded.
    $this->assertContains('sourceEditingCodemirror.SourceEditingCodeMirror', $editor_settings['plugins']);

    // Ensure the plugin is enabled.
    $library_path = _ckeditor_codemirror_get_library_path('codemirror');
    if (file_exists("$library_path/package.json")) {
      $ckeditor_enabled = $editor_settings['config']['sourceEditingCodemirror.SourceEditingCodeMirror']['enable'];
      $this->assertTrue($ckeditor_enabled);
    }
  }

}
