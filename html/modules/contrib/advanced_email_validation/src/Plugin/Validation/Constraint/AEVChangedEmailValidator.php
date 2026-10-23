<?php

namespace Drupal\advanced_email_validation\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\advanced_email_validation\AdvancedEmailValidator;
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

    if ($value->isEmpty()) {
      return;
    }
    if ($value->getEntity()->isNew()) {
      return;
    }

    $email = $value->getString();
    $storedValue = $this->getStoredValue($value);
    if (!$storedValue->isEmpty()) {
      $storedEmail = $storedValue->getString();
      if ($storedEmail === $email) {
        return;
      }
    }

    $result = $this->emailValidator->validateEmail($email);
    if (!$result->isValid()) {
      $errorMessage = $result->message;
      if (empty($errorMessage)) {
        $this->context->addViolation($constraint->defaultError);
        return;
      }
      $this->context->addViolation($errorMessage);
    }
  }

  /**
   * Get the stored value of the field.
   */
  protected function getStoredValue(FieldItemListInterface $value): FieldItemListInterface {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $value->getEntity();
    $entityType = $entity->getEntityType()->id();
    $entityStorage = $this->entityTypeManager->getStorage($entityType);
    // Load the persisted entity, bypassing the static cache, so we compare
    // against the stored email rather than the in-memory (already modified)
    // value of the entity currently being validated.
    $storedEntity = $entityStorage->loadUnchanged($entity->id());
    return $storedEntity->get($value->getName());
  }

}
