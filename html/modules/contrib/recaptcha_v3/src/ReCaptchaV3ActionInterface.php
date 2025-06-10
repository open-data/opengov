<?php

namespace Drupal\recaptcha_v3;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a recaptcha v3 entity..
 */
interface ReCaptchaV3ActionInterface extends ConfigEntityInterface {

  /**
   * Setter for label.
   *
   * @param string $label
   *   Label of action.
   */
  public function setLabel(string $label);

  /**
   * Getter for threshold.
   *
   * @return float
   *   Get threshold value.
   */
  public function getThreshold(): float;

  /**
   * Setter for threshold.
   *
   * @param float $threshold
   *   Set threshold value.
   */
  public function setThreshold(float $threshold);

  /**
   * Getter for challenge.
   *
   * @return string
   *   Challenge type.
   */
  public function getChallenge(): string;

  /**
   * Setter for challenge.
   *
   * @param string $challenge
   *   Set challenge type.
   */
  public function setChallenge(string $challenge);

}
