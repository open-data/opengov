<?php

namespace Drupal\fontawesome\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Implementation of Font Awesome icon formatter.
 *
 * @FieldFormatter(
 *   id = "fontawesome_icon_formatter",
 *   label = @Translation("Font Awesome Icon"),
 *   field_types = {
 *     "fontawesome_icon"
 *   }
 * )
 */
class FontAwesomeIconFormatter extends FormatterBase implements ContainerFactoryPluginInterface {
  /**
   * Drupal configuration service container.
   *
   * @var Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, ConfigFactory $config_factory) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    // Load the configuration settings.
    $configuration_settings = $this->configFactory->get('fontawesome.settings');

    // Setting for optional download link.
    $elements['layers'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display multi-value fields as layers?'),
      '#default_value' => $this->getSetting('layers'),
      '#description' => $this->t('Layers are the new way to place icons and text visually on top of each other, replacing the Font Awesome classic icons stacks. With this new approach you can use more than 2 icons. Layers are awesome when you don’t want your page’s background to show through, or when you do want to use multiple colors, layer several icons, layer text, or layer counters onto an icon. Note that layers only work with the SVG version of Font Awesome. For more information, see @layersLink.', [
        '@layersLink' => Link::fromTextAndUrl($this->t('the Font Awesome guide to layers'), Url::fromUri('https://fontawesome.com/how-to-use/on-the-web/styling/layering'))->toString(),
      ]),
      // Disable power transforms for webfonts.
      '#disabled' => $configuration_settings->get('method') == 'webfonts',
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $settings = $this->getSettings();

    $summary = [];

    // Load the configuration settings.
    $configuration_settings = $this->configFactory->get('fontawesome.settings');

    // Show whether or not we are layering the icons.
    $summary[] = $this->t('Display multi-value fields as layers: <strong>@layersSetting</strong>', [
      '@layersSetting' => (($settings['layers'] && $configuration_settings->get('method') != 'webfonts') ? 'Yes' : 'No'),
    ]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'layers' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // Early opt-out if the field is empty.
    if (count($items) <= 0) {
      return [];
    }

    // Load the configuration settings.
    $configurationSettings = $this->configFactory->get('fontawesome.settings');

    // Attach the libraries as needed.
    $fontawesomeLibraries = [];
    if ($configurationSettings->get('method') == 'webfonts') {
      // Webfonts method.
      $fontawesomeLibraries[] = 'fontawesome/fontawesome.webfonts';
    }
    else {
      // SVG method.
      $fontawesomeLibraries[] = 'fontawesome/fontawesome.svg';

      // Attach the shim file if needed.
      if ($configurationSettings->get('use_shim')) {
        $fontawesomeLibraries[] = 'fontawesome/fontawesome.svg.shim';
      }
    }

    // Loop over each icon and build data.
    $icons = [];
    foreach ($items as $item) {
      // Get the icon settings.
      $iconSettings = unserialize($item->get('settings')->getValue());

      // Format mask.
      $iconMask = '';
      if (!empty($iconSettings['masking']['mask'])) {
        $iconMask = $iconSettings['masking']['style'] . ' fa-' . $iconSettings['masking']['mask'];
      }
      unset($iconSettings['masking']);

      // Format power transforms.
      $iconTransforms = [];
      $powerTransforms = $iconSettings['power_transforms'];
      foreach ($powerTransforms as $transform) {
        if (!empty($transform['type'])) {
          $iconTransforms[] = $transform['type'] . '-' . $transform['value'];
        }
      }
      unset($iconSettings['power_transforms']);

      $icons[] = [
        '#theme' => 'fontawesomeicon',
        '#tag' => $configurationSettings->get('tag'),
        '#name' => 'fa-' . $item->get('icon_name')->getValue(),
        '#style' => $item->get('style')->getValue(),
        '#settings' => implode(' ', $iconSettings),
        '#transforms' => implode(' ', $iconTransforms),
        '#mask' => $iconMask,
      ];
    }

    // Get the icon settings.
    $settings = $this->getSettings();

    return [
      [
        '#theme' => 'fontawesomeicons',
        '#icons' => $icons,
        '#layers' => $settings['layers'],
      ],
      '#attached' => [
        'library' => $fontawesomeLibraries,
      ],
    ];
  }

}
