<?php

namespace Drupal\recaptcha_v3\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\recaptcha_v3\ReCaptchaV3ActionInterface;

/**
 * Defines the reCAPTCHA v3 action entity.
 *
 * @ConfigEntityType(
 *   id = "recaptcha_v3_action",
 *   label = @Translation("reCAPTCHA v3 action"),
 *   label_collection = @Translation("reCAPTCHA v3 actions"),
 *   label_singular = @Translation("reCAPTCHA v3 action"),
 *   label_plural = @Translation("reCAPTCHA v3 actions"),
 *   label_count = @PluralTranslation(
 *     singular = "@count reCAPTCHA v3 action",
 *     plural = "@count reCAPTCHA v3 actions",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\recaptcha_v3\ReCaptchaV3ActionListBuilder",
 *     "form" = {
 *       "add" = "Drupal\recaptcha_v3\Form\ReCaptchaV3ActionForm",
 *       "edit" = "Drupal\recaptcha_v3\Form\ReCaptchaV3ActionForm",
 *       "delete" = "Drupal\recaptcha_v3\Form\ReCaptchaV3ActionDeleteForm"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "recaptcha_v3_action",
 *   admin_permission = "administer CAPTCHA settings",
 *   list_cache_tags = {
 *    "rendered"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "threshold",
 *     "challenge",
 *   },
 *   links = {
 *     "add-form" = "/admin/config/people/captcha/recaptcha-v3-actions/add",
 *     "edit-form" = "/admin/config/people/captcha/recaptcha-v3-actions/{recaptcha_v3_action}",
 *     "delete-form" = "/admin/config/people/captcha/recaptcha-v3-actions/{recaptcha_v3_action}/delete",
 *     "collection" = "/admin/config/people/captcha/recaptcha-v3-actions"
 *   }
 * )
 */
class ReCaptchaV3Action extends ConfigEntityBase implements ReCaptchaV3ActionInterface {

  /**
   * The reCAPTCHA v3 action ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The reCAPTCHA v3 action label.
   *
   * @var string
   */
  protected $label;

  /**
   * The reCAPTCHA v3 action threshold.
   *
   * @var float
   */
  protected $threshold = .5;

  /**
   * The reCAPTCHA v3 action fallback challenge.
   *
   * @var string
   */
  protected $challenge = 'default';

  /**
   * {@inheritdoc}
   */
  public function setLabel(string $label) {
    $this->label = $label;
  }

  /**
   * {@inheritdoc}
   */
  public function getThreshold(): float {
    return $this->threshold;
  }

  /**
   * {@inheritdoc}
   */
  public function setThreshold(float $threshold) {
    $this->threshold = $threshold;
  }

  /**
   * {@inheritdoc}
   */
  public function getChallenge(): string {
    return $this->challenge;
  }

  /**
   * {@inheritdoc}
   */
  public function setChallenge(string $challenge) {
    $this->challenge = $challenge;
  }

}
