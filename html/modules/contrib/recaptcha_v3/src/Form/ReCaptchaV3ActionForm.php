<?php

namespace Drupal\recaptcha_v3\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\captcha\Service\CaptchaService;
use Drupal\recaptcha_v3\Entity\ReCaptchaV3Action;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the recaptcha_v3_action entity edit forms.
 *
 * @internal
 */
class ReCaptchaV3ActionForm extends EntityForm {

  /**
   * The CAPTCHA helper service.
   *
   * @var \Drupal\captcha\Service\CaptchaService
   */
  protected $captchaService;

  /**
   * Constructs a ReCaptchaV3ActionForm.
   *
   * @param \Drupal\captcha\Service\CaptchaService $captcha_service
   *   Captcha service.
   */
  public function __construct(CaptchaService $captcha_service) {
    $this->captchaService = $captcha_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('captcha.helper'));
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $recaptcha_v3_action = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $recaptcha_v3_action->label(),
      '#description' => $this->t('Label for the reCAPTCHA v3 action.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $recaptcha_v3_action->id(),
      '#required' => TRUE,
      '#machine_name' => [
        'exists' => [ReCaptchaV3Action::class, 'load'],
      ],
      '#disabled' => !$recaptcha_v3_action->isNew(),
    ];

    $form['threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Threshold'),
      '#min' => 0,
      '#max' => 1,
      '#step' => 0.1,
      '#required' => TRUE,
      '#default_value' => $recaptcha_v3_action->getThreshold(),
      '#description' => $this->t('The threshold score value. See <a href=":url" target="_blank">the scores interpretation</a> documentation for more information.', [
        ':url' => 'https://developers.google.com/recaptcha/docs/v3#interpreting_the_score',
      ]),
    ];

    // @todo the same code lines using in several other places
    // need to refactor this.
    // Maybe create method in recaptcha v3 action storage?
    $challenges = $this->captchaService->getAvailableChallengeTypes(FALSE);
    // Remove recaptcha v3 challenges from the list of available
    // fallback challenges.
    $challenges = array_filter($challenges, static function ($captcha_type) {
      return !(strpos($captcha_type, 'recaptcha_v3') === 0);
    }, ARRAY_FILTER_USE_KEY);
    $challenges = ['default' => $this->t('Default fallback challenge')] + $challenges;

    $form['challenge'] = [
      '#type' => 'select',
      '#title' => $this->t('Fallback challenge'),
      '#description' => $this->t('Select the fallback challenge on reCAPTCHA v3 user validation fail.'),
      '#options' => $challenges,
      '#default_value' => $recaptcha_v3_action->getChallenge(),
      '#empty_option' => $this->t('- None -'),
      '#empty_value' => '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $label = $this->entity->label();
    $saved_state = parent::save($form, $form_state);
    switch ($saved_state) {
      case SAVED_NEW:
        $this->messenger()
          ->addStatus($this->t('Created the %label reCAPTCHA v3 action.', ['%label' => $label]));
        $this->getLogger('recaptcha_v3')
          ->info('Created the %label reCAPTCHA v3 action.', ['%label' => $label]);
        break;

      default:
        $this->messenger()
          ->addStatus($this->t('Saved the %label reCAPTCHA v3 action.', ['%label' => $label]));
        $this->getLogger('recaptcha_v3')
          ->info('Saved the %label reCAPTCHA v3 action.', ['%label' => $label]);
    }

    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $saved_state;
  }

}
