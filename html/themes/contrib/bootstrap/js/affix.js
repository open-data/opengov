/**
 * @file
 * Affix for Bootstrap 5.
 * https://www.codeply.com/users/skelly
 *
 */
(function ($, Drupal, once) {
  'use strict';

  Drupal.behaviors.bootstrap_affix = {
    attach: function (context, settings) {
      var toggleAffix = function (
        affixElement,
        scrollElement,
        wrapper
      ) {
        var height = affixElement.outerHeight(),
          top = wrapper.offset().top;

        if (scrollElement.scrollTop() >= top) {
          wrapper.height(height);
          affixElement.addClass('affix');
        } else {
          affixElement.removeClass('affix');
          wrapper.height('auto');
        }
      };

      once('affixed', '[data-toggle="affix"]', context).forEach(
        (element) => {
          var ele = $(element),
            wrapper = $('<div></div>');

          ele.before(wrapper);
          $(window).on('scroll resize', function () {
            toggleAffix(ele, $(this), wrapper);
          });

          // init
          toggleAffix(ele, $(window), wrapper);
        }
      );
    },
  };
})(jQuery, Drupal, once);
