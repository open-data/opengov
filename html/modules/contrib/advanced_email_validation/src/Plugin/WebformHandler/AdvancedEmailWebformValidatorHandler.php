<?php

namespace Drupal\advanced_email_validation\Plugin\WebformHandler;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\advanced_email_validation\Helper\EmailValidatorHelper;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Advanced email webform validator handler.
 *
 * @WebformHandler(
 *   id = "advanced_email_webform_validator_handler",
 *   label = @Translation("Advanced email webform validator"),
 *   category = @Translation("Settings"),
 *   description = @Translation("Validate email addresses using advanced, configurable rules."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class AdvancedEmailWebformValidatorHandler extends WebformHandlerBase {

  use StringTranslationTrait;

  /**
   * The current request.
   *
   * @var null|\Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The advanced email validator.
   *
   * @var \Drupal\advanced_email_validation\AdvancedEmailValidatorInterface
   */
  protected $advancedEmailValidator;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->request = $container->get('request_stack')->getCurrentRequest();
    $instance->advancedEmailValidator = $container->get('advanced_email_validation.validator');
    $instance->configFactory = $container->get('config.factory');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $moduleConfig = $this->configFactory->getEditable('advanced_email_validation.settings');
    $rules = $moduleConfig->get('rules');
    $domainLists = $moduleConfig->get('domain_lists');
    $errorMessages = $moduleConfig->get('error_messages');
    return parent::defaultConfiguration() + [
      'override_site_defaults' => FALSE,
      'rules' => $rules,
      'error_messages' => $errorMessages,
      'domain_lists' => $domainLists,
      'emails' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $email_element_options = [];
    $elements = $this->webform->getElementsInitializedAndFlattened();
    foreach ($elements as $key => $element) {
      if (isset($element['#type'])
        && in_array($element['#type'], ['email', 'webform_email_confirm'])) {
        $title = (isset($element['#title'])) ? new FormattableMarkup('@title (@key)', [
          '@title' => $element['#title'],
          '@key' => $key,
        ]) : $key;
        $email_element_options["[webform_submission:values:$key:html_email]"] = $title;
      }
    }
    if ($email_element_options) {
      $form['validate_email'] = [
        '#type' => 'details',
        '#title' => $this->t('Advanced email validation settings'),
        '#open' => TRUE,
      ];
      $validate_options[(string) $this->t('Element')] = $email_element_options;
      $form['validate_email']['emails'] = [
        '#type' => 'select',
        '#title' => $this->t('Select the email field/s to be validated'),
        '#multiple' => TRUE,
        '#options' => $validate_options,
        '#default_value' => $this->configuration['emails'],
        '#required' => TRUE,
      ];
    }
    $form['validate_email']['override_site_defaults'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Override site defaults'),
      '#default_value' => $this->configuration['override_site_defaults'] ?? FALSE,
      '#weight' => 0,
    ];
    $checks = EmailValidatorHelper::getValidationChecks();

    $options = [];
    $weight = 1;

    foreach ($checks as $key => $check) {
      $form['validate_email'][$key] = [
        '#type' => 'fieldset',
        '#title' => $check['settings_title'],
        '#weight' => $weight,
        '#states' => [
          'visible' => [
            ':input[name="settings[rules][' . $key . ']"]' => ['checked' => TRUE],
            ':input[name="settings[override_site_defaults]"]' => ['checked' => TRUE],
          ],
        ],
      ];
      $form['validate_email'][$key]['error_message_' . $key] = [
        '#type' => 'textfield',
        '#title' => $this->t('Error message'),
        '#description' => $check['error_description'] ?? NULL,
        '#size' => 120,
        '#default_value' => $this->configuration['error_messages'][$key] ?? $check['default_error_message'],
        '#states' => [
          'required' => [
            ':input[name="settings[rules][' . $key . ']"]' => ['checked' => TRUE],
          ],
        ],
      ];

      if (array_key_exists('domain_list', $check)) {
        $form['validate_email'][$key]['domain_list_' . $key] = [
          '#type' => 'textarea',
          '#title' => $check['domain_list']['title'],
          '#description' => $check['domain_list']['description'],
          '#default_value' => implode("\r\n", $this->configuration['domain_lists'][$key] ?? []),
          '#placeholder' => 'example.org',
        ];
      }

      $weight++;

      if (array_key_exists('rule_title', $check)) {
        $options[$key] = $check['rule_title'];
      }
    }

    $form['validate_email']['rules'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Ensure user account email <em>domains</em> are:'),
      '#options' => $options,
      '#default_value' => EmailValidatorHelper::convertToCheckboxesOptions($this->configuration['rules'] ?? []),
      // We want this at the top of the form, but we're making use of the
      // preceding loop to build our options.
      '#weight' => 0,
      '#states' => [
        'visible' => [
          ':input[name="settings[override_site_defaults]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form = parent::buildConfigurationForm($form, $form_state);
    return $this->setSettingsParents($form);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    $emails = $form_state->getValue('emails');
    if (empty($emails)) {
      $form_state->setErrorByName('emails', $this->t('Advanced email webform validator handler can be used only if the form has an email element added to the form.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $checks = EmailValidatorHelper::getValidationChecks();
    foreach ($checks as $key => $check) {
      $errorMessages[$key] = $form_state->getValue('validate_email')[$key]['error_message_' . $key];

      if (array_key_exists('domain_list', $check)) {
        $domainList = explode("\r\n", $form_state->getValue('validate_email')[$key]['domain_list_' . $key]);
        foreach ($domainList as &$domain) {
          $domain = trim($domain);
        }
        $domainLists[$key] = $domainList;
      }
    }
    $form_state->unsetValue('validate_email');
    if (!empty($errorMessages)) {
      $form_state->setValue('error_messages', $errorMessages);
    }
    if (!empty($domainLists)) {
      $form_state->setValue('domain_lists', $domainLists);
    }
    $this->applyFormStateToConfiguration($form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $this->validateEmail($form_state);
    parent::validateForm($form, $form_state, $webform_submission);
  }

  /**
   * Validate email fields.
   */
  private function validateEmail(FormStateInterface $formState): void {
    $webformConfig = $this->configuration;
    if (!$emails = $webformConfig['emails']) {
      return;
    }
    $override_site_defaults = $webformConfig['override_site_defaults'] ?? FALSE;
    $configOverrides = [];
    $errorMessages = [];
    if ($override_site_defaults) {
      $rules = $webformConfig['rules'] ?? [];
      $domainLists = $webformConfig['domain_lists'] ?? [];
      $errorMessages = $webformConfig['error_messages'] ?? [];
      $configOverrides = [
        'checkMxRecords' => $rules[$this->advancedEmailValidator::MX_LOOKUP],
        'checkBannedListedEmail' => $rules[$this->advancedEmailValidator::BANNED_DOMAIN],
        'checkDisposableEmail' => $rules[$this->advancedEmailValidator::DISPOSABLE_DOMAIN],
        'checkFreeEmail' => $rules[$this->advancedEmailValidator::FREE_DOMAIN],
        'bannedList' => $domainLists[$this->advancedEmailValidator::BANNED_DOMAIN],
        'disposableList' => $domainLists[$this->advancedEmailValidator::DISPOSABLE_DOMAIN],
        'freeList' => $domainLists[$this->advancedEmailValidator::FREE_DOMAIN],
      ];
    }
    foreach ($emails as $emailField) {
      $emailField = str_replace('[webform_submission:values:', '', $emailField);
      $emailField = str_replace(':html_email]', '', $emailField);
      if (!$email = $formState->getValue($emailField)) {
        continue;
      }
      if (!$result = $this->advancedEmailValidator->validate($email, $configOverrides)) {
        continue;
      }
      $errorMessage = $this->advancedEmailValidator->errorMessageFromCode($result, $errorMessages);
      if (!empty($errorMessage)) {
        $formState->setErrorByName($emailField, $errorMessage);
      }
    }
  }

}
