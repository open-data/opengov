<?php

namespace Drupal\webform\Element;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a webform element for a location geocomplete element.
 *
 * @FormElement("webform_location_geocomplete")
 */
class WebformLocationGeocomplete extends WebformLocationBase {

  /**
   * {@inheritdoc}
   */
  protected static $name = 'geocomplete';

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return parent::getInfo() + [
      '#api_key' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getLocationAttributes() {
    return [
      'lat' => t('Latitude'),
      'lng' => t('Longitude'),
      'location' => t('Location'),
      'formatted_address' => t('Formatted Address'),
      'street_address' => t('Street Address'),
      'street_number' => t('Street Number'),
      'subpremise' => t('Unit'),
      'postal_code' => t('Postal Code'),
      'locality' => t('Locality'),
      'sublocality' => t('City'),
      'administrative_area_level_1' => t('State/Province'),
      'country' => t('Country'),
      'country_short' => t('Country Code'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function processWebformComposite(&$element, FormStateInterface $form_state, &$complete_form) {
    $element = parent::processWebformComposite($element, $form_state, $complete_form);

    // Add Google Maps API key which is required by
    // https://maps.googleapis.com/maps/api/js?key=API_KEY&libraries=places
    // @see webform_js_alter()
    $api_key = (!empty($element['#api_key'])) ? $element['#api_key'] : \Drupal::config('webform.settings')->get('element.default_google_maps_api_key');
    $element['#attached']['drupalSettings']['webform']['location']['geocomplete']['api_key'] = $api_key;

    return $element;
  }

}
