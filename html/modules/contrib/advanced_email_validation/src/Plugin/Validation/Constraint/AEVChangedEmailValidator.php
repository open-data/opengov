<?php

namespace Drupal\advanced_email_validation\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\advanced_email_validation\AdvancedEmailValidator;
use EmailValidator\EmailValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the ChangedEmailValidation constraint.
 *
 * May be used for any email or string field on any entity type.
 */
class AEVChangedEmailValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Constructs a new AdvancedEmailValidationValidator object.
   */
  public function __construct(
    protected AdvancedEmailValidator $emailValidator,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('advanced_email_validation.validator'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint): void {

    if ($value->getEntity()->isNew()) {
      return;
    }
    if (!$this->valueChanged($value)) {
      return;
    }

    $result = $this->emailValidator->validate($value->getString());

    if ($result !== EmailValidator::NO_ERROR) {
      $errorMessage = $this->emailValidator->errorMessageFromCode($result);
      if (empty($errorMessage)) {
        $this->context->addViolation($constraint->defaultError);
      }
      $this->context->addViolation($errorMessage);
    }
  }

  /**
   * Determine whether the field being validated has been changed.
   */
  protected function valueChanged(FieldItemListInterface $value): bool {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $value->getEntity();
    $entityType = $entity->getEntityType()->id();
    $entityStorage = $this->entityTypeManager->getStorage($entityType);
    $storedEntity = $entityStorage->load($entity->id());
    $storedField = $storedEntity->get($value->getName());
    $storedValue = $storedField->get(0)->get('value')->getValue();
    return $storedValue !== $value->getString();
  }

}
