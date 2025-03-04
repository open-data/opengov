<?php

namespace Drupal\recaptcha_v3;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\captcha\Service\CaptchaService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of reCAPTCHA v3 action entities.
 */
class ReCaptchaV3ActionListBuilder extends ConfigEntityListBuilder {

  /**
   * Recaptcha v3 challenge types.
   *
   * @var array
   *    An array of recaptcha v3 challenge types.
   */
  protected $challengeTypes;

  /**
   * The captcha helper service.
   *
   * @var \Drupal\captcha\Service\CaptchaService
   */
  protected $captchaHelper;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new ReCaptchaV3ActionListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The action storage.
   * @param \Drupal\captcha\Service\CaptchaService $captcha_service
   *   The captcha helper service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, CaptchaService $captcha_service, ConfigFactoryInterface $config_factory) {
    parent::__construct($entity_type, $storage);

    $this->captchaHelper = $captcha_service;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('captcha.helper'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['id'] = $this->t('Action');
    $header['threshold'] = $this->t('Threshold');
    $header['challenge'] = $this->t('Fail challenge');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\recaptcha_v3\ReCaptchaV3ActionInterface $entity */
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['threshold'] = $entity->getThreshold();
    $challenge_type = $entity->getChallenge();
    $row['challenge'] = $this->getCaptchaChallengeTypes()[$challenge_type] ?? $this->t('Not defined');
    return $row + parent::buildRow($entity);
  }

  /**
   * Gets reCaptcha v3 challenge types.
   *
   * @return array
   *   All reCaptcha v3 challenge types.
   */
  protected function getCaptchaChallengeTypes() {
    if ($this->challengeTypes === NULL) {
      $this->challengeTypes = $this->captchaHelper->getAvailableChallengeTypes(FALSE);
      $this->challengeTypes = array_filter($this->challengeTypes, static function ($captcha_type) {
        return !(strpos($captcha_type, 'recaptcha_v3') === 0);
      }, ARRAY_FILTER_USE_KEY);
      $default = $this->configFactory->get('recaptcha_v3.settings')->get('default_challenge');
      $this->challengeTypes['default'] = $this->challengeTypes[$default] ?? $this->t('Default');
    }
    return $this->challengeTypes;
  }

}
