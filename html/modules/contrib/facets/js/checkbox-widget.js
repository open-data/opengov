/**
 * @file
 * Transforms links into checkboxes.
 */

(function ($, Drupal, once) {
  Drupal.facets = Drupal.facets || {};
  Drupal.behaviors.facetsCheckboxWidget = {
    attach: (context) => {
      Drupal.facets.makeCheckboxes(context);
    },
  };

  window.onbeforeunload = function (e) {
    if (Drupal.facets) {
      const $checkboxWidgets = $('.js-facets-checkbox-links');
      if ($checkboxWidgets.length > 0) {
        $checkboxWidgets.each(function (index, widget) {
          const $widget = $(widget);
          const $widgetLinks = $widget.find('.facet-item > a');
          $widgetLinks.each(Drupal.facets.updateCheckbox);
        });
      }
    }
  };

  /**
   * Turns all facet links into checkboxes.
   */
  Drupal.facets.makeCheckboxes = function (context) {
    // Find all checkbox facet links and give them a checkbox.
    const $checkboxWidgets = $(
      once('facets-checkbox-transform', '.js-facets-checkbox-links', context),
    );

    if ($checkboxWidgets.length > 0) {
      $checkboxWidgets.each(function (index, widget) {
        const $widget = $(widget);
        const $widgetLinks = $widget.find('.facet-item > a');

        // Add correct CSS selector for the widget. The Facets JS API will
        // register handlers on that element.
        $widget.addClass('js-facets-widget');

        // Transform links to checkboxes.
        $widgetLinks.each(Drupal.facets.makeCheckbox);

        // We have to trigger attaching of behaviours, so that Facets JS API can
        // register handlers on checkbox widgets.
        Drupal.attachBehaviors(this.parentNode, Drupal.settings);
      });
    }

    // Set indeterminate value on parents having an active trail.
    $('.facet-item--expanded.facet-item--active-trail > input').prop(
      'indeterminate',
      true,
    );
  };

  /**
   * Replace a link with a checked checkbox.
   */
  Drupal.facets.makeCheckbox = function () {
    const $link = $(this);
    const active = $link.hasClass('is-active');
    const description = $link.html();
    const href = $link.attr('href');
    const id = $link.data('drupal-facet-item-id');
    const type = $link.data('drupal-facet-widget-element-class');

    const checkbox = $(
      '<input type="checkbox" class="form-checkbox form-check-input">',
    )
      .attr('id', id)
      .attr(
        'name',
        `${$(this)
          .closest('.js-facets-widget')
          .data('drupal-facet-filter-key')}[]`,
      )
      .addClass(type)
      .data($link.data())
      .data('facetsredir', href);
    checkbox[0].value = $link.data('drupal-facet-filter-value');

    const singleSelectionGroup = $(this).data(
      'drupal-facet-single-selection-group',
    );
    if (singleSelectionGroup) {
      checkbox.addClass(singleSelectionGroup);
    }

    if (type === 'facets-link') {
      checkbox.hide();
    }

    const label = $(
      `<label for="${id}" class="form-check-label">${description}</label>`,
    );

    checkbox.on('change.facets', function (e) {
      e.preventDefault();

      const $widget = $(this).closest('.js-facets-widget');

      Drupal.facets.disableFacet($widget);
      $widget.trigger('facets_filter', [href]);
    });

    if (active) {
      checkbox.prop('checked', true);
      label.addClass('is-active');
      label.find('.js-facet-deactivate').remove();
    }

    $link.before(checkbox).before(label).hide();
  };

  /**
   * Update checkbox active state.
   */
  Drupal.facets.updateCheckbox = function () {
    const $link = $(this);
    const active = $link.hasClass('is-active');

    if (!active) {
      $link.parent().find('input.facets-checkbox').prop('checked', false);
    }
  };

  /**
   * Disable all facet checkboxes in the facet and apply a 'disabled' class.
   *
   * @param {object} $facet
   *   jQuery object of the facet.
   */
  Drupal.facets.disableFacet = function ($facet) {
    $facet.addClass('facets-disabled');
    $('input.facets-checkbox, input.facets-link', $facet).click(
      Drupal.facets.preventDefault,
    );
    $('input.facets-checkbox, input.facets-link', $facet).attr(
      'disabled',
      true,
    );
  };

  /**
   * Event listener for easy prevention of event propagation.
   *
   * @param {object} e
   *   Event.
   */
  Drupal.facets.preventDefault = function (e) {
    e.preventDefault();
  };
})(jQuery, Drupal, once);
