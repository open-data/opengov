<?php

namespace Drupal\advanced_email_validation\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\advanced_email_validation\Helper\EmailValidatorHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Email Validation settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The entity_type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->setEntityTypeManager($container->get('entity_type.manager'));
    return $instance;
  }

  /**
   * Set the entity_type manager service.
   */
  protected function setEntityTypeManager(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'advanced_email_validation_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['advanced_email_validation.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('advanced_email_validation.settings');

    $form['validate_account_on'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Validate user account mail field on:'),
      '#options' => [
        'created' => $this->t('Registration'),
        'updated' => $this->t('Email change'),
      ],
      '#default_value' => EmailValidatorHelper::convertToCheckboxesOptions($config->get('validate_account_on') ?? []),
      '#weight' => 0,
    ];

    /** @var array $checks */
    $checks = EmailValidatorHelper::getValidationChecks();
    $options = [];
    $weight = 2;

    foreach ($checks as $key => $check) {
      $form[$key] = [
        '#type' => 'fieldset',
        '#title' => $check['settings_title'],
        '#weight' => $weight,
        '#states' => [
          'visible' => [
            ':input[name="rules[' . $key . ']"]' => ['checked' => TRUE],
          ],
        ],
        $key . '_error_message' => [
          '#type' => 'textfield',
          '#title' => $this->t('Error message'),
          '#description' => $check['error_description'] ?? NULL,
          '#size' => 120,
          '#default_value' => $config->get('error_messages.' . $key) ?? $check['default_error_message'],
          '#states' => [
            'required' => [
              ':input[name="rules[' . $key . ']"]' => ['checked' => TRUE],
            ],
          ],
        ],
      ];

      if (array_key_exists('domain_list', $check)) {
        $form[$key][$key . '_domain_list'] = [
          '#type' => 'textarea',
          '#title' => $check['domain_list']['title'],
          '#description' => $check['domain_list']['description'],
          '#default_value' => implode("\r\n", $config->get('domain_lists.' . $key) ?? []),
          '#placeholder' => 'example.org',
        ];
      }

      if (array_key_exists('local_list_only', $check)) {
        $form[$key][$key . '_local_list_only'] = [
          '#type' => 'checkbox',
          '#title' => $check['local_list_only']['title'],
          '#description' => $check['local_list_only']['description'],
          '#default_value' => $config->get('local_list_only.' . $key),
        ];
      }

      $weight++;

      if (array_key_exists('rule_title', $check)) {
        $options[$key] = $check['rule_title'];
      }
    }

    $form['rules'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Ensure user account email <em>domains</em> are:'),
      '#options' => $options,
      '#default_value' => EmailValidatorHelper::convertToCheckboxesOptions($config->get('rules') ?? []),
      // We want this near the top of the form, but we're making use of the
      // preceding loop to build our options.
      '#weight' => 1,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $checks = EmailValidatorHelper::getValidationChecks();
    foreach (array_keys($checks) as $key) {
      $element = $key . '_domain_list';
      $value = $form_state->getValue($element);
      if ($value && preg_match('/@+/', $value)) {
        $form_state->setErrorByName($element, $this->t('Use domain names only (the part after "@" in an email address)'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $checks = EmailValidatorHelper::getValidationChecks();
    $config = $this->config('advanced_email_validation.settings');

    $config->set('validate_account_on', $form_state->getValue('validate_account_on'));
    $config->set('rules', $form_state->getValue('rules'));

    foreach ($checks as $key => $check) {
      $errorMessage = $form_state->getValue($key . '_error_message');
      $config->set('error_messages.' . $key, $errorMessage);

      if (array_key_exists('domain_list', $check)) {
        $domainList = explode("\r\n", $form_state->getValue($key . '_domain_list'));
        foreach ($domainList as &$domain) {
          $domain = trim($domain);
        }
        $config->set('domain_lists.' . $key, $domainList);
      }

      if (array_key_exists('local_list_only', $check)) {
        $localList = (bool) $form_state->getValue($key . '_local_list_only');
        $config->set('local_list_only.' . $key, $localList);
      }
    }

    $config->save();

    // Clear entity definition cache so the base info alter hook is reapplied.
    // @todo This cache clear might be too broad, we probably can clear a more
    // specific cache here.
    $this->entityTypeManager->clearCachedDefinitions();

    parent::submitForm($form, $form_state);
  }

}
