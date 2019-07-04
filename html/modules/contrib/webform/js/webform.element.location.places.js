/**
 * @file
 * JavaScript behaviors for Algolia places location integration.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  // @see https://github.com/algolia/places
  // @see https://community.algolia.com/places/documentation.html#options
  Drupal.webform = Drupal.webform || {};
  Drupal.webform.locationPlaces = Drupal.webform.locationPlaces || {};
  Drupal.webform.locationPlaces.options = Drupal.webform.locationPlaces.options || {};

  var mapping = {
    lat: 'lat',
    lng: 'lng',
    name: 'name',
    postcode: 'postcode',
    locality: 'locality',
    city: 'city',
    administrative: 'administrative',
    country: 'country',
    countryCode: 'country_code',
    county: 'county',
    suburb: 'suburb'
  };

  /**
   * Initialize location places.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformLocationPlaces = {
    attach: function (context) {
      if (!window.places) {
        return;
      }

      $(context).find('.js-webform-type-webform-location-places').once('webform-location-places').each(function () {
        var $element = $(this);
        var $input = $element.find('.webform-location-places');

        // Prevent the 'Enter' key from submitting the form.
        $input.keydown(function (event) {
          if (event.keyCode === 13) {
            event.preventDefault();
          }
        });

        var options = $.extend({
          type: 'address',
          useDeviceLocation: true,
          container: $input.get(0)
        }, Drupal.webform.locationPlaces.options);

        // Add application id and API key.
        if (drupalSettings.webform.location.places.app_id && drupalSettings.webform.location.places.api_key) {
          options.appId = drupalSettings.webform.location.places.app_id;
          options.apiKey = drupalSettings.webform.location.places.api_key;
        }

        var placesAutocomplete = window.places(options);

        // Disable autocomplete.
        // @see https://gist.github.com/niksumeiko/360164708c3b326bd1c8
        var isChrome = /Chrome/.test(window.navigator.userAgent) && /Google Inc/.test(window.navigator.vendor);
        $input.attr('autocomplete', (isChrome) ? 'off' : 'false');

        // Sync values on change and clear events.
        placesAutocomplete.on('change', function (e) {
          $.each(mapping, function (source, destination) {
            var value = (source === 'lat' || source === 'lng' ? e.suggestion.latlng[source] : e.suggestion[source]) || '';
            setValue(destination, value);
          });
        });
        placesAutocomplete.on('clear', function (e) {
          $.each(mapping, function (source, destination) {
            setValue(destination, '');
          });
        });

        /**
         * Set attribute value.
         *
         * @param {string} name
         *   The attribute name
         * @param {string} value
         *   The attribute value
         */
        function setValue(name, value) {
          var inputSelector = ':input[data-webform-location-places-attribute="' + name + '"]';
          $element.find(inputSelector).val(value);
        }
      });
    }
  };

})(jQuery, Drupal, drupalSettings);

