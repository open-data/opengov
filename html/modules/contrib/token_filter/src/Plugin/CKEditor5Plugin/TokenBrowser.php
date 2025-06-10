<?php

namespace Drupal\token_filter\Plugin\CKEditor5Plugin;

use Drupal\ckeditor5\Plugin\CKEditor5Plugin\DynamicPluginConfigWithCsrfTokenUrlTrait;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableInterface;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableTrait;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\Token;
use Drupal\editor\EditorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Token browser for CKEditor5.
 */
class TokenBrowser extends CKEditor5PluginDefault implements CKEditor5PluginConfigurableInterface, ContainerFactoryPluginInterface {

  use CKEditor5PluginConfigurableTrait;

  use DynamicPluginConfigWithCsrfTokenUrlTrait;

  /**
   * Token service.
   */
  protected Token $tokenService;

  /**
   * CSRF Token generator class.
   */
  protected CsrfTokenGenerator $csrfTokenGenerator;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    CKEditor5PluginDefinition $plugin_definition,
    Token $token_service,
    CsrfTokenGenerator $csrf_token_generator,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->tokenService = $token_service;
    $this->csrfTokenGenerator = $csrf_token_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): TokenBrowser {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('token'),
      $container->get('csrf_token'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDynamicPluginConfig(array $static_plugin_config, EditorInterface $editor): array {
    $config = $static_plugin_config;
    $config += [
      'drupalTokenBrowser' => [
        'url' => $this->getUrl($this->getConfiguration()['token_types'])->toString(),
      ],
    ];

    return $config;
  }

  /**
   * {@inheritDoc}
   */
  public function defaultConfiguration() {
    return [
      'token_types' => [],
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Get config.
    $config = $this->getConfiguration();

    // Get parent token type names, keyed by machine name.
    $parent_token_types = array_filter(
      $this->tokenService->getInfo()['types'],
      static fn($v) => empty($v['nested']),
    );
    $parent_token_types = array_map(
      static fn($v) => $v['name'],
      $parent_token_types
    );

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

  /**
   * {@inheritDoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
  }

  /**
   * {@inheritDoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['token_types'] = $form_state->getValue('token_types');
  }

  /**
   * Fetches the URL.
   *
   * @return \Drupal\Core\Url
   *   The URL.
   *
   * @see \Drupal\token\Controller\TokenTreeController::outputTree()
   */
  protected function getUrl($token_types = NULL) {
    $url = Url::fromRoute('token.tree');
    $options['query'] = [
      'options' => Json::encode($this->getQueryOptions($token_types)),
      'token' => $this->csrfTokenGenerator->get($url->getInternalPath()),
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
   * @see \Drupal\token\TreeBuilderInterface::buildRenderable()
   */
  protected function getQueryOptions($token_types = NULL) {
    return [
      'token_types' => $token_types ?: 'all',
      'global_types' => FALSE,
      'click_insert' => TRUE,
      'show_restricted' => FALSE,
      'show_nested' => FALSE,
      'recursion_limit' => 3,
    ];
  }

}
