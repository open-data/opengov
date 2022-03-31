<?php

namespace Drupal\webform;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\webform\Form\WebformDialogFormTrait;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a webform add form.
 */
class WebformEntityAddForm extends BundleEntityFormBase {

  use WebformDialogFormTrait;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a WebformEntityAddForm.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(ModuleHandlerInterface $module_handler, LanguageManagerInterface $language_manager) {
    $this->moduleHandler = $module_handler;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntity() {
    if ($this->operation === 'duplicate') {
      $this->setEntity($this->getEntity()->createDuplicate());
    }
    parent::prepareEntity();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();

    // Customize title for duplicate webform.
    if ($this->operation === 'duplicate') {
      // Display custom title.
      $form['#title'] = $this->t("Duplicate '@label' form", ['@label' => $webform->label()]);
    }

    $form = parent::buildForm($form, $form_state);

    return $this->buildDialogForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $webform->id(),
      '#machine_name' => [
        'exists' => '\Drupal\webform\Entity\Webform::load',
        'source' => ['title'],
        'label' => '<br/>' . $this->t('Machine name'),

      ],
      '#maxlength' => 32,
      '#field_suffix' => ' (' . $this->t('Maximum @max characters', ['@max' => 32]) . ')',
      '#disabled' => (bool) ($webform->id() && $this->operation !== 'duplicate'),
      '#required' => TRUE,
    ];
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#maxlength' => 255,
      '#default_value' => $webform->label(),
      '#required' => TRUE,
      '#id' => 'title',
      '#attributes' => [
        'autofocus' => 'autofocus',
      ],
    ];
    $form['description'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Administrative description'),
      '#default_value' => $webform->get('description'),
    ];
    /** @var \Drupal\webform\WebformEntityStorageInterface $webform_storage */
    $webform_storage = $this->entityTypeManager->getStorage('webform');
    $form['category'] = [
      '#type' => 'webform_select_other',
      '#title' => $this->t('Category'),
      '#options' => $webform_storage->getCategories(),
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $webform->get('category'),
    ];
    $form['status'] = [
      '#type' => 'radios',
      '#title' => $this->t('Status'),
      '#default_value' => $webform->get('status'),
      '#options' => [
        WebformInterface::STATUS_OPEN => $this->t('Open'),
        WebformInterface::STATUS_CLOSED => $this->t('Closed'),
      ],
      '#options_display' => 'side_by_side',
    ];

    $form = $this->protectBundleIdElement($form);

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    if ($this->operation === 'duplicate') {
      $original_id = \Drupal::routeMatch()->getRawParameter('webform');
      $duplicate_id = $this->getEntity()->id();

      // Copy translations.
      if ($this->moduleHandler->moduleExists('config_translation')) {
        $original_name = 'webform.webform.' . $original_id;
        $duplicate_name = 'webform.webform.' . $duplicate_id;
        $current_langcode = $this->languageManager->getConfigOverrideLanguage()->getId();
        $languages = $this->languageManager->getLanguages();
        foreach ($languages as $language) {
          $langcode = $language->getId();
          if ($langcode !== $current_langcode) {
            $original_translation = $this->languageManager->getLanguageConfigOverride($langcode, $original_name)->get();
            if ($original_translation) {
              $duplicate_translation = $this->languageManager->getLanguageConfigOverride($langcode, $duplicate_name);
              $duplicate_translation->setData($original_translation);
              $duplicate_translation->save();
            }
          }
        }
      }

      // Copy webform export and results from state.
      $state = \Drupal::state()->get("webform.webform.$original_id");
      // Remove node (source entity) keys.
      unset($state['results.export.node'], $state['results.custom.node']);
      if ($state) {
        \Drupal::state()->set("webform.webform.$duplicate_id", $state);
      }
    }

    $form_state->setRedirectUrl(Url::fromRoute('entity.webform.edit_form', ['webform' => $this->getEntity()->id()]));
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();

    $webform->save();

    $context = [
      '@label' => $webform->label(),
      'link' => $webform->toLink($this->t('Edit'), 'settings')->toString(),
    ];
    $t_args = ['%label' => $webform->label()];
    $this->logger('webform')->notice('Webform @label created.', $context);
    $this->messenger()->addStatus($this->t('Webform %label created.', $t_args));
  }

}
