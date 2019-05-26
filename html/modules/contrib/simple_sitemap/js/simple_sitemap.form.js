/**
 * @file
 * Attaches simple_sitemap behaviors to the entity form.
 */
(function($) {

  "use strict";

  Drupal.behaviors.simple_sitemapForm = {
    attach: function(context) {

      // On load: Hide the 'Regenerate sitemap' field to only display it if settings have changed.
      $('.form-item-simple-sitemap-regenerate-now').hide();

      // Show 'Regenerate sitemap' field if settings have changed.
      $("#edit-simple-sitemap-index-content"
          + ", #edit-simple-sitemap-variant"
          + ", #edit-simple-sitemap-priority"
          + ", #edit-simple-sitemap-changefreq"
          + ", #edit-simple-sitemap-include-images"
      ).change(function() {
        $('.form-item-simple-sitemap-regenerate-now').show();
      });
    }
  };
})(jQuery);
