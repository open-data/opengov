<?php

namespace Drupal\recaptcha_v3\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a confirmation form for deleting reCAPTCHA v3 action entities.
 *
 * @internal
 */
class ReCaptchaV3ActionDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $action = $this->entityTypeManager->getStorage('captcha_point')
      ->getQuery()
      ->accessCheck()
      ->condition('captchaType', 'recaptcha_v3/' . $this->entity->id())
      ->execute();
    if (!empty($action)) {
      $caption = '<p>' . $this->formatPlural(
          count($action),
          '%label is used by 1 captcha point form on your site. You can not remove %label until you have removed it from %formId captcha points form.',
          '%label is used by @count captcha point forms on your site. You may not remove %label until you have removed %label from %formId.',
          [
            '%label' => $this->entity->label(),
            '%formId' => implode(", ", $action),
          ]
        ) . '</p>';
      $form['description'] = ['#markup' => $caption];
    }
    else {
      $form = parent::buildForm($form, $form_state);
    }

    return $form;
  }

}
