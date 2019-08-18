/**
 * @file
 * GeoMap Layer related javascript functions
 */

(function ($, Drupal) {

  'use strict';

  /**
   * GeoMap Layer Display Wrapper for Open Government Across Canada
   * Change panel to collapsible panel
   */
  Drupal.behaviors.GeoMapLayersWrapper = {
    attach: function (context, settings) {
      $(document).on("wb-ready.wb", function(event) {
        let map = document.getElementById("geomap-layers-location_map");
        let layers = map.getElementsByClassName("panel");
        $(layers).each(function(index) {
          let title = $(this).find(".panel-title").text();
          $(this).find(".panel-title").text("");
          $(this).find(".panel-title").append("<a data-toggle=\"collapse\" href=\"#collapse" + index + "\">" + title + "</a>");
          $(this).find(".panel-body").wrap("<div id=\"collapse" + index + "\" class=\"panel-collapse collapse\">");
          $(this).wrap("<div id='map_layer" + index +"'></div>");
        });
      });
    }
  };

  /**
   * Open collapsible panel on link click
   */
  Drupal.behaviors.OpenGeoMapLayer = {
    attach: function (context, settings) {
      $("#open_provinces").click(function() {
        $(document.getElementById("collapse2")).addClass("in");
      });
      $("#open_initiatives").click(function() {
        $(document.getElementById("collapse1")).addClass("in");
      });
      $("#open_municipalities").click(function() {
        $(document.getElementById("collapse0")).addClass("in");
      });
    }
  };

})(window.jQuery, window.Drupal, window.drupalSettings);

