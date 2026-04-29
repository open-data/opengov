/* eslint-disable class-methods-use-this */
((Drupal) => {
  class ViewsFiltersSummaryCVFBHandler extends Drupal.ViewsFiltersSummaryHandler {
    /**
     * Returns the current view exposed form to reset.
     *
     * @param {Element} resetElement
     *   The reset button element that was clicked.
     *
     * @return {Element|*}
     *   The view exposed form to reset.
     */
    getExposedFormToReset(resetElement) {
      // Find the form associated with this specific reset button
      const summaryElt = resetElement.closest('[data-exposed-form-id]');
      const exposedFormId = summaryElt.getAttribute('data-exposed-form-id');
      return document.querySelector(`form#${exposedFormId}`);
    }

    /**
     * Get all exposed form elements that match a specific selector.
     *
     * @param {Element} exposedForm
     *   The exposed form jQuery object.
     * @param {string} selector
     *   The selector to match inputs against.

     * @return {NodeListOf<Element>}
     *   The list of inputs that match the selector.
     */
    getFormElementsByName(exposedForm, selector) {
      return document.querySelectorAll(`[name^="${selector}"]`);
    }
  }
  Drupal.ViewsFiltersSummaryHandler = ViewsFiltersSummaryCVFBHandler;
})(Drupal);
