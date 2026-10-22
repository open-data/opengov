<?php

namespace Drupal\search_api_solr\Plugin\DataType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\Attribute\DataType;
use Drupal\Core\TypedData\Plugin\DataType\DateTimeIso8601;

/**
 * A data type for Solr date strings.
 *
 * The plain value of this data type is a date string in ISO 8601 format.
 */
#[DataType(
 id: 'solr_date',
 label: new TranslatableMarkup('Solr date')
)]
class SolrDate extends DateTimeIso8601 {

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    return \Drupal::service('solarium.query_helper')->formatDate((string) $this->value);
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    parent::setValue(rtrim($value, 'Z'), $notify);
  }

}
