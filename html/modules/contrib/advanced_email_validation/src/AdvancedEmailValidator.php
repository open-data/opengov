<?php

namespace Drupal\advanced_email_validation;

use Drupal\Core\Config\ConfigFactoryInterface;
use EmailValidator\EmailValidator;

/**
 * Email validator service.
 */
class AdvancedEmailValidator implements AdvancedEmailValidatorInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs an EmailValidator object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(string $email, array $configOverrides = []): int {
    $moduleConfig = $this->configFactory->get('advanced_email_validation.settings');
    $rules = $moduleConfig->get('rules');
    $domainLists = $moduleConfig->get('domain_lists');
    $defaultConfig = [
      'checkMxRecords' => $rules[self::MX_LOOKUP],
      'checkBannedListedEmail' => $rules[self::BANNED_DOMAIN],
      'checkDisposableEmail' => $rules[self::DISPOSABLE_DOMAIN],
      'checkFreeEmail' => $rules[self::FREE_DOMAIN],
      'bannedList' => $domainLists[self::BANNED_DOMAIN],
      'disposableList' => $domainLists[self::DISPOSABLE_DOMAIN],
      'freeList' => $domainLists[self::FREE_DOMAIN],
      'LocalDisposableOnly' => $moduleConfig->get('local_list_only.' . self::DISPOSABLE_DOMAIN),
      'LocalFreeOnly' => $moduleConfig->get('local_list_only.' . self::FREE_DOMAIN),
    ];

    $validatorConfig = !empty($configOverrides) ? $configOverrides : $defaultConfig;
    $emailValidator = new EmailValidator($validatorConfig);
    $emailValidator->validate($email);
    return $emailValidator->getErrorCode();
  }

  /**
   * {@inheritdoc}
   */
  public function errorMessageFromCode(int $errorCode, array $errorMessages = []): string {
    $moduleConfig = $this->configFactory->get('advanced_email_validation.settings');
    $errorMessages = !empty($errorMessages) ? $errorMessages : $moduleConfig->get('error_messages');

    switch ($errorCode) {
      case EmailValidator::FAIL_BASIC:
        return $errorMessages[self::BASIC];

      case EmailValidator::FAIL_MX_RECORD:
        return $errorMessages[self::MX_LOOKUP];

      case EmailValidator::FAIL_DISPOSABLE_DOMAIN:
        return $errorMessages[self::DISPOSABLE_DOMAIN];

      case EmailValidator::FAIL_FREE_PROVIDER:
        return $errorMessages[self::FREE_DOMAIN];

      case EmailValidator::FAIL_BANNED_DOMAIN:
        return $errorMessages[self::BANNED_DOMAIN];
    }

    return '';
  }

}
