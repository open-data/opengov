<?php

namespace Drupal\advanced_email_validation\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\advanced_email_validation\AdvancedEmailValidator;
use EmailValidator\EmailValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the NewEmailValidation constraint.
 */
class AEVNewEmailValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Constructs a new AdvancedEmailValidationValidator object.
   */
  public function __construct(protected AdvancedEmailValidator $emailValidator) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('advanced_email_validation.validator'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint): void {
    $isNew = $value->getParent()->getEntity()->isNew();

    if (!$isNew) {
      return;
    }

    $email = $value->getString();
    if (empty($email)) {
      return;
    }

    $result = $this->emailValidator->validate($email);

    if ($result !== EmailValidator::NO_ERROR) {
      $errorMessage = $this->emailValidator->errorMessageFromCode($result);
      if (empty($errorMessage)) {
        $this->context->addViolation($constraint->defaultError);
      }
      $this->context->addViolation($errorMessage);
    }
  }

}
