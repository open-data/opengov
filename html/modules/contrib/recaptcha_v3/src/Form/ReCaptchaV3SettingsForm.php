<?php

namespace Drupal\recaptcha_v3\Form;

use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\ElementInfoManager;
use Drupal\captcha\Service\CaptchaService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure the google reCAPTCHA v3 api and fallback challenge.
 */
class ReCaptchaV3SettingsForm extends ConfigFormBase {

  /**
   * The library discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * The element info manager.
   *
   * @var \Drupal\Core\Render\ElementInfoManager
   */
  protected $elementInfoManager;

  /**
   * The CAPTCHA helper service.
   *
   * @var \Drupal\captcha\Service\CaptchaService
   */
  protected $captchaService;

  /**
   * ReCaptchaV3SettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   *   The typed config manager.
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery
   *   Library discovery service.
   * @param \Drupal\Core\Render\ElementInfoManager $element_info_manager
   *   Element info manager service.
   * @param \Drupal\captcha\Service\CaptchaService $captcha_service
   *   Captcha service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, TypedConfigManagerInterface $typedConfigManager, LibraryDiscoveryInterface $library_discovery, ElementInfoManager $element_info_manager, CaptchaService $captcha_service) {
    parent::__construct($config_factory, $typedConfigManager);
    $this->libraryDiscovery = $library_discovery;
    $this->elementInfoManager = $element_info_manager;
    $this->captchaService = $captcha_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('library.discovery'),
      $container->get('plugin.manager.element_info'),
      $container->get('captcha.helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'recaptcha_v3.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'recaptcha_v3_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('recaptcha_v3.settings');

    $form['site_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site key'),
      '#default_value' => $config->get('site_key'),
      '#maxlength' => 40,
      '#description' => $this->t('The site key given to you when you <a href=":url">register for reCAPTCHA</a>.', [':url' => 'https://www.google.com/recaptcha/admin']),
      '#required' => TRUE,
    ];
    $form['secret_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret key'),
      '#default_value' => $config->get('secret_key'),
      '#maxlength' => 40,
      '#description' => $this->t('The secret key given to you when you <a href=":url">register for reCAPTCHA</a>.', [':url' => 'https://www.google.com/recaptcha/admin']),
      '#required' => TRUE,
    ];
    $form['verify_hostname'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Local domain name validation'),
      '#default_value' => $config->get('verify_hostname'),
      '#description' => $this->t('Checks the hostname on your server when verifying a solution. Enable this validation only, if <em>Verify the origin of reCAPTCHA solutions</em> is unchecked for your key pair. Provides crucial security by verifying requests come from one of your listed domains.'),
    ];

    $challenges = $this->captchaService->getAvailableChallengeTypes(FALSE);
    // Remove recaptcha v3 challenges from the list of available
    // fallback challenges.
    $challenges = array_filter($challenges, static function ($captcha_type) {
      return !(strpos($captcha_type, 'recaptcha_v3') === 0);
    }, ARRAY_FILTER_USE_KEY);

    $form['default_challenge'] = [
      '#type' => 'select',
      '#title' => $this->t('Default fallback challenge type'),
      '#description' => $this->t('Select the default fallback challenge type on verification fail.'),
      '#options' => $challenges,
      '#default_value' => $config->get('default_challenge'),
      '#empty_option' => $this->t('- None -'),
      '#empty_value' => '',
    ];

    $form['error_message'] = [
      '#type' => 'textfield',
      '#size' => 128,
      '#title' => $this->t('Error message'),
      '#description' => $this->t('This message will be displayed to user in case of failed recaptcha v3 verification.'),
      '#default_value' => $config->get('error_message'),
    ];

    $form['cacheable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Cacheable'),
      '#description' => $this->t('Make captcha cacheable: can lead to some validation errors like "unknown CAPTCHA session ID".'),
      '#default_value' => $config->get('cacheable'),
    ];

    $form['library_use_recaptcha_net'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Load library from <em>recaptcha.net</em> instead of <em>google.com</em>'),
      '#description' => $this->t('By default, the recaptcha javascript library is loaded from the google.com domain. Check this if you want to load it from recaptcha.net to prevent cookies from the google.com domain to be available. See <a href=":url" target="_blank">the reCAPTCHA docs</a> for more information.', [
        ':url' => 'https://developers.google.com/recaptcha/docs/faq#does-recaptcha-use-cookies',
      ]),
      '#default_value' => $config->get('library_use_recaptcha_net'),
    ];

    $form['hide_badge'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide badge'),
      '#default_value' => $config->get('hide_badge'),
      '#description' => $this->t('Hide the reCAPTCHA badge. An alternative branding will be shown in the form. Cache clearing required.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->config('recaptcha_v3.settings');

    $is_key_changed = $config->get('site_key') !== $values['site_key'];
    $is_library_changed = $config->get('library_use_recaptcha_net') !== $values['library_use_recaptcha_net'];
    // If site key or recaptcha library URL have been changed,
    // then need to rebuild site libraries and elements.
    if ($is_key_changed || $is_library_changed) {
      $this->libraryDiscovery->clearCachedDefinitions();
      $this->elementInfoManager->clearCachedDefinitions();
    }

    $this->config('recaptcha_v3.settings')
      ->set('site_key', $values['site_key'])
      ->set('secret_key', $values['secret_key'])
      ->set('hide_badge', $values['hide_badge'])
      ->set('verify_hostname', $values['verify_hostname'])
      ->set('default_challenge', $values['default_challenge'])
      ->set('error_message', $values['error_message'])
      ->set('cacheable', $values['cacheable'])
      ->set('library_use_recaptcha_net', $values['library_use_recaptcha_net'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
