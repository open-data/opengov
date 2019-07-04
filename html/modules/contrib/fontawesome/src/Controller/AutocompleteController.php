<?php

namespace Drupal\fontawesome\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Tags;

/**
 * Defines a route controller for entity autocomplete form elements.
 */
class AutocompleteController extends ControllerBase {

  /**
   * Handler for autocomplete request.
   */
  public function handleAutocomplete(Request $request) {
    $results = [];

    // Get the typed string from the URL, if it exists.
    if ($input = $request->query->get('q')) {
      $typed_string = Tags::explode($input);
      $typed_string = mb_strtolower(array_pop($typed_string));

      // Load the icon data so we can check for a valid icon.
      $iconData = fontawesome_extract_icons();

      // Check each icon to see if it starts with the typed string.
      foreach ($iconData as $icon => $data) {
        // If the string is found.
        if (strpos($icon, $typed_string) === 0) {
          $results[] = [
            'value' => $icon,
            'label' => $this->t('<i class=":prefix fa-:icon fa-fw fa-2x"></i> :icon', [
              ':prefix' => fontawesome_determine_prefix($iconData[$icon]['styles']),
              ':icon' => $icon,
            ]),
          ];
        }
      }
    }

    return new JsonResponse($results);
  }

}
