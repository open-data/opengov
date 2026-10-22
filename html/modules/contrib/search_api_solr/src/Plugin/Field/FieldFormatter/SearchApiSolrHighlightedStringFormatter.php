<?php

namespace Drupal\search_api_solr\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\Plugin\Field\FieldType\StringItemBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Template\TwigEnvironment;
use Drupal\Core\Template\TwigExtension;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'solr_highlighted_string' formatter.
 */
#[FieldFormatter(
  id: 'solr_highlighted_string',
  label: new TranslatableMarkup('Highlighted plain text (Search API Solr)'),
  field_types: ['string'],
)]
class SearchApiSolrHighlightedStringFormatter extends FormatterBase {
  use SearchApiSolrHighlightedFormatterSettingsTrait;

  /**
   * The Twig service.
   */
  protected TwigEnvironment $twig;

  /**
   * The Twig extension service.
   */
  protected TwigExtension $twigExtension;

  /**
   * Constructs a formatter instance.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, TwigEnvironment $twig, TwigExtension $twig_extension) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->twig = $twig;
    $this->twigExtension = $twig_extension;
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
      $container->get('twig'),
      $container->get('twig.extension')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   *
   * @see \Drupal\Core\Field\Plugin\Field\FieldFormatter\StringFormatter::viewValue()
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    // The ProcessedText element already handles cache context & tag bubbling.
    // @see \Drupal\filter\Element\ProcessedText::preRenderText()
    foreach ($items as $delta => $item) {
      assert($item instanceof StringItemBase);

      $cacheableMetadata = new CacheableMetadata();
      // The fulltext search keys are usually set via a GET parameter.
      $cacheableMetadata->addCacheContexts(['url.query_args']);

      $elements[$delta] = [
        '#markup' => nl2br($this->getHighlightedValue($item, $this->twigExtension->escapeFilter($this->twig, $item->value), $langcode, $cacheableMetadata)),
      ];

      $cacheableMetadata->applyTo($elements[$delta]);
    }

    return $elements;
  }

}
