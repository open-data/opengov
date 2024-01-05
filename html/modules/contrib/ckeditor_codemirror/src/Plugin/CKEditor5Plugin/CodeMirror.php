<?php

namespace Drupal\ckeditor_codemirror\Plugin\CKEditor5Plugin;

use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableInterface;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableTrait;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\editor\EditorInterface;

/**
 * Defines the CodeMirror code editor plugin.
 *
 * @CKEditor5Plugin(
 *   id = "ckeditor_codemirror_source_editing",
 *   ckeditor5 = @CKEditor5AspectsOfCKEditor5Plugin(
 *     plugins = {"sourceEditingCodemirror.SourceEditingCodeMirror"}
 *   ),
 *   drupal = @DrupalAspectsOfCKEditor5Plugin(
 *     label = @Translation("CodeMirror source editing"),
 *     library = "ckeditor_codemirror/source_editing_code_mirror",
 *     admin_library = "ckeditor_codemirror/admin",
 *     elements = false,
 *   )
 * )
 */
class CodeMirror extends CKEditor5PluginDefault implements CKEditor5PluginConfigurableInterface {

  use CKEditor5PluginConfigurableTrait;
  use StringTranslationTrait;

  /**
   * Additional settings options.
   *
   * @return array
   *   An array of settings options and their descriptions.
   */
  private function options(): array {
    return [
      'autoCloseBrackets' => $this->t('Close brackets automatically.'),
      'autoCloseTags' => $this->t('Close tags automatically.'),
      'folding' => $this->t('Enable code folding.'),
      'lineNumbers' => $this->t('Show line numbers.'),
      'lineWrapping' => $this->t('Enable line wrapping.'),
      'matchBrackets' => $this->t('Highlight matching brackets.'),
      'matchTags' => $this->t('Highlight matching tags.'),
      'searchBottom' => $this->t('Display search bar at bottom.'),
      'styleActiveLine' => $this->t('Highlight active line.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'enable' => FALSE,
      'mode' => 'htmlmixed',
      'options' => array_fill_keys(array_keys($this->options()), TRUE),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDynamicPluginConfig(array $static_plugin_config, EditorInterface $editor): array {
    $options = $this->configuration['options'];
    $options['mode'] = $this->configuration['mode'];
    $options['gutters'] = [];
    $options['extraKeys'] = ['Alt-F' => 'findPersistent'];

    if ($this->configuration['options']['lineNumbers']) {
      $options['gutters'][] = 'CodeMirror-linenumbers';
    }

    if ($this->configuration['options']['folding']) {
      $options['foldGutter'] = TRUE;
      $options['gutters'][] = 'CodeMirror-foldgutter';
    }
    unset($options['folding']);

    if ($this->configuration['options']['searchBottom']) {
      $options['search'] = ['bottom' => TRUE];
    }
    unset($options['searchBottom']);

    return ['sourceEditingCodeMirror' => ['options' => $options]];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $settings = $this->getConfiguration();

    $form['enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable CodeMirror source view syntax highlighting.'),
      '#default_value' => $settings['enable'] ?? FALSE,
    ];

    $form['mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Mode'),
      '#options' => [
        'htmlmixed' => $this->t('HTML (including css, xml and javascript)'),
        'text/html' => $this->t('HTML only'),
        'application/x-httpd-php' => $this->t('PHP (including HTML)'),
        'text/javascript' => $this->t('Javascript only'),
        'css' => $this->t('CSS'),
        'text/x-scss' => $this->t('SCSS'),
      ],
      '#default_value' => $settings['mode'] ?? 'htmlmixed',
    ];

    $form['options'] = [
      '#type' => 'details',
      '#title' => $this->t('Additional settings'),
      '#description' => $this->t('Source highlighting and code formatting options:'),
      '#open' => TRUE,
    ];

    foreach ($this->options() as $setting => $description) {
      $form['options'][$setting] = [
        '#type' => 'checkbox',
        '#title' => $description,
        '#default_value' => $settings['options'][$setting] ?? TRUE,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Match the config schema structure ckeditor5.plugin.ckeditor_codemirror.
    $form_values = $form_state->getValues();
    $form_state->setValue('enable', (bool) $form_values['enable']);
    foreach ($form_values['options'] as $option => $form_value) {
      $form_state->setValue($option, (bool) $form_value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['enable'] = $form_state->getValue('enable');
    $this->configuration['mode'] = $form_state->getValue('mode');
    $options = [];
    foreach (array_keys($this->options()) as $option) {
      $options[$option] = $form_state->getValue($option);
    }
    $this->configuration['options'] = $options;
  }

}
