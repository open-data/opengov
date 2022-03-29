<?php

namespace Drupal\simple_sitemap\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\simple_sitemap\Entity\EntityHelper;
use Drupal\simple_sitemap\Entity\SimpleSitemap;
use Drupal\simple_sitemap\Manager\Generator;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\simple_sitemap\Settings;

/**
 * Simple XML Sitemap form helper.
 */
class FormHelper {

  use StringTranslationTrait;

  protected const PRIORITY_HIGHEST = 10;
  protected const PRIORITY_DIVIDER = 10;

  /**
   * The sitemap generator service.
   *
   * @var \Drupal\simple_sitemap\Manager\Generator
   */
  protected $generator;

  /**
   * Helper class for working with entities.
   *
   * @var \Drupal\simple_sitemap\Entity\EntityHelper
   */
  protected $entityHelper;

  /**
   * Proxy for the current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The form state.
   *
   * @var \Drupal\Core\Form\FormStateInterface
   */
  protected $formState;

  /**
   * Form entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The entity category.
   *
   * @var string|null
   */
  protected $entityCategory;

  /**
   * The entity type ID.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The bundle of the entity.
   *
   * @var string
   */
  protected $bundleName;

  /**
   * The entity identifier.
   *
   * @var string
   */
  protected $instanceId;

  /**
   * The entity bundle settings.
   *
   * @var array
   */
  protected $bundleSettings;

  /**
   * The simple_sitemap.settings service.
   *
   * @var \Drupal\simple_sitemap\Settings
   */
  protected $settings;

  /**
   * Allowed form operations.
   *
   * @var array
   */
  protected static $allowedFormOperations = [
    'default',
    'edit',
    'add',
    'register',
  ];

  /**
   * Change frequency values.
   *
   * @var array
   */
  protected static $changefreqValues = [
    'always',
    'hourly',
    'daily',
    'weekly',
    'monthly',
    'yearly',
    'never',
  ];

  /**
   * Cron intervals.
   *
   * @var int[]
   */
  protected static $cronIntervals = [
    1,
    3,
    6,
    12,
    24,
    48,
    72,
    96,
    120,
    144,
    168,
  ];

  /**
   * FormHelper constructor.
   *
   * @param \Drupal\simple_sitemap\Manager\Generator $generator
   *   The sitemap generator service.
   * @param \Drupal\simple_sitemap\Settings $settings
   *   The simple_sitemap.settings service.
   * @param \Drupal\simple_sitemap\Entity\EntityHelper $entityHelper
   *   Helper class for working with entities.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   Proxy for the current user account.
   */
  public function __construct(
    Generator $generator,
    Settings $settings,
    EntityHelper $entityHelper,
    AccountProxyInterface $current_user
  ) {
    $this->generator = $generator;
    $this->settings = $settings;
    $this->entityHelper = $entityHelper;
    $this->currentUser = $current_user;
  }

  /**
   * Processes the specified form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state to process.
   *
   * @return bool
   *   Whether the processed form is supported.
   */
  public function processForm(FormStateInterface $form_state): bool {
    $this->formState = $form_state;
    $this->cleanUpFormInfo();

    return $this->userAccess()
      && $this->getFormEntity()
      && $this->getFormEntityData()
      && $this->generator->entityManager()
        ->entityTypeIsEnabled($this->getEntityTypeId());
  }

  /**
   * Determine if the current user has access to administer sitemap settings.
   *
   * @return bool
   *   Returns whether the user has access to administer sitemap settings.
   */
  protected function userAccess(): bool {
    return $this->currentUser->hasPermission('administer sitemap settings');
  }

  /**
   * Sets the entity category.
   *
   * @param string|null $entity_category
   *   The entity category.
   *
   * @return $this
   */
  public function setEntityCategory(?string $entity_category): FormHelper {
    $this->entityCategory = $entity_category;

    return $this;
  }

  /**
   * Gets the form entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The form entity.
   */
  public function getEntity(): ?EntityInterface {
    return $this->entity;
  }

  /**
   * Gets the entity category.
   *
   * @return null|string
   *   The entity category.
   */
  public function getEntityCategory(): ?string {
    return $this->entityCategory;
  }

  /**
   * Sets the entity type ID.
   *
   * @param string|null $entity_type_id
   *   The entity type ID.
   *
   * @return $this
   */
  public function setEntityTypeId(?string $entity_type_id): FormHelper {
    $this->entityTypeId = $entity_type_id;

    return $this;
  }

  /**
   * Gets the entity type ID.
   *
   * @return string
   *   The entity type ID.
   */
  public function getEntityTypeId(): ?string {
    return $this->entityTypeId;
  }

  /**
   * Sets the bundle of the entity.
   *
   * @param string|null $bundle_name
   *   The bundle of the entity.
   *
   * @return $this
   */
  public function setBundleName(?string $bundle_name): FormHelper {
    $this->bundleName = $bundle_name;

    return $this;
  }

  /**
   * Gets the bundle of the entity.
   *
   * @return string
   *   The bundle of the entity.
   */
  public function getBundleName(): ?string {
    return $this->bundleName;
  }

  /**
   * Sets the entity identifier.
   *
   * @param string|null $instance_id
   *   The entity identifier.
   *
   * @return $this
   */
  public function setInstanceId(?string $instance_id): FormHelper {
    $this->instanceId = $instance_id;

    return $this;
  }

  /**
   * Gets the entity identifier.
   *
   * @return string
   *   The entity identifier.
   */
  public function getInstanceId(): ?string {
    return $this->instanceId;
  }

  /**
   * Determines whether the entity is new.
   *
   * @return bool
   *   TRUE if the entity is new, or FALSE if the entity has already been saved.
   */
  public function entityIsNew(): bool {
    return $this->entity === NULL || $this->entity->isNew();
  }

  /**
   * Displays the 'Regenerate all sitemaps' checkbox.
   *
   * @param array $form_fragment
   *   The form fragment.
   *
   * @return $this
   */
  public function displayRegenerateNow(array &$form_fragment): FormHelper {
    $form_fragment['simple_sitemap_regenerate_now'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Regenerate all sitemaps after hitting <em>Save</em>'),
      '#description' => $this->t('This setting will regenerate all sitemaps including the above changes.'),
      '#default_value' => FALSE,
      '#tree' => FALSE,
    ];
    if ($this->settings->get('cron_generate')) {
      $form_fragment['simple_sitemap_regenerate_now']['#description'] .= '<br>' . $this->t('Otherwise the sitemaps will be regenerated during a future cron run.');
    }

    return $this;
  }

  /**
   * Gathers sitemap settings for set entity.
   *
   * @return $this
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getEntitySitemapSettings(): FormHelper {
    $this->bundleSettings = $this->generator
      ->setVariants()
      ->entityManager()
      ->getBundleSettings($this->getEntityTypeId(), $this->getBundleName());

    if ($this->getEntityCategory() === 'instance') {
      // @todo Simplify after getEntityInstanceSettings() works with multiple variants.
      foreach ($this->bundleSettings as $variant => $settings) {
        if (NULL !== $instance_id = $this->getInstanceId()) {
          $this->bundleSettings[$variant] = $this->generator
            ->setVariants($variant)
            ->entityManager()
            ->getEntityInstanceSettings($this->getEntityTypeId(), $instance_id)[$variant];
        }
        $this->bundleSettings[$variant]['bundle_settings'] = $settings;
      }
    }

    return $this;
  }

  /**
   * Displays the entity settings.
   *
   * @param array $form_fragment
   *   The form fragment.
   *
   * @return $this
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @todo Refactor.
   */
  public function displayEntitySettings(array &$form_fragment): FormHelper {

    $bundle_name = !empty($this->getBundleName())
      ? $this->entityHelper->getBundleLabel($this->getEntityTypeId(), $this->getBundleName())
      : $this->t('undefined');

    $sitemaps = SimpleSitemap::loadMultiple();
    $form_fragment['#markup'] = empty($sitemaps)
      ? $this->t('At least one sitemap needs to be defined for a bundle to be indexable.<br>Sitemaps can be configured <a href="@url">here</a>.', ['@url' => $GLOBALS['base_url'] . '/admin/config/search/simplesitemap'])
      : '<strong>' . $this->t('Sitemaps') . '</strong>';

    $this->getEntitySitemapSettings();
    foreach ($sitemaps as $variant => $sitemap) {
      $form_fragment[$variant] = [
        '#type' => 'details',
        '#title' => '<em>' . $sitemap->label() . '</em>',
        '#open' => !empty($this->bundleSettings[$variant]['index']),
        '#after_build' => [[self::class, 'displayEntitySettingsAfterBuild']],
      ];

      // Disable fields of entity instance whose bundle is not indexed.
      $form_fragment[$variant]['#disabled'] = $this->getEntityCategory() === 'instance' && empty($this->bundleSettings[$variant]['bundle_settings']['index']);

      // Index.
      $form_fragment[$variant]['index'] = [
        '#type' => 'radios',
        '#default_value' => (int) $this->bundleSettings[$variant]['index'],
        '#options' => [
          $this->getEntityCategory() === 'instance'
          ? $this->t('Do not index this <em>@bundle</em> entity in sitemap <em>@sitemap_label</em>', [
            '@bundle' => $bundle_name,
            '@sitemap_label' => $sitemap->label(),
          ])
          : $this->t('Do not index entities of type <em>@bundle</em> in sitemap <em>@sitemap_label</em>', [
            '@bundle' => $bundle_name,
            '@sitemap_label' => $sitemap->label(),
          ]),
          $this->getEntityCategory() === 'instance'
          ? $this->t('Index this <em>@bundle entity</em> in sitemap <em>@sitemap_label</em>', [
            '@bundle' => $bundle_name,
            '@sitemap_label' => $sitemap->label(),
          ])
          : $this->t('Index entities of type <em>@bundle</em> in sitemap <em>@sitemap_label</em>', [
            '@bundle' => $bundle_name,
            '@sitemap_label' => $sitemap->label(),
          ]),
        ],
        '#attributes' => [
          'data-simple-sitemap-label' => $sitemap->label(),
        ],
      ];

      if ($this->getEntityCategory() === 'instance' && isset($this->bundleSettings[$variant]['bundle_settings']['index'])) {
        $form_fragment[$variant]['index']['#options'][(int) $this->bundleSettings[$variant]['bundle_settings']['index']] .= ' <em>(' . $this->t('default') . ')</em>';
      }

      // Priority.
      $form_fragment[$variant]['priority'] = [
        '#type' => 'select',
        '#title' => $this->t('Priority'),
        '#description' => $this->getEntityCategory() === 'instance'
        ? $this->t('The priority this <em>@bundle</em> entity will have in the eyes of search engine bots.', ['@bundle' => $bundle_name])
        : $this->t('The priority entities of this type will have in the eyes of search engine bots.'),
        '#default_value' => $this->bundleSettings[$variant]['priority'],
        '#options' => $this->getPrioritySelectValues(),
      ];

      if ($this->getEntityCategory() === 'instance' && isset($this->bundleSettings[$variant]['bundle_settings']['priority'])) {
        $form_fragment[$variant]['priority']['#options'][$this->formatPriority($this->bundleSettings[$variant]['bundle_settings']['priority'])] .= ' (' . $this->t('default') . ')';
      }

      // Changefreq.
      $form_fragment[$variant]['changefreq'] = [
        '#type' => 'select',
        '#title' => $this->t('Change frequency'),
        '#description' => $this->getEntityCategory() === 'instance'
        ? $this->t('The frequency with which this <em>@bundle</em> entity changes. Search engine bots may take this as an indication of how often to index it.', ['@bundle' => $bundle_name])
        : $this->t('The frequency with which entities of this type change. Search engine bots may take this as an indication of how often to index them.'),
        '#default_value' => $this->bundleSettings[$variant]['changefreq'] ?? NULL,
        '#options' => $this->getChangefreqSelectValues(),
      ];

      if ($this->getEntityCategory() === 'instance' && isset($this->bundleSettings[$variant]['bundle_settings']['changefreq'])) {
        $form_fragment[$variant]['changefreq']['#options'][$this->bundleSettings[$variant]['bundle_settings']['changefreq']] .= ' (' . $this->t('default') . ')';
      }

      // Images.
      $form_fragment[$variant]['include_images'] = [
        '#type' => 'select',
        '#title' => $this->t('Include images'),
        '#description' => $this->getEntityCategory() === 'instance'
        ? $this->t('Determines if images referenced by this <em>@bundle</em> entity should be included in the sitemap.', ['@bundle' => $bundle_name])
        : $this->t('Determines if images referenced by entities of this type should be included in the sitemap.'),
        '#default_value' => isset($this->bundleSettings[$variant]['include_images']) ? (int) $this->bundleSettings[$variant]['include_images'] : 0,
        '#options' => [$this->t('No'), $this->t('Yes')],
      ];

      if ($this->getEntityCategory() === 'instance' && isset($this->bundleSettings[$variant]['bundle_settings']['include_images'])) {
        $form_fragment[$variant]['include_images']['#options'][(int) $this->bundleSettings[$variant]['bundle_settings']['include_images']] .= ' (' . $this->t('default') . ')';
      }
    }

    return $this;
  }

  /**
   * After-build callback to set the correct #states.
   *
   * @param array $element
   *   The element structure.
   *
   * @return array
   *   The element structure.
   */
  public static function displayEntitySettingsAfterBuild(array $element): array {
    $selector = ':input[name="' . $element['index']['#name'] . '"]';

    foreach (['priority', 'changefreq', 'include_images'] as $key) {
      $element[$key]['#states'] = [
        'visible' => [$selector => ['value' => 1]],
      ];
    }

    return $element;
  }

  /**
   * Gathers info about the entity.
   *
   * @return bool
   *   TRUE if this is a bundle or bundle instance form, FALSE otherwise.
   */
  protected function getFormEntityData(): bool {
    $entity_type_id = $this->entity->getEntityTypeId();
    $sitemap_entity_types = $this->entityHelper->getSupportedEntityTypes();
    if (isset($sitemap_entity_types[$entity_type_id])) {
      $this->setEntityCategory('instance');
    }
    else {
      /** @var \Drupal\Core\Entity\EntityType $sitemap_entity_type */
      foreach ($sitemap_entity_types as $sitemap_entity_type) {
        if ($sitemap_entity_type->getBundleEntityType() === $entity_type_id) {
          $this->setEntityCategory('bundle');
          break;
        }
      }
    }

    // Menu fix.
    $this->setEntityCategory(
      NULL === $this->getEntityCategory() && $entity_type_id === 'menu'
        ? 'bundle'
        : $this->getEntityCategory()
    );

    switch ($this->getEntityCategory()) {
      case 'bundle':
        $this->setEntityTypeId($this->entityHelper->getBundleEntityTypeId($this->entity));
        $this->setBundleName($this->entity->id());
        $this->setInstanceId(NULL);
        return TRUE;

      case 'instance':
        $this->setEntityTypeId($entity_type_id);
        $this->setBundleName($this->entityHelper->getEntityInstanceBundleName($this->entity));
        // New menu link's id is '' instead of NULL, hence checking for empty.
        $this->setInstanceId(!$this->entityIsNew() ? $this->entity->id() : NULL);
        return TRUE;

      default:
        return FALSE;
    }
  }

  /**
   * Sets the object entity of the form if available.
   */
  protected function getFormEntity(): bool {
    $form_object = $this->formState->getFormObject();
    if (NULL !== $form_object
      && method_exists($form_object, 'getOperation')
      && method_exists($form_object, 'getEntity')
      && in_array($form_object->getOperation(), static::$allowedFormOperations, TRUE)) {
      $this->entity = $form_object->getEntity();
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Removes gathered form information from service object.
   *
   * Needed because this service may contain form info from the previous
   * operation when revived from the container.
   *
   * @return $this
   */
  public function cleanUpFormInfo(): FormHelper {
    $this->entity = NULL;
    $this->entityCategory = NULL;
    $this->entityTypeId = NULL;
    $this->bundleName = NULL;
    $this->instanceId = NULL;
    $this->bundleSettings = NULL;

    return $this;
  }

  /**
   * Gets the values needed to display the priority dropdown setting.
   *
   * @return array
   *   The values for the priority dropdown setting.
   */
  public function getPrioritySelectValues(): array {
    $options = [];
    foreach (range(0, self::PRIORITY_HIGHEST) as $value) {
      $value = $this->formatPriority($value / self::PRIORITY_DIVIDER);
      $options[$value] = $value;
    }

    return $options;
  }

  /**
   * Gets the values needed to display the changefreq dropdown setting.
   *
   * @return array
   *   The values for the changefreq dropdown setting.
   */
  public function getChangefreqSelectValues(): array {
    $options = ['' => $this->t('- Not specified -')];
    foreach (self::getChangefreqOptions() as $setting) {
      $options[$setting] = $this->t($setting);
    }

    return $options;
  }

  /**
   * Gets the change frequency values.
   *
   * @return array
   *   Change frequency values.
   */
  public static function getChangefreqOptions(): array {
    return self::$changefreqValues;
  }

  /**
   * Formats the given priority.
   *
   * @param string $priority
   *   The priority to format.
   *
   * @return string
   *   The formatted priority.
   */
  public function formatPriority(string $priority): string {
    return number_format((float) $priority, 1, '.', '');
  }

  /**
   * Validates the priority.
   *
   * @param string|int $priority
   *   The priority value.
   *
   * @return bool
   *   TRUE if the priority is valid.
   */
  public static function isValidPriority(string $priority): bool {
    return is_numeric($priority) && $priority >= 0 && $priority <= 1;
  }

  /**
   * Validates the change frequency.
   *
   * @param string $changefreq
   *   The change frequency value.
   *
   * @return bool
   *   TRUE if the change frequency is valid.
   */
  public static function isValidChangefreq(string $changefreq): bool {
    return in_array($changefreq, self::$changefreqValues, TRUE);
  }

  /**
   * Gets the cron intervals.
   *
   * @return array
   *   Cron intervals.
   */
  public static function getCronIntervalOptions(): array {
    /** @var \Drupal\Core\Datetime\DateFormatter $formatter */
    $formatter = \Drupal::service('date.formatter');
    $intervals = array_flip(self::$cronIntervals);
    foreach ($intervals as $interval => &$label) {
      $label = $formatter->formatInterval($interval * 60 * 60);
    }

    return [0 => t('On every cron run')] + $intervals;
  }

  /**
   * Gets the donation text.
   *
   * @return string
   *   The donation text.
   */
  public static function getDonationText(): string {
    return '<div class="description">' . t('If you would like to say thanks and support the development of this module, a <a target="_blank" href="@url">donation</a> will be much appreciated.', ['@url' => 'https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=5AFYRSBLGSC3W']) . '</div>';
  }

  /**
   * Adds a submit handler to a form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param callable $callback
   *   The submit handler.
   */
  public static function addSubmitHandler(array &$form, callable $callback) {
    if (isset($form['actions']['submit']['#submit'])) {
      foreach (array_keys($form['actions']) as $action) {
        if ($action !== 'preview'
          && isset($form['actions'][$action]['#type'])
          && $form['actions'][$action]['#type'] === 'submit') {
          $form['actions'][$action]['#submit'][] = $callback;
        }
      }
    }
    // Fix for account page rendering other submit handlers not usable.
    else {
      $form['#submit'][] = $callback;
    }
  }

}
