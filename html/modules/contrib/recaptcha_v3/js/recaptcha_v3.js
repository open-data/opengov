/* cspell:ignore grecaptcha */
/* global grecaptcha */

/**
 * @file
 * Recaptcha v3 behaviors.
 */

(($, Drupal) => {
  function doUpdateTokenElement(element) {
    const $element = $(element);

    grecaptcha.ready(() => {
      if (!element) {
        return;
      }

      grecaptcha
        .execute($element.data('recaptchaV3SiteKey'), {
          action: $element.data('recaptchaV3Action'),
        })
        .then((token) => {
          $element[0].value = token;
          $element.trigger('change');
        });
    });
  }

  function updateTokenElement(element) {
    let timer;
    // Wait for grecaptcha to be loaded.
    if (typeof grecaptcha === 'undefined') {
      timer = setInterval(() => {
        if (typeof grecaptcha !== 'undefined' || !element) {
          clearInterval(timer);

          if (element) {
            doUpdateTokenElement(element);
          }
        }
      }, 500);
    } else {
      doUpdateTokenElement(element);
    }
  }

  /**
   * Attach recaptcha response token from google with form.
   *
   * @type {{attach: Drupal.behaviors.reCaptchaV3.attach}}
   */
  Drupal.behaviors.reCaptchaV3 = {
    attach: (context) => {
      once('recaptcha-v3-token', '.recaptcha-v3-token', context).forEach(
        (element) => {
          updateTokenElement(element);
          // Update the recaptcha tokens every 90 seconds.
          // This seems to be the most robust way to always have valid recaptcha
          // tokens when you don't have control over how the forms are being
          // submitted. For example normal form submits are synchronous while
          // Google Recaptcha v3 is asynchronous.
          // A recaptcha token has a maximum lifetime of 120 seconds.
          // https://developers.google.com/recaptcha/docs/v3
          const interval = setInterval(() => {
            if (!element) {
              clearInterval(interval);
            } else {
              updateTokenElement(element);
            }
          }, 90000);
        },
      );
    },
  };
})(jQuery, Drupal);
