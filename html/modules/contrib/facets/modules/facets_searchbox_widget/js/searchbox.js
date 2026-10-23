/**
 * @file
 * Provides the searchbox functionality.
 */

(function ($) {
  Drupal.facets = Drupal.facets || {};

  Drupal.behaviors.facets_searchbox = {
    attach: (context, settings) => {
      const $facetsWidgetSearchbox = $('.facets-widget-searchbox', context);

      function search(filter, display, $targetList, $context) {
        const value = $(this).find('.facet-item__value').html();

        if (value.toUpperCase().indexOf(filter) !== -1) {
          if (!$(this).hasClass('hide-if-no-result')) {
            this.style.display = display;
          }
          $context.find('.facets-soft-limit-link').each(function () {
            this.style.display = 'inline';
          });
        } else {
          if (!$(this).hasClass('facet-item--expanded')) {
            this.style.display = 'none';
          } else {
            $(this).addClass('hide-if-no-result');
          }

          $context.find('.facets-soft-limit-link').each(function () {
            this.style.display = 'none';
          });
        }
      }

      function resetSearch($facetsSoftLimitLink, display, displayCount) {
        if (
          $facetsSoftLimitLink.length === 0 ||
          $facetsSoftLimitLink.hasClass('open')
        ) {
          if (!$(this).hasClass('hide-if-no-result')) {
            this.style.display = display;
          }
        } else {
          // eslint-disable-next-line no-lonely-if
          if (displayCount >= 5) {
            if (!$(this).hasClass('facet-item--expanded')) {
              this.style.display = 'none';
            } else {
              $(this).addClass('hide-if-no-result');
            }
          } else {
            if (!$(this).hasClass('hide-if-no-result')) {
              this.style.display = display;
            }
            displayCount += 1;
          }
        }
        $facetsSoftLimitLink.each(function () {
          this.style.display = 'inline';
        });

        return displayCount;
      }

      function handleNoResults(targetListId, $facetsWidgetSearchboxNoResult) {
        if (
          $(
            `[data-drupal-facet-id='${
              targetListId
            }'] li:visible:not(.hide-if-no-result)`,
          ).length === 0
        ) {
          $facetsWidgetSearchboxNoResult.removeClass('hide');
          $('.hide-if-no-result').addClass('hide');
        } else {
          $facetsWidgetSearchboxNoResult.addClass('hide');
          $('.hide-if-no-result').removeClass('hide');
        }
      }

      function getDisplayBehavior() {
        switch ($(this).attr('data-type')) {
          case 'checkbox':
            return 'flex';

          case 'links':
            return 'inline';
        }
      }

      $facetsWidgetSearchbox.on('keyup', function () {
        const input = this;
        const $input = $(input);
        const $context = $input.parent();
        const $facetsWidgetSearchboxNoResult = $context.find(
          '.facets-widget-searchbox-no-result',
        );
        const $targetList = $context.find('.facets-widget-searchbox-list');
        const targetListId = $targetList.attr('data-drupal-facet-id');
        const $facetsSoftLimitLink = $context.find('.facets-soft-limit-link');
        const filter = input.value.toUpperCase();
        let displayCount = 0;
        const display = getDisplayBehavior.call(this);

        $(`[data-drupal-facet-id='${targetListId}'] li`).each(function () {
          if (filter !== '') {
            search.call(this, filter, display, $targetList, $context);
          } else {
            displayCount = resetSearch.call(
              this,
              $facetsSoftLimitLink,
              display,
              displayCount,
            );
          }
        });

        handleNoResults(targetListId, $facetsWidgetSearchboxNoResult);
      });
    },
  };
})(jQuery);
