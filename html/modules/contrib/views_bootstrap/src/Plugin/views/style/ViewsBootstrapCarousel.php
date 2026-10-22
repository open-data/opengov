<?php

namespace Drupal\views_bootstrap\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Style plugin to render each item as a row in a Bootstrap Carousel.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "views_bootstrap_carousel",
 *   title = @Translation("Bootstrap Carousel"),
 *   help = @Translation("Displays rows in a Bootstrap Carousel."),
 *   theme = "views_bootstrap_carousel",
 *   theme_file = "../views_bootstrap.theme.inc",
 *   display_types = {"normal"}
 * )
 */
class ViewsBootstrapCarousel extends StylePluginBase {
  /**
   * Whether this style uses a row plugin.
   *
   * @var bool
   */
  protected $usesRowPlugin = TRUE;

  /**
   * Whether the config form exposes the class to provide on each row.
   *
   * @var bool
   */
  protected $usesRowClass = TRUE;

  /**
   * Definition.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    // General carousel settings.
    $options['interval'] = ['default' => 5000];
    $options['keyboard'] = ['default' => TRUE];
    $options['ride'] = ['default' => ''];
    $options['navigation'] = ['default' => TRUE];
    $options['indicators'] = ['default' => TRUE];
    $options['pause'] = ['default' => TRUE];
    $options['wrap'] = ['default' => TRUE];
    $options['effect'] = ['default' => 'slide'];
    $options['use_caption'] = ['default' => TRUE];
    $options['caption_breakpoints'] = ['default' => 'd-none d-md-block'];
    $options['columns'] = ['default' => 1];
    $options['breakpoints'] = ['default' => 'md'];

    // Fields to use in carousel.
    $options['display'] = ['default' => 'fields'];
    $options['image'] = ['default' => ''];
    $options['title'] = ['default' => ''];
    $options['description'] = ['default' => ''];

    return $options;
  }

  /**
   * Render the given style.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['help'] = [
      '#markup' => $this->t('The Bootstrap carousel displays content as a slideshow (<a href=":docs">see documentation</a>).',
        [':docs' => 'https://www.drupal.org/docs/extending-drupal/contributed-modules/contributed-module-documentation/views-bootstrap-for-bootstrap-5/carousel']),
      '#weight' => -99,
    ];

    $form['responsive_help'] = [
      '#type' => 'details',
      '#title' => $this->t('Responsive carousel behavior'),
      '#description' => $this->t('<p><strong>How to show different numbers of items per slide on mobile vs desktop:</strong></p><ul><li><strong>Mobile (small screens):</strong> Always shows 1 item per slide at full width when using multi-column settings</li><li><strong>Desktop (larger screens):</strong> Shows the number of items you select in "Columns per slide"</li></ul><p><strong>Example:</strong> To show 3 items per slide on desktop and 1 item on mobile, set "Columns per slide" to 3 and "Multi-column breakpoint" to Medium. On screens smaller than 992px (phones), users will see 1 item per slide. On screens 992px and wider (tablets, laptops, desktops), they will see 3 items per slide.</p>'),
      '#weight' => -98,
    ];

    $fields = $this->displayHandler->getFieldLabels(TRUE);

    $form['row_class']['#title'] = $this->t('Custom carousel item class');
    $form['row_class']['#description'] = $this->t('Additional classes to provide on the carousel-item row div. Separated by a space. You may use field replacement patterns from the rewrite results section below.');

    $form['keyboard'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Keyboard'),
      '#description' => $this->t('Whether the carousel should react to keyboard events.'),
      '#default_value' => $this->options['keyboard'],
    ];

    $form['ride'] = [
      '#type' => 'select',
      '#title' => $this->t('Ride (Autoplay)'),
      '#description' => $this->t('<a href=":docs">See Bootstrap documentation</a>', [':docs' => 'https://getbootstrap.com/docs/5.3/components/carousel/#autoplaying-carousels']),
      '#empty_option' => $this->t('Do not autoplay'),
      '#options' => [
        'carousel' => $this->t('Autoplay the carousel on load'),
        'true' => $this->t('Autoplay the carousel after user interaction'),
      ],
      '#default_value' => $this->options['ride'],
    ];

    $form['interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Interval'),
      '#description' => $this->t('The amount of time to delay between automatically cycling an item.'),
      '#states' => [
        'visible' => [
          ':input[name="style_options[ride]"]' => ['filled' => TRUE],
        ],
      ],
      '#default_value' => $this->options['interval'],
    ];

    $form['navigation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show navigation'),
      '#default_value' => $this->options['navigation'],
    ];

    $form['indicators'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show indicators'),
      '#default_value' => $this->options['indicators'],
    ];

    $form['pause'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Pause on hover'),
      '#description' => $this->t('Pauses the cycling of the carousel on mouseenter and resumes the cycling of the carousel on mouseleave.'),
      '#default_value' => $this->options['pause'],
    ];

    $form['use_caption'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add captions to your slides for add title and description over the image.'),
      '#description' => $this->t('<a href=":docs">See Bootstrap documentation</a>', [':docs' => 'https://getbootstrap.com/docs/5.3/components/carousel/#captions']),
      '#default_value' => $this->options['use_caption'],
    ];

    $form['caption_breakpoints'] = [
      '#type' => 'select',
      '#title' => $this->t('Hide captions'),
      '#description' => $this->t('Only show captions for the selected breakpoint and larger.'),
      '#empty_option' => $this->t('Always Show'),
      '#options' => [
        'd-none d-sm-block' => $this->t('Small'),
        'd-none d-md-block' => $this->t('Medium'),
        'd-none d-lg-block' => $this->t('Large'),
        'd-none d-xl-block' => $this->t('Extra Large'),
        'd-none d-xxl-block' => $this->t('Extra Extra Large'),
      ],
      '#default_value' => $this->options['caption_breakpoints'],
    ];

    $form['wrap'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Wrap'),
      '#description' => $this->t('The carousel should cycle continuously or have hard stops.'),
      '#default_value' => $this->options['wrap'],
    ];

    $form['effect'] = [
      '#type' => 'select',
      '#title' => $this->t('Effect'),
      '#description' => $this->t('Transition effect (since bootstrap 4.1). <a href=":docs">See Bootstrap documentation</a>', [':docs' => 'https://getbootstrap.com/docs/5.3/components/carousel/#crossfade']),
      '#options' => [
        'slide' => $this->t('Slide'),
        'slide carousel-fade' => $this->t('Fade'),
      ],
      '#default_value' => $this->options['effect'],
    ];

    $form['columns'] = [
      '#type' => 'select',
      '#title' => $this->t('Columns per slide'),
      '#description' => $this->t('The number of items to display side-by-side in each carousel slide on larger screens. On smaller screens (below the breakpoint), only 1 item will be shown per slide.'),
      '#options' => [
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
      ],
      '#default_value' => $this->options['columns'],
    ];

    $form['breakpoints'] = [
      '#type' => 'select',
      '#title' => $this->t('Multi-column breakpoint'),
      '#description' => $this->t('The minimum screen width where the multi-column layout begins. Below this breakpoint, the carousel will display 1 item per slide (full width). At this breakpoint and above, it will display the number of columns selected above. For example: selecting "Medium" with 3 columns will show 1 item per slide on mobile phones, and 3 items per slide on tablets and larger screens.'),
      '#options' => [
        'xs' => $this->t('Extra Small (576px+)'),
        'sm' => $this->t('Small (768px+)'),
        'md' => $this->t('Medium (992px+)'),
        'lg' => $this->t('Large (1200px+)'),
        'xl' => $this->t('Extra large (1400px+)'),
        'xxl' => $this->t('Extra extra large (1600px+)'),
      ],
      '#default_value' => $this->options['breakpoints'],
    ];

    if ($this->usesFields()) {
      $form['display'] = [
        '#type' => 'radios',
        '#title' => $this->t('Display'),
        '#options' => [
          'fields' => $this->t('Select by fields'),
          'content' => $this->t('Display fields as row content'),
        ],
        '#description' => $this->t('Displaying fields as row content will output the field rows as unformatted values within each carousel item.'),
        '#default_value' => $this->options['display'],
      ];

      $form['image'] = [
        '#type' => 'select',
        '#title' => $this->t('Image'),
        '#empty_option' => $this->t('- None -'),
        '#options' => $fields,
        '#default_value' => $this->options['image'],
        '#states' => [
          'visible' => [
            ':input[name="style_options[display]"]' => ['value' => 'fields'],
          ],
        ],
      ];

      $form['title'] = [
        '#type' => 'select',
        '#title' => $this->t('Title'),
        '#empty_option' => $this->t('- None -'),
        '#options' => $fields,
        '#default_value' => $this->options['title'],
        '#states' => [
          'visible' => [
            ':input[name="style_options[display]"]' => ['value' => 'fields'],
          ],
        ],
      ];

      $form['description'] = [
        '#type' => 'select',
        '#title' => $this->t('Description'),
        '#empty_option' => $this->t('- None -'),
        '#options' => $fields,
        '#default_value' => $this->options['description'],
        '#states' => [
          'visible' => [
            ':input[name="style_options[display]"]' => ['value' => 'fields'],
          ],
        ],
      ];
    }

  }

}
