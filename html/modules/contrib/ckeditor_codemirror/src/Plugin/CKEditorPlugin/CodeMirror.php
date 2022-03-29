<?php

namespace Drupal\ckeditor_codemirror\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\ckeditor\CKEditorPluginContextualInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\editor\Entity\Editor;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the "CodeMirror" plugin.
 *
 * @CKEditorPlugin(
 *   id = "codemirror",
 *   label = @Translation("CodeMirror"),
 *   module = "ckeditor_codemirror"
 * )
 */
class CodeMirror extends CKEditorPluginBase implements CKEditorPluginConfigurableInterface, CKEditorPluginContextualInterface, ContainerFactoryPluginInterface {

  /**
   * File system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FileSystemInterface $file_system) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return _ckeditor_codemirror_get_library_path() . '/codemirror/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function isInternal() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(Editor $editor) {
    $settings = $editor->getSettings();

    if (isset($settings['plugins']['codemirror'])) {
      return $editor->getSettings()['plugins']['codemirror']['enable'];
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    $settings = $editor->getSettings()['plugins']['codemirror'];

    $config = [
      'codemirror' => [
        'enable' => isset($settings['enable']) ? $settings['enable'] : FALSE,
        'mode' => isset($settings['mode']) ? $settings['mode'] : 'htmlmixed',
        'theme' => isset($settings['theme']) ? $settings['theme'] : 'default',
      ],
      'startupMode' => isset($settings['startupMode'])
      ? $settings['startupMode'] : 'wysiwyg',
    ];

    foreach ($this->options() as $option => $description) {
      $config['codemirror'][$option] = isset($settings['options'][$option])
        ? $settings['options'][$option] : TRUE;
    }

    return $config;
  }

  /**
   * Additional settings options.
   *
   * @return array
   *   An array of settings options and their descriptions.
   */
  private function options() {
    return [
      'lineNumbers' => $this->t('Show line numbers.'),
      'lineWrapping' => $this->t('Enable line wrapping.'),
      'matchBrackets' => $this->t('Highlight matching brackets.'),
      'autoCloseTags' => $this->t('Close tags automatically.'),
      'autoCloseBrackets' => $this->t('Close brackets automatically.'),
      'enableSearchTools' => $this->t('Enable search tools.'),
      'enableCodeFolding' => $this->t('Enable code folding.'),
      'enableCodeFormatting' => $this->t('Enable code formatting.'),
      'autoFormatOnStart' => $this->t('Format code on start.'),
      'autoFormatOnModeChange' => $this->t('Format code each time source is opened.'),
      'autoFormatOnUncomment' => $this->t('Format code when a line is uncommented.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    $editor_settings = $editor->getSettings();
    if (isset($editor_settings['plugins']['codemirror'])) {
      $settings = $editor_settings['plugins']['codemirror'];
    }

    $form['#attached']['library'][] = 'ckeditor_codemirror/ckeditor_codemirror.admin';

    $form['enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable CodeMirror source view syntax highlighting.'),
      '#default_value' => isset($settings['enable'])
      ? $settings['enable'] : FALSE,
    ];

    $form['startupMode'] = [
      '#type' => 'select',
      '#title' => $this->t('Editor startup Mode'),
      '#options' => [
        'wysiwyg' => $this->t('WYSIWYG (default)'),
        'source' => $this->t('Source'),
      ],
      '#default_value' => isset($settings['startupMode'])
      ? $settings['startupMode'] : 'wysiwyg',
    ];

    $form['mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Mode'),
      '#options' => [
        'htmlmixed' => $this->t('HTML (including css, xml and javascript)'),
        'text/html' => $this->t('HTML only'),
        'application/x-httpd-php' => $this->t('PHP (including HTML)'),
        'text/javascript' => $this->t('Javascript only'),
      ],
      '#default_value' => isset($settings['mode'])
      ? $settings['mode'] : 'htmlmixed',
    ];

    $theme_options = ['default' => 'default'];
    $themes_directory = _ckeditor_codemirror_get_library_path()
      . '/codemirror/theme';
    if (is_dir($themes_directory)) {
      $theme_css_files = $this->fileSystem->scanDirectory($themes_directory, '/\.css/i');
      foreach ($theme_css_files as $file) {
        $theme_options[$file->name] = $file->name;
      }
    }

    $form['theme'] = [
      '#type' => 'select',
      '#title' => $this->t('Theme'),
      '#options' => $theme_options,
      '#default_value' => isset($settings['theme'])
      ? $settings['theme'] : 'default',
    ];

    $form['options'] = [
      '#type' => 'details',
      '#title' => t('Additional settings'),
      '#description' => t('Source highlighting and code formatting options:'),
      '#open' => FALSE,
    ];

    foreach ($this->options() as $setting => $description) {
      $form['options'][$setting] = [
        '#type' => 'checkbox',
        '#title' => $description,
        '#default_value' => isset($settings['options'][$setting]) ? $settings['options'][$setting] : TRUE,
      ];
    }

    return $form;
  }

}
