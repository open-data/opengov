<?php

namespace Drupal\vud\Plugin\VoteUpDownWidget;

use Drupal\Core\Annotation\Translation;
use Drupal\vud\Plugin\VoteUpDownWidgetBase;

/**
 * Provides the "plain" Vote Up/Down widget
 *
 * @VoteUpDownWidget(
 *   id = "plain",
 *   admin_label = @Translation("Plain"),
 *   description = @Translation("Provides two arrows, up and down.")
 *  )
 */
class Plain extends VoteUpDownWidgetBase {

}