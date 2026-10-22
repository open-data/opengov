<?php

namespace Drupal\facets\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a Facets URL processor plugin attribute.
 *
 * @see \Drupal\facets\UrlProcessor\UrlProcessorPluginManager
 * @see plugin_api
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class FacetsUrlProcessor extends Plugin {

  /**
   * Constructs a Facets URL processor attribute.
   *
   * @param string $id
   *   The URL processor plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $label
   *   The human-readable name of the URL processor plugin.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $description
   *   The URL processor description.
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   */
  public function __construct(
    public readonly string $id,
    public readonly ?TranslatableMarkup $label = NULL,
    public readonly ?TranslatableMarkup $description = NULL,
    public readonly ?string $deriver = NULL,
  ) {}

}
