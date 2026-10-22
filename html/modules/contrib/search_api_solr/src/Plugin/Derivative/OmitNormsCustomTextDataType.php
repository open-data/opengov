<?php

namespace Drupal\search_api_solr\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\search_api_solr\Entity\SolrFieldType;
use Drupal\search_api_solr\Plugin\search_api\data_type\SearchApiDataTypePrefixInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides plugin definitions for custom full text data types which omit norms.
 *
 * @see \Drupal\search_api_solr\Plugin\search_api\data_type\CustomTextDataType
 */
class OmitNormsCustomTextDataType extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static();
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach (SolrFieldType::getAvailableCustomCodes() as $custom_code) {
      $this->derivatives[$custom_code] = $base_plugin_definition;
      $this->derivatives[$custom_code]['label'] =
        $this->t('Fulltext ":custom_code" Omit norms', [':custom_code' => $custom_code]);

      if (is_a($this->derivatives[$custom_code]['class'], SearchApiDataTypePrefixInterface::class, TRUE)) {
        // With attributes the prefix is provided by the method.
        $prefix = $this->derivatives[$custom_code]['class']::getPrefix();
      }
      else {
        // With legacy annotations, the prefix was a root property.
        $prefix = $this->derivatives[$custom_code]['prefix'];
      }

      $this->derivatives[$custom_code]['prefix'] = $prefix . $custom_code;
    }
    return $this->derivatives;
  }

}
