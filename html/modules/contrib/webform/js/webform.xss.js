/**
 * @file
 * JavaScript helpers for filtering HTML.
 */

(function (Drupal, drupalSettings) {
  Drupal.webform = Drupal.webform || {};
  Drupal.webform.xss = Drupal.webform.xss || {};

  const defaultAllowedTags = {
    br: [],
    p: [],
    h2: [],
    h3: [],
    h4: [],
    h5: [],
    h6: [],
    a: ['href'],
    blockquote: [],
    ul: [],
    ol: ['reversed', 'start'],
    strong: [],
    em: [],
    sub: [],
    sup: [],
    li: [],
  };

  /**
   * Gets allowed tags from Drupal settings.
   *
   * @return {object}
   *   An object keyed by tag name with allowed attributes as values.
   */
  function getAllowedTags() {
    const settings =
      drupalSettings.webform &&
      drupalSettings.webform.xss &&
      drupalSettings.webform.xss.allowedTags;
    if (!Array.isArray(settings) || !settings.length) {
      return defaultAllowedTags;
    }

    const tags = {};
    settings.forEach(function (tagName) {
      const normalizedTagName = tagName.toLowerCase();
      tags[normalizedTagName] = defaultAllowedTags[normalizedTagName] || [];
    });
    return tags;
  }

  const allowedTags = getAllowedTags();

  /**
   * Checks if an attribute is allowed on an element.
   *
   * @param {string} tagName
   *   The element tag name.
   * @param {string} attributeName
   *   The attribute name.
   *
   * @return {boolean}
   *   TRUE if the attribute is allowed.
   */
  function isAllowedAttribute(tagName, attributeName) {
    return allowedTags[tagName].includes(attributeName);
  }

  /**
   * Checks if a URL attribute value is allowed.
   *
   * @param {string} value
   *   The attribute value.
   *
   * @return {boolean}
   *   TRUE if the attribute value is allowed.
   */
  function isAllowedUrl(value) {
    const parser = document.createElement('a');
    parser.href = value;
    return (
      ['http:', 'https:', 'mailto:', 'tel:', ''].includes(parser.protocol)
    );
  }

  /**
   * Filters HTML to the tags supported by Webform's editor and filter.
   *
   * @param {string} html
   *   The HTML.
   *
   * @return {string}
   *   The filtered HTML.
   */
  Drupal.webform.xss.filter = function (html) {
    const template = document.createElement('template');
    // Parse HTML in a <template> because template contents are inert until
    // later inserted into the live document. MDN documents template.content as
    // a DocumentFragment, and the HTML Standard states template contents are
    // kept outside a browsing context and remain inert, e.g. scripts do not run.
    // @see https://developer.mozilla.org/en-US/docs/Web/API/HTMLTemplateElement/content
    // @see https://html.spec.whatwg.org/multipage/scripting.html#the-template-element
    template.innerHTML = html || '';

    Array.from(template.content.querySelectorAll('*')).forEach(function (element) {
        const tagName = element.tagName.toLowerCase();
        if (!allowedTags[tagName]) {
          if (['script', 'style'].includes(tagName)) {
            element.remove();
          } else {
            element.replaceWith(
              ...Array.from(element.childNodes),
            );
          }
          return;
        }

        Array.from(element.attributes).forEach(function (attribute) {
            const attributeName = attribute.name.toLowerCase();
            if (!isAllowedAttribute(tagName, attributeName)) {
              element.removeAttribute(attribute.name);
              return;
            }

            if (attributeName === 'href' && !isAllowedUrl(attribute.value)) {
              element.removeAttribute(attribute.name);
            }
          });
      });

    return template.innerHTML;
  };
})(Drupal, drupalSettings);
