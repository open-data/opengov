/**
 * @file
 * Transforms links into a dropdown list.
 */

(function ($, Drupal, once) {
  Drupal.facets = Drupal.facets || {};
  Drupal.behaviors.facetsDropdownWidget = {
    attach: (context, settings) => {
      Drupal.facets.makeDropdown(context, settings);
    },
  };

  /**
   * Turns all facet links into a dropdown with options for every link.
   *
   * @param {object} context
   *   Context.
   * @param {object} settings
   *   Settings.
   */
  Drupal.facets.makeDropdown = function (context, settings) {
    // Find all dropdown facet links and turn them into an option.
    $(once('facets-dropdown-transform', '.js-facets-dropdown-links')).each(
      function () {
        const $ul = $(this);
        const $links = $ul.find('.facet-item a');
        const $dropdown = $('<select></select>');
        // Preserve all attributes of the list.
        $ul.each(function () {
          $.each(this.attributes, function (idx, elem) {
            $dropdown.attr(elem.name, elem.value);
          });
        });
        // Remove the class which we are using for .once().
        $dropdown.removeClass('js-facets-dropdown-links');

        $dropdown.addClass('facets-dropdown');
        $dropdown.addClass('js-facets-widget');
        $dropdown.addClass('js-facets-dropdown');
        $dropdown.attr('name', `${$ul.data('drupal-facet-filter-key')}[]`);

        const id = $(this).data('drupal-facet-id');
        // Add aria-labelledby attribute to reference label.
        $dropdown.attr('aria-labelledby', `facet_${id}_label`);
        const defaultOptionLabel =
          settings.facets.dropdown_widget[id]['facet-default-option-label'];

        // Add empty text option first.
        const $defaultOption = $('<option></option>').attr('value', '');
        $defaultOption[0].textContent = defaultOptionLabel;
        $dropdown.append($defaultOption);

        $ul.prepend(
          `<li class="default-option"><a href="${
            window.location.href.split('?')[0]
          }">${Drupal.checkPlain(defaultOptionLabel)}</a></li>`,
        );

        let hasActive = false;
        $links.each(function () {
          const $link = $(this);
          const active = $link.hasClass('is-active');
          const type = $link.data('drupal-facet-widget-element-class');
          const $option = $('<option></option>')
            .attr('value', $link.attr('href'))
            .data($link.data())
            .addClass(type);
          $option[0].value = $link.data('drupal-facet-filter-value');
          if (active) {
            hasActive = true;
            // Set empty text value to this link to unselect facet.
            $defaultOption.attr('value', $link.attr('href'));
            $ul.find('.default-option a').attr('href', $link.attr('href'));
            $option.attr('selected', 'selected');
            $link.find('.js-facet-deactivate').remove();
          }
          const optionLabel = () => {
            // Add hierarchy indicator in case hierarchy is enabled.
            const $parents = $link
              .parent('li.facet-item')
              .parents('li.facet-item');
            let prefix = '';
            for (let i = 0; i < $parents.length; i++) {
              prefix += '-';
            }
            return `${prefix} ${$link[0].textContent.trim()}`;
          };
          $option[0].textContent = optionLabel();
          $dropdown.append($option);
        });

        // Go to the selected option when it's clicked.
        $dropdown.on('change.facets', function () {
          const anchor = $($ul).find(
            `[data-drupal-facet-item-id='${$(this)
              .find(':selected')
              .data('drupalFacetItemId')}']`,
          );
          const $linkElement =
            anchor.length > 0 ? $(anchor) : $ul.find('.default-option a');
          const url = $linkElement.attr('href');
          $(this).trigger('facets_filter', [url]);
        });

        // Append empty text option.
        if (!hasActive) {
          $defaultOption.attr('selected', 'selected');
        }

        // Replace links with dropdown.
        $ul.after($dropdown).hide();
        Drupal.attachBehaviors($dropdown.parent()[0], Drupal.settings);
      },
    );
  };
})(jQuery, Drupal, once);
