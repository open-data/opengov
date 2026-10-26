((Drupal, once) => {
  const identifierAttribute = 'data-facets-exposed-filter-identifier';
  const dependentsAttribute = 'data-facets-exposed-filter-dependents';

  /**
   * Gets exposed form elements for an exposed filter identifier.
   *
   * @param {HTMLFormElement} form
   *   The exposed form.
   * @param {string} identifier
   *   The exposed filter identifier.
   *
   * @return {Element[]}
   *   Matching form elements.
   */
  function getElementsByIdentifier(form, identifier) {
    return Array.from(form.elements).filter((element) => {
      const name = element.getAttribute('name') || '';
      return name === identifier || name.startsWith(`${identifier}[`);
    });
  }

  /**
   * Resets a select to the empty exposed-filter option.
   *
   * @param {HTMLSelectElement} select
   *   The select element.
   */
  function resetSelect(select) {
    if (select.multiple) {
      Array.from(select.options).forEach((option) => {
        option.selected = false;
      });
      return;
    }

    const emptyOption =
      Array.from(select.options).find((option) => option.value === 'All') ||
      Array.from(select.options).find((option) => option.value === '');

    if (emptyOption) {
      select.value = emptyOption.value;
      return;
    }

    select.selectedIndex = -1;
  }

  /**
   * Clears one exposed filter element.
   *
   * @param {Element} element
   *   The element to clear.
   */
  function clearElement(element) {
    if (element.tagName === 'SELECT') {
      resetSelect(element);
      return;
    }

    if (element.tagName !== 'INPUT' && element.tagName !== 'TEXTAREA') {
      return;
    }

    switch (element.type) {
      case 'checkbox':
      case 'radio':
        element.checked = false;
        break;

      case 'hidden':
        element.remove();
        break;

      default:
        element.value = '';
        break;
    }
  }

  /**
   * Builds a dependency map from the rendered exposed form.
   *
   * @param {HTMLFormElement} form
   *   The exposed form.
   *
   * @return {Map<string, string[]>}
   *   Direct dependent identifiers keyed by parent identifier.
   */
  function getDependencyMap(form) {
    const map = new Map();
    form.querySelectorAll(`[${dependentsAttribute}]`).forEach((element) => {
      const identifier = element.getAttribute(identifierAttribute);
      const dependents = element
        .getAttribute(dependentsAttribute)
        .split(' ')
        .filter(Boolean);

      if (identifier && dependents.length > 0) {
        map.set(identifier, dependents);
      }
    });

    return map;
  }

  /**
   * Clears dependent filters recursively.
   *
   * @param {HTMLFormElement} form
   *   The exposed form.
   * @param {string[]} identifiers
   *   The identifiers to clear.
   * @param {Map<string, string[]>} dependencyMap
   *   Dependency map for the exposed form.
   * @param {Set<string>} visited
   *   Already cleared identifiers.
   */
  function clearDependentFilters(form, identifiers, dependencyMap, visited) {
    identifiers.forEach((identifier) => {
      if (visited.has(identifier)) {
        return;
      }

      visited.add(identifier);
      getElementsByIdentifier(form, identifier).forEach(clearElement);
      clearDependentFilters(
        form,
        dependencyMap.get(identifier) || [],
        dependencyMap,
        visited,
      );
    });
  }

  /**
   * Finds the exposed form referenced by a Views filters summary.
   *
   * @param {Element} element
   *   A summary child element.
   *
   * @return {HTMLFormElement|null}
   *   The exposed form.
   */
  function getSummaryExposedForm(element) {
    const summary = element.closest('.views-filters-summary');
    const exposedFormId = summary?.getAttribute('data-exposed-form-id');

    if (!exposedFormId) {
      return null;
    }

    return (
      Array.from(document.forms).find((form) =>
        form.id.startsWith(exposedFormId),
      ) || null
    );
  }

  /**
   * Clears dependencies for one changed parent element.
   *
   * @param {Element} parent
   *   The parent exposed filter element.
   */
  function clearElementDependents(parent) {
    const form = parent.closest('form');

    if (!form) {
      return;
    }

    const dependents = parent
      .getAttribute(dependentsAttribute)
      .split(' ')
      .filter(Boolean);

    clearDependentFilters(form, dependents, getDependencyMap(form), new Set());
  }

  Drupal.behaviors.facetsExposedDependentFilters = {
    attach(context) {
      once('facets-exposed-dependent-filters', 'form', context).forEach(
        (form) => {
          if (!form.querySelector(`[${dependentsAttribute}]`)) {
            return;
          }

          form.addEventListener(
            'change',
            (event) => {
              if (!(event.target instanceof Element)) {
                return;
              }

              const parent = event.target.closest(`[${dependentsAttribute}]`);

              if (!parent || !form.contains(parent)) {
                return;
              }

              clearElementDependents(parent);
            },
            true,
          );
        },
      );

      once(
        'facets-exposed-dependent-filter-summary',
        '.views-filters-summary a.remove-filter[data-remove-selector]',
        context,
      ).forEach((link) => {
        link.addEventListener(
          'click',
          () => {
            const removeSelector = link.getAttribute('data-remove-selector');

            if (!removeSelector) {
              return;
            }

            const colonIndex = removeSelector.indexOf(':');

            if (colonIndex === -1) {
              return;
            }

            const identifier = removeSelector.substring(0, colonIndex);
            const form = getSummaryExposedForm(link);

            if (!form) {
              return;
            }

            const dependencyMap = getDependencyMap(form);
            clearDependentFilters(
              form,
              dependencyMap.get(identifier) || [],
              dependencyMap,
              new Set(),
            );
          },
          true,
        );
      });
    },
  };
})(Drupal, once);
