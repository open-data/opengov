(function () {
  /**
   * Attaches the behavior to bootstrap carousel view.
   */
  Drupal.behaviors.views_bootstrap_carousel = {
    attach(context, settings) {
      const carousels = document.querySelectorAll('.carousel-inner');
      carousels.forEach(function (carousel) {
        const children = carousel.querySelectorAll('div');
        if (children.length === 1) {
          const siblings = carousel.parentElement.querySelectorAll(
            '.carousel-control, .carousel-indicators',
          );
          siblings.forEach(function (sibling) {
            sibling.style.display = 'none';
          });
        }
      });
    },
  };
})();
