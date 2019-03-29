<?php

namespace Drupal\merge_translations\Form;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The merge translation form.
 */
class mergeTranslationsForm extends FormBase {

  const DONOTHING = '_none';
  const REMOVE = 'remove';
  const AUTO = '_auto';
  const ENTITYTYPE = 'node';

  /**
   * @var EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * @var RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * @var \Drupal\Core\Entity\EntityInterface|null
   */
  protected $node;

  /**
   * @var LanguageManager
   */
  protected $languages;

  /**
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * mergeTranslationsForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   * @param \Drupal\Core\Language\LanguageManager $languages
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManager $entityTypeManager, RouteMatchInterface $routeMatch, LanguageManager $languages, MessengerInterface $messenger) {
    $this->entityTypeManager = $entityTypeManager;
    $this->routeMatch = $routeMatch;
    $this->languages = $languages;
    $this->messenger = $messenger;

    $this->node = $entityTypeManager->getStorage(self::ENTITYTYPE)->load($routeMatch->getParameter(self::ENTITYTYPE));
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return \Drupal\Core\Form\FormBase|\Drupal\merge_translations\Form\mergeTranslationsForm
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
      $container->get('language_manager'),
      $container->get('messenger')
    );
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'merge_translations_form';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $isTranslationImportAvailable = $this->isTranslationImportAvailable();

    $form['node_translations'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Language'),
        $this->t('Translation'),
        $this->t('Status')
      ],
      '#rows' => [],
    ];
    foreach ($this->languages->getLanguages() as $key => $language) {
      $language_name = $language->getName();
      $source = [
        '#type' => 'entity_autocomplete',
        '#target_type' => 'node',
        '#selection_settings' => [
          'target_bundles' => [$this->node->getType()],
        ],
        '#disabled' => $isTranslationImportAvailable,
        '#maxlength' => 255,
      ];
      $status = '-';

      if ($this->node->getTranslationStatus($key)) {
        $translation = $this->node->getTranslation($key);
        $source = [
          '#markup' => $this->t('<a href="@href">@title</a>', [
            '@href' => $translation->toUrl()->toString(),
            '@title' => $translation->getTitle(),
          ]),
        ];
        $status = $translation->isPublished() ? $this->t('Published') : $this->t('Not published');

        if ($translation->isDefaultTranslation()) {
          $language_name = $this->t('<b>@language (Original language)</b>', ['@language' => $language_name]);
        }
      }
      $form['node_translations'][$key]['language_name'] = [
        '#markup' => $language_name,
      ];
      $form['node_translations'][$key]['node_source'] = $source;
      $form['node_translations'][$key]['status'] = [
        '#markup' => $status,
      ];
    }

    $form['node_source_action'] = [
      '#type' => 'radios',
      '#title' => $this->t('Action with source node after import'),
      '#description' => $this->t('You can remove or do nothing with source node after import translations'),
      '#options' => [
        self::DONOTHING => $this->t('Do nothing'),
        self::REMOVE => $this->t('Remove node'),
      ],
      '#default_value' => self::DONOTHING,
      '#disabled' => $isTranslationImportAvailable,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import translations'),
      '#disabled' => $isTranslationImportAvailable,
    ];

    return $form;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $translations = $form_state->getValue('node_translations');
    $action = $form_state->getValue('node_source_action');

    foreach ($translations as $langcode => $source) {
      if (empty($source['node_source']) || (($entity = $this->entityTypeManager->getStorage(self::ENTITYTYPE)->load($source['node_source'])) === NULL)) {
        continue;
      }
      // Add translation.
      $this->mergeTranslations($entity, $langcode);

      if (self::REMOVE === $action) {
        $this->removeNode($entity);
      }
    }

    $this->node->save();
  }

  /**
   * Validate the submitted values.
   *
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $translations = $form_state->getValue('node_translations');
    foreach ($translations as $langcode => $source) {
      if (empty($source['node_source'])) {
        continue;
      }
      if ($this->node->id() === $source['node_source']) {
        $form_state->setErrorByName("{$langcode}][node_source", $this->t('Translation source and target can not be the same'));
      }
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * @param NodeInterface $node_source
   * @return \Exception
   */
  private function removeNode(NodeInterface $node_source) {
    try {
      $this->messenger->addStatus($this->t('Node @node has been removed.', ['@node' => $node_source->getTitle()]));
      $node_source->delete();

      return TRUE;
    }
    catch (\Exception $e) {
      return $e;
    }
  }

  /**
   * @param NodeInterface $node_source
   * @param $langcode
   */
  private function mergeTranslations(NodeInterface $node_source, $langcode) {
    $languages = $this->languages->getLanguages();

    if ($langcode != self::AUTO) {
      $this->addTranslation($langcode, $node_source->toArray());
    }
    else {

      foreach ($languages as $key => $language) {
        if ($node_source->hasTranslation($key)) {
          $this->addTranslation($key, $node_source->getTranslation($key)->toArray());
        }
      }
    }
  }

  /**
   * @param $langcode
   * @param array $node_array
   * @return bool
   */
  private function addTranslation($langcode, array $node_array) {
    \Drupal::moduleHandler()->invokeAll('merge_translations_prepare_alter', [&$node_array]);

    $node_target = $this->node;
    $message_argumens = [
      '@langcode' => $langcode,
      '@title' => $node_target->getTitle(),
    ];

    if (!$node_target->hasTranslation($langcode)) {
      $node_target->addTranslation($langcode, $node_array);
      $this->messenger->addStatus($this->t('Add @langcode translation to node @title.', $message_argumens));
      return TRUE;
    }

    $this->messenger->addWarning($this->t('Translation @langcode already exist in node @title.', $message_argumens));

    return FALSE;
  }

  /**
   * Check if translation import is possible.
   *
   * @return bool
   */
  private function isTranslationImportAvailable() {
    $languages = $this->languages->getLanguages();

    if (!$this->node->isTranslatable()) {
      $this->messenger->addWarning(
        $this->t('Translation for this content type is disabled now. Go to <a href="@link">Settings page</a>.',
          ['@link' => '/admin/structure/types/manage/' . $this->node->getType() . '#edit-language'])
      );

      return TRUE;
    }

    foreach ($languages as $key => $language) {
      if (!$this->node->getTranslationStatus($key)) {
        return FALSE;
      }
    }

    return TRUE;
  }

}
