/**
 * @file
 * Provides the soft limit functionality.
 */

(function ($, once) {
  Drupal.behaviors.facetSoftLimit = {
    attach: (context, settings) => {
      if (settings.facets.softLimit !== 'undefined') {
        $.each(settings.facets.softLimit, function (facet, limit) {
          Drupal.facets.applySoftLimit(facet, limit, settings);
        });
      }
    },
  };

  Drupal.facets = Drupal.facets || {};

  /**
   * Applies the soft limit UI feature to a specific facets list.
   *
   * @param {string} facet
   *   The facet id.
   * @param {string} limit
   *   The maximum amount of items to show.
   * @param {object} settings
   *   Settings.
   */
  Drupal.facets.applySoftLimit = function (facet, limit, settings) {
    const zeroBasedLimit = limit - 1;
    const facetId = facet;
    const facetsList = $(`ul[data-drupal-facet-id="${facetId}"]`);

    // In case of multiple instances of a facet, we need to key them.
    if (facetsList.length > 1) {
      facetsList.each(function (key, $value) {
        $(this).attr('data-drupal-facet-id', `${facetId}-${key}`);
      });
    }

    // Hide facets over the limit.
    facetsList.each(function () {
      const allLiElements = $(this).find('li');
      $(once('applysoftlimit', allLiElements.slice(zeroBasedLimit + 1))).hide();
    });

    // Add "Show more" / "Show less" links.
    $(
      once(
        'applysoftlimit',
        facetsList.filter(function () {
          return $(this).find('> li').length > limit;
        }),
      ),
    ).each(function () {
      const facet = $(this);
      const showLessLabel =
        settings.facets.softLimitSettings[facetId].showLessLabel;
      const showMoreLabel =
        settings.facets.softLimitSettings[facetId].showMoreLabel;
      const $link = $('<a href="#" class="facets-soft-limit-link"></a>');
      $link[0].textContent = showMoreLabel;
      $link
        .on('click', function () {
          if (facet.find('> li:hidden').length > 0) {
            facet.find(`> li:gt(${zeroBasedLimit})`).slideDown();
            facet
              .find(
                `> li:lt(${zeroBasedLimit + 2}) a, li:lt(${
                  zeroBasedLimit + 2
                }) input`,
              )
              .focus();
            $(this).addClass('open')[0].textContent = showLessLabel;
          } else {
            facet.find(`> li:gt(${zeroBasedLimit})`).slideUp();
            $(this).removeClass('open')[0].textContent = showMoreLabel;
          }
          return false;
        })
        .insertAfter($(this));
    });
  };
})(jQuery, once);
