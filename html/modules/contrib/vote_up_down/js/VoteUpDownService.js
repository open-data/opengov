/**
 * @file
 * Vote Up/Down behavior service
 */
(function ($, Drupal) {

  window.voteUpDownService = (function() {
    function voteUpDownService() {}
    voteUpDownService.vote = function(baseWidget, url, operation, basePath, points, uservote) {
      $.ajax({
        type: "GET",
        url: url,
        success: function(response) {
          baseWidget.find('.throbber').remove();
          baseWidget.find('.reset').addClass('element-invisible');
          baseWidget.find('.up.active').each(function () {
            $(this).removeClass('active').addClass('inactive');
          });
          baseWidget.find('.down.active').each(function () {
            $(this).removeClass('active').addClass('inactive');
          });
          if(operation !== 'reset') {
            baseWidget.find('.' + operation).each(function () {
              $(this).removeClass('inactive').addClass('active');
            });
            baseWidget.find('.reset').removeClass('element-invisible');
            points -= uservote;
          }
          else if(operation === 'up')
            points -= uservote + 1;
          else
            points -= uservote - 1;
        }
      });
    };
    return voteUpDownService;
  })();

})(jQuery, Drupal);
