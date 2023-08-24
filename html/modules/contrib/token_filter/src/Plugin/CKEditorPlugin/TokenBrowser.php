<?php

namespace Drupal\token_filter\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;
use Drupal\token\Token;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the "tokenbrowser" plugin.
 *
 * NOTE: The plugin ID ('id' key) corresponds to the CKEditor plugin name.
 * It is the first argument of the CKEDITOR.plugins.add() function in the
 * plugin.js file.
 *
 * @CKEditorPlugin(
 *   id = "tokenbrowser",
 *   label = @Translation("Token browser")
 * )
 */
class TokenBrowser extends CKEditorPluginBase implements ContainerFactoryPluginInterface, CKEditorPluginConfigurableInterface {

  /**
   * The CSRF token manager service.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfTokenService;

  /**
   * The token service.
   *
   * @var \Drupal\token\Token
   */
  protected $tokenService;

  /**
   * The file URL generator service.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * The module extension list service.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * {@inheritdoc}
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrf_token_service
   *   Generates and validates CSRF tokens.
   * @param \Drupal\token\Token $token_service
   *   Returns the token service.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   Generates file URLs for a stream to an external or local file.
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_extension_list
   *   Provides available extensions.
   */
  public function __construct(
    array $configuration,
                              $plugin_id,
                              $plugin_definition,
    CsrfTokenGenerator $csrf_token_service,
    Token $token_service,
    FileUrlGeneratorInterface $file_url_generator,
    ModuleExtensionList $module_extension_list) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->csrfTokenService = $csrf_token_service;
    $this->tokenService = $token_service;
    $this->fileUrlGenerator = $file_url_generator;
    $this->moduleExtensionList = $module_extension_list;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('csrf_token'),
      $container->get('token'),
      $container->get('file_url_generator'),
      $container->get('extension.list.module'),
    );
  }

  /**
   * {@inheritdoc}
   *
   * NOTE: The keys of the returned array corresponds to the CKEditor button
   * names. They are the first argument of the editor.ui.addButton() or
   * editor.ui.addRichCombo() functions in the plugin.js file.
   */
  public function getButtons($token_types = NULL) {
    return [
      'tokenbrowser' => [
        'id' => 'tokenbrowser',
        'label' => $this->t('Token browser'),
        'image' => $this->fileUrlGenerator->generateAbsoluteString($this->getImage()),
        'link' => $this->getUrl($token_types)->toString(),
      ],
    ];
  }

  /**
   * Fetches the URL.
   *
   * @return \Drupal\Core\Url
   *   The URL.
   *
   * @see TokenTreeController::outputTree()
   */
  protected function getUrl($token_types = NULL): Url {
    $url = Url::fromRoute('token.tree');
    $options['query'] = [
      'options' => Json::encode($this->getQueryOptions($token_types)),
      'token' => $this->csrfTokenService->get($url->getInternalPath()),
    ];
    $url->setOptions($options);
    return $url;
  }

  /**
   * Fetches the list of query options.
   *
   * @return array
   *   The list of query options.
   *
   * @see TreeBuilderInterface::buildRenderable()
   *   for option definitions.
   */
  protected function getQueryOptions($token_types = NULL): array {
    return [
      'token_types' => $token_types ?: 'all',
      'global_types' => FALSE,
      'click_insert' => TRUE,
      'show_restricted' => FALSE,
      'show_nested' => FALSE,
      'recursion_limit' => 3,
    ];
  }

  /**
   * Fetches the path to the image.
   *
   * Make sure that the path to the image matches the file structure of the
   * CKEditor plugin you are implementing.
   *
   * @return string
   *   The string representation of the path to the image.
   */
  protected function getImage(): string {
    return $this->getModulePath('token_filter') . '/js/plugins/tokenbrowser/tokenbrowser.png';
  }

  /**
   * {@inheritdoc}
   *
   * Make sure that the path to the plugin.js matches the file structure of the
   * CKEditor plugin you are implementing.
   */
  public function getFile(): string {
    return $this->getModulePath('token_filter') . '/js/plugins/tokenbrowser/plugin.js';
  }

  /**
   * Fetches the path to this module.
   *
   * @return string
   *   The string representation of the module's path.
   */
  protected function getModulePath(string $module_name): string {
    return $this->moduleExtensionList->getPath($module_name);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor): array {

    // Get settings.
    $token_types = NULL;
    $settings = $editor->getSettings();
    if (isset($settings['plugins']['tokenbrowser'], $settings['plugins']['tokenbrowser']['token_types'])) {
      $token_types = $settings['plugins']['tokenbrowser']['token_types'];
    }

    return [
      'TokenBrowser_buttons' => $this->getButtons($token_types),
      'token_types' => $token_types,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor): array {

    // Get config.
    $config = $this->getConfig($editor);

    // Get parent token type names, keyed by machine name.
    $parent_token_types = array_filter($this->tokenService
      ->getInfo()['types'], function ($v) {
        return empty($v['nested']);
      });
    $parent_token_types = array_map(function ($v) {
      return $v['name'];
    }, $parent_token_types);

    // Add multiselect for token types to show in browser.
    $form['token_types'] = [
      '#type' => 'select',
      '#title' => $this->t('Token types'),
      '#description' => $this->t('Optionally restrict the token types to show in the browser. Select none to show all.'),
      '#multiple' => TRUE,
      '#options' => $parent_token_types,
      '#default_value' => $config['token_types'],
    ];

    return $form;
  }

}
