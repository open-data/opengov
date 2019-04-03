(function ($) {

  Drupal.behaviors.vud_widget_thumbs = {
    attach: function (context) {
      if (!$('.vud-widget').hasClass('vud-widget-processed')) {
        $('.vote-thumb').click(function () {
          if ($(this).hasClass('active') || $(this).hasClass('active')) {
            $(this).parents('.vud-widget').find('.vud-link-reset').click();
          }
        });
      }
    }
  };

})(jQuery);
