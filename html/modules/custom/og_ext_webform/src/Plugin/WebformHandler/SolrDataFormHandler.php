<?php

namespace Drupal\og_ext_webform\Plugin\WebformHandler;

use Drupal\search_api\Entity\Index;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\webformSubmissionInterface;

/**
 * Form submission handler.
 *
 * @WebformHandler(
 *   id = "solr_data_form_handler",
 *   label = @Translation("Solr Data Copy Handler"),
 *   category = @Translation("Form Handler"),
 *   description = @Translation("Copies field data from a Solr record to existing matching fields (based on machine name) in the webform"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */
class SolrDataFormHandler extends WebformHandlerBase {

  public function preSave(WebformSubmissionInterface $webform_submission) {
    $webform_values = $webform_submission->getData();
    $index = Index::load($webform_values['solr_core']);
    if ($index) {
      $query = $index->query();
      $query->addCondition('id', $webform_values['entity_id']);
      $results = $query->execute();
      $items = $results->getResultItems();
      if (!empty($items)) {
        $row = reset($items);
        $field_names = array_keys($row->getFields());
        $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();

        foreach ($webform_values as $key => $value) {
          if (in_array($key, $field_names) || in_array($key . '_' . $langcode, $field_names)) {
            $solr_field_value = (!empty($row->getField($key . '_' . $langcode)))
              ? $row->getField($key . '_' . $langcode)->getValues()
              : $row->getField($key)->getValues();
            $webform_submission->setElementData($key, implode(", ", $solr_field_value));
          }
        }
      }
    }
  }

}
