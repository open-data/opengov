<?php

namespace Drupal\webform_entity_print_attachment_test\Plugin\EntityPrint\PrintEngine;

use Drupal\entity_print_test\Plugin\EntityPrint\PrintEngine\TestPrintEngine;

/**
 * A test print engine that returns the rendered attachment contents.
 *
 * @PrintEngine(
 *   id = "webform_entity_print_test",
 *   label = @Translation("Webform Entity Print Test Print Engine"),
 *   export_type = "pdf"
 * )
 */
class WebformEntityPrintTestPrintEngine extends TestPrintEngine {

  /**
   * {@inheritdoc}
   */
  public function getBlob() {
    return $this->html;
  }

}
