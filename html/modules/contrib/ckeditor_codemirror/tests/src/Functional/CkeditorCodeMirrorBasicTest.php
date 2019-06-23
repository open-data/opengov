<?php

namespace Drupal\Tests\ckeditor_codemirror\Functional;

use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\BrowserTestBase;

/**
 * Test enabling the module and adding source highlighting to a text format.
 *
 * @ingroup ckeditor_codemirror
 *
 * @group ckeditor_codemirror
 */
class CkeditorCodeMirrorBasicTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'filter',
    'node',
    'editor',
    'ckeditor',
    'ckeditor_codemirror',
  ];

  /**
   * {@inheritdoc}
   */
  protected $profile = 'minimal';

  /**
   * The user for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $privilegedUser;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create text format, associate CKEditor.
    $full_html_format = FilterFormat::create([
      'format' => 'full_html',
      'name' => 'Full HTML',
      'weight' => 0,
      'filters' => [],
    ]);
    $full_html_format->save();
    $editor = Editor::create([
      'format' => 'full_html',
      'editor' => 'ckeditor',
    ]);
    $editor->save();

    // Create node type.
    $this->drupalCreateContentType([
      'type' => 'article',
      'name' => 'Article',
    ]);

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
    module_load_include('install', 'ckeditor_codemirror');

    $this->drupalLogin($this->privilegedUser);
    $this->drupalGet('admin/reports/status');

    $library_path = _ckeditor_codemirror_get_library_path();
    if (file_exists(DRUPAL_ROOT . '/' . $library_path . '/codemirror/plugin.js')) {
      $this->assertSession()->responseContains(
        t('CKEditor CodeMirror plugin version %version installed at %path.',
          [
            '%path' => base_path() . $library_path,
            '%version' => _ckeditor_codemirror_get_version(),
          ])
      );
    }
    else {
      $this->assertSession()->pageTextContains(
        t('CKEditor CodeMirror plugin was not found.')
      );
    }
  }

  /**
   * Enable CKEditor CodeMirror plugin.
   */
  public function testEnableCkeditorCodeMirrorPlguin() {
    $this->drupalLogin($this->privilegedUser);
    $this->drupalGet('admin/config/content/formats/manage/full_html');
    $this->assertSession()->pageTextContains(
      t('Enable CodeMirror source view syntax highlighting.')
    );
    $this->assertSession()->checkboxNotChecked(
      'edit-editor-settings-plugins-codemirror-enable'
    );

    // Enable the plugin.
    $edit = [
      'editor[settings][plugins][codemirror][enable]' => '1',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $this->assertSession()->pageTextContains(
      t('The text format Full HTML has been updated.')
    );

    // Check for the plugin on node add page.
    $this->drupalGet('node/add/article');
    $editor_settings = $this->getDrupalSettings()['editor']['formats']['full_html']['editorSettings'];

    $library_path = _ckeditor_codemirror_get_library_path();
    if (file_exists(DRUPAL_ROOT . '/' . $library_path . '/codemirror/plugin.js')) {
      $ckeditor_enabled = $editor_settings['codemirror']['enable'];
      $this->assertTrue($ckeditor_enabled);
    }
  }

}
