<?php

/**
 * @file
 * Preprocessors and helper functions to make theming easier.
 */

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Template\Attribute;
use Drupal\views_bootstrap\ViewsBootstrap;

/**
 * Normalize any value to a safe string for Xss::filter().
 *
 * PHP 8.1+ deprecates passing NULL into string funcs used by Xss::filter().
 * Coerce to string before filtering to avoid deprecations.
 */
function views_bootstrap_safe_markup($value, array $allowed_markup) : string {
  return Xss::filter($value ?? '', $allowed_markup);
}

/**
 * Prepares variables for views accordion templates.
 *
 * Default template: views-bootstrap-accordion.html.twig.
 *
 * @param array $vars
 *   An associative array containing:
 *   - view: A ViewExecutable object.
 *   - rows: The raw row data.
 */
function template_preprocess_views_bootstrap_accordion(array &$vars): void {
  $view = $vars['view'];
  $options = $view->style_plugin->options;
  $vars['id'] = ViewsBootstrap::getUniqueId($view);
  $group_title_field = isset($options['grouping'][0]) ? $options['grouping'][0]['field'] : "";
  $panel_title_field = $options['panel_title_field'];
  $vars['output'] = $options['panel_output'];
  $vars['behavior'] = $options['behavior'];
  $vars['collapse'] = $options['sections'];
  $vars['attributes'] = new Attribute(['class' => ['accordion']]);

  // Define allowed HTML markup for XSS filtering.
  $allowed_markup = ['img', 'br', 'h2', 'h3', 'h4', 'h5', 'h6', 'span', 'strong', 'em', 'i', 'small'];

  if ($options['flush']) {
    $vars['attributes']->addClass('accordion-flush');
  }

  if ($vars['output'] == 'grouped') {
    // Initialize an array to hold grouped content.
    $grouped_content = [];

    // Iterate over each row.
    foreach ($vars['rows'] as $id => $row) {
      // Get the panel title for the current row.
      $panel_title = $view->style_plugin->getField($id, $panel_title_field);

      // Filter the panel title for XSS protection.
      $filtered_title = views_bootstrap_safe_markup($panel_title, $allowed_markup);

      // Prepare $filtered_title for use as part of an HTML ID.
      $group_id = Html::getId('-' . $filtered_title);

      // Initialize title if it doesn't exist in the grouped content array.
      if (!isset($grouped_content[$group_id])) {
        $grouped_content[$group_id] = [
          'title' => ['#markup' => $filtered_title],
          'content' => [],
        ];
      }

      // Add the current row's content to the grouped content.
      $grouped_content[$group_id]['content'][] = $row;
    }

    // Replace the rows with the grouped content.
    $vars['rows'] = $grouped_content;
  }
  else {
    foreach ($vars['rows'] as $id => $row) {
      $vars['group_title'] = $group_title_field ? $view->style_plugin->getField($id, $group_title_field) : "";
      $vars['rows'][$id] = [];
      $vars['rows'][$id]['content'] = $row;
      $vars['rows'][$id]['title'] = [
        '#markup' => views_bootstrap_safe_markup(
          $view->style_plugin->getField($id, $panel_title_field),
          $allowed_markup
        ),
      ];
    }
  }
}

/**
 * Prepares variables for views cards templates.
 *
 * Default template: views-bootstrap-cards.html.twig.
 *
 * @param array $vars
 *   An associative array containing:
 *   - view: A ViewExecutable object.
 *   - rows: The raw row data.
 */
function template_preprocess_views_bootstrap_cards(array &$vars): void {
  $view = $vars['view'];
  $options = $view->style_plugin->options;

  // Get unique ID and initialize card group attributes.
  $vars['id'] = ViewsBootstrap::getUniqueId($view);

  if ($options['card_group']) {
    $vars['use_card_group'] = TRUE;
    $vars['attributes'] = new Attribute(['class' => ['card-group']]);
    // Add custom classes if defined.
    if (!empty($options['card_group_class_custom'])) {
      $custom_classes = array_filter(explode(' ', $options['card_group_class_custom']));
      $clean_classes = array_map([Html::class, 'cleanCssIdentifier'], $custom_classes);
      $vars['attributes']->addClass($clean_classes);
    }
  }
  else {
    $vars['row_attributes'] = new Attribute(['class' => ['row']]);
    if (!empty($options['row_class_custom'])) {
      $custom_classes = array_filter(explode(' ', $options['row_class_custom']));
      $clean_classes = array_map([Html::class, 'cleanCssIdentifier'], $custom_classes);
      $vars['row_attributes']->addClass($clean_classes);
    }
    $vars['col_attributes'] = new Attribute(['class' => ['col']]);
    if (!empty($options['col_class_custom'])) {
      $custom_classes = array_filter(explode(' ', $options['col_class_custom']));
      $clean_classes = array_map([Html::class, 'cleanCssIdentifier'], $custom_classes);
      $vars['col_attributes']->addClass($clean_classes);
    }
  }

  // Set columns and display settings.
  $vars['columns'] = $options['columns'] ?? 1;
  $display = $options['display'] ?? 'content';

  // Check if the view is using fields to display instead of content.
  $using_fields = ($display != 'content' && $view->style_plugin->usesFields());

  // Define field variables if using fields.
  $image_field = $using_fields ? ($options['card_image_field'] ?? NULL) : NULL;
  $title_field = $using_fields ? ($options['card_title_field'] ?? NULL) : NULL;
  $content_field = $using_fields ? ($options['card_content_field'] ?? NULL) : NULL;
  $card_clickable = $options['card_clickable'] ?? FALSE;
  $link_field = $using_fields ? ($options['card_link_field'] ?? NULL) : NULL;

  // Process each row in the view.
  foreach ($vars['rows'] as $id => $row) {
    // Initialize the row attributes.
    $row_attributes = new Attribute(['class' => ['card']]);
    if ($row_class = $view->style_plugin->getRowClass($id)) {
      $row_attributes->addClass($row_class);
    }

    // Process row based on display type (fields vs content).
    if ($using_fields) {
      $title_output = $title_field ? $view->style_plugin->getField($id, $title_field) : NULL;
      $link_output = NULL;

      // Add stretched-link class to make entire card clickable if enabled.
      if ($card_clickable && $link_field) {
        $link_output = $view->style_plugin->getField($id, $link_field);

        if ($link_output) {
          // Convert Markup objects to strings for processing.
          $link_string = ($link_output instanceof MarkupInterface) ? (string) $link_output : $link_output;

          if (is_string($link_string) && strpos($link_string, '<a ') !== FALSE) {
            // Add the stretched-link class to the first <a> tag.
            $link_string = preg_replace(
              '/(<a\s+)/',
              '$1class="stretched-link" ',
              $link_string,
              1
            );
            // If the <a> tag already has a class attribute, merge them.
            $link_string = preg_replace(
              '/class="stretched-link"\s+class="([^"]*)"/',
              'class="stretched-link $1"',
              $link_string
            );
            // Update the link output with the modified string.
            $link_output = $link_string;
          }
        }
      }

      $vars['rows'][$id] = [
        'attributes' => $row_attributes,
        'image' => $image_field ? $view->style_plugin->getField($id, $image_field) : NULL,
        'title' => $title_output,
        'content' => $content_field ? $view->style_plugin->getField($id, $content_field) : NULL,
        'link' => $link_output,
      ];
    }
    else {
      // Display row content directly.
      $vars['rows'][$id] = [
        'attributes' => $row_attributes,
        'content' => $row,
      ];
    }
  }
}

/**
 * Prepares variables for views carousel template.
 *
 * Default template: views-bootstrap-carousel.html.twig.
 *
 * @param array $vars
 *   An associative array containing:
 *   - view: A ViewExecutable object.
 *   - rows: The raw row data.
 */
function template_preprocess_views_bootstrap_carousel(array &$vars): void {
  $view = $vars['view'];
  $options = $view->style_plugin->options;
  $vars['id'] = ViewsBootstrap::getUniqueId($view);
  $vars['attributes']['class'][] = 'views-bootstrap-media-object';
  $vars['attributes']['class'][] = 'media-list';

  // Carousel options.
  $columns = max(1, (int) $options['columns']);
  $has_multiple_slides = count($vars['rows']) > $columns;
  $vars['interval'] = $options['interval'];
  $vars['keyboard'] = $options['keyboard'];
  $vars['navigation'] = $options['navigation'] && $has_multiple_slides;
  $vars['indicators'] = $options['indicators'] && $has_multiple_slides;
  $vars['pause'] = $options['pause'] ? 'hover' : FALSE;
  $vars['wrap'] = $options['wrap'];
  $vars['effect'] = $options['effect'];
  $vars['columns'] = $columns;
  $prefix = ViewsBootstrap::getColumnPrefix($options['breakpoints']);
  $column_width = intval(12 / $columns);
  // Add mobile-first responsive columns: full width on small screens,
  // multi-column at the selected breakpoint and above.
  if ($options['breakpoints'] != 'xs') {
    $vars['breakpoints'] = 'col-12 ' . $prefix . '-' . $column_width;
  }
  else {
    $vars['breakpoints'] = $prefix . '-' . $column_width;
  }
  $vars['use_caption'] = $options['use_caption'];
  $vars['caption_breakpoints'] = $options['caption_breakpoints'];
  $vars['ride'] = $options['ride'];
  $vars['display'] = 'content';

  // Carousel rows.
  if ($options['display'] != 'content' && $view->style_plugin->usesFields()) {
    $image = $options['image'];
    $title = $options['title'];
    $description = $options['description'];
    $fieldLabels = $view->display_handler->getFieldLabels(TRUE);
    $vars['display'] = 'fields';
  }

  foreach ($vars['rows'] as $id => $row) {
    $vars['rows'][$id] = [];
    $row_attributes = ['class' => []];
    $class = $options['row_class'];
    if ($vars['display'] == 'fields') {
      $vars['rows'][$id]['image'] = $view->style_plugin->getField($id, $image);
      $vars['rows'][$id]['title'] = $view->style_plugin->getField($id, $title);
      $vars['rows'][$id]['description'] = $view->style_plugin->getField($id, $description);
      // Add any additional fields to result.
      foreach (array_keys($fieldLabels) as $label) {
        if (!in_array($label, [$image, $title, $description])) {
          $vars['rows'][$id][$label] = $view->style_plugin->getField($id, $label);
        }
      }
      $class = strip_tags($view->style_plugin->tokenizeValue($class, $id));
      $class = Html::cleanCssIdentifier($class);
    }
    else {
      $vars['rows'][$id]['content'] = $row;
    }
    $classes = explode(' ', $class);
    foreach ($classes as &$class) {
      $class = Html::cleanCssIdentifier($class);
    }
    $row_class = array_filter($classes);
    if (!empty($row_class)) {
      $row_attributes['class'] = array_merge($row_attributes['class'], $row_class);
    }
    $vars['rows'][$id]['attributes'] = new Attribute($row_attributes);

  }

}

/**
 * Prepares variables for views dropdown templates.
 *
 * Adds 'dropdown' classes and some aria roles to the list structure.
 *
 * Default template: views-bootstrap-dropdown.html.twig.
 *
 * @param array $vars
 *   An associative array containing:
 *   - view: A ViewExecutable object.
 *   - rows: The raw row data.
 *
 * @see template_preprocess_views_view_list()
 */
function template_preprocess_views_bootstrap_dropdown(array &$vars): void {
  /** @var \Drupal\views\ViewExecutable $view */
  $view = $vars['view'];
  $options = $view->style_plugin->options;
  $vars['id'] = ViewsBootstrap::getUniqueId($view);

  // Fetch classes from handler options. Sanitize user input.
  $wrapper_class = explode(' ', $options['wrapper_class']);
  $wrapper_class[] = 'dropdown';
  $wrapper_class = array_map([Html::class, 'cleanCssIdentifier'], $wrapper_class);
  $vars['attributes'] = new Attribute(['class' => $wrapper_class]);

  $class = explode(' ', $options['class']);
  $class[] = "dropdown-menu";
  $class = array_map([Html::class, 'cleanCssIdentifier'], $class);
  $vars['list']['attributes'] = new Attribute(['class' => $class]);

  $vars['button']['text'] = $options['button_text'];
  $button_class = explode(' ', $options['button_class']);
  $button_class[] = 'dropdown-toggle';
  $button_class = array_map([Html::class, 'cleanCssIdentifier'], $button_class);
  $vars['button']['attributes'] = new Attribute(['class' => $button_class]);

  // Inject additional dropdown aria attributes into the individual rows to
  // make them behave as menu items.
  // The most common case should be one linked field, but there seems
  // no reason why the whole rendered row can't be here if that's what you want.
  foreach ($vars['rows'] as $id => $row) {
    $vars['rows'][$id] = [];
    $vars['rows'][$id]['content'] = $row;
    // Using role=presentation here is supposed to diminish the screen readers
    // treatment of list items as "List Items". Being a menu item is sufficient.
    // tabindex -1 means that all these links will not waylay keyboard
    // navigation (until the user deliberately opens that list).
    $vars['rows'][$id]['attributes'] = new Attribute(['role' => 'menuitem presentation', 'tabindex' => -1]);
    if ($row_class = $view->style_plugin->getRowClass($id)) {
      $vars['rows'][$id]['attributes']->addClass($row_class);
    }
  }
}

/**
 * Prepares variables for views grid templates.
 *
 * Default template: views-bootstrap-grid.html.twig.
 *
 * @param array $vars
 *   An associative array containing:
 *   - view: A ViewExecutable object.
 *   - rows: The raw row data.
 */
function template_preprocess_views_bootstrap_grid(array &$vars): void {
  $view = $vars['view'];
  $rows = $vars['rows'];
  $options = $view->style_plugin->options;
  $vars['id'] = ViewsBootstrap::getUniqueId($view);
  $attributes = new Attribute(['class' => ['grid']]);

  if ($options['grid_class']) {
    $grid_class = explode(' ', $options['grid_class']);
    $grid_classes = array_map([Html::class, 'cleanCssIdentifier'], array_filter($grid_class));
    $attributes->addClass($grid_classes);
  }

  // When grouping is enabled, Views passes the rendered grouping field as the
  // title variable. This may include theme debug HTML comments and extra
  // whitespace that should be cleaned up before display.
  if (!empty($vars['title']) && !is_array($vars['title'])) {
    $title = $vars['title'];
    // Normalize whitespace.
    $title = preg_replace('/\s+/', ' ', trim($title));
    // Convert to render array to prevent Twig auto-escaping.
    $vars['title'] = ['#markup' => $title];
  }

  $col_classes = [];
  foreach (ViewsBootstrap::getBreakpoints() as $breakpoint) {
    if ($options["col_$breakpoint"] == 'none') {
      continue;
    }
    $col_classes[] = $options["col_$breakpoint"];
  }
  foreach ($rows as $id => $row) {
    $row_attributes = new Attribute();
    if ($row_class = $view->style_plugin->getRowClass($id)) {
      $row_attributes->addClass($row_class);
    }
    $row_attributes->addClass($col_classes);
    $vars['rows'][$id] = [
      'content' => $row,
      'attributes' => $row_attributes,
    ];
  }

  $vars['attributes'] = $attributes;
}

/**
 * Prepares variables for views list group templates.
 *
 * Default template: views-bootstrap-list-group.html.twig.
 *
 * @param array $vars
 *   An associative array containing:
 *   - view: A ViewExecutable object.
 *   - rows: The raw row data.
 */
function template_preprocess_views_bootstrap_list_group(array &$vars): void {
  $view = $vars['view'];
  $options = $view->style_plugin->options;
  $vars['id'] = ViewsBootstrap::getUniqueId($view);
  $group_title_field = isset($options['grouping'][0]) ? $options['grouping'][0]['field'] : "";
  $vars['attributes'] = new Attribute(['class' => ['views-bootstrap-list-group']]);

  if ($options['list_group_class_custom']) {
    $classes = explode(' ', $options['list_group_class_custom']);
    $classes = array_map([Html::class, 'cleanCssIdentifier'], array_filter($classes));
    $vars['attributes']->addClass($classes);
  }

  foreach ($vars['rows'] as $id => $row) {
    $vars['group_title'] = $group_title_field ? $view->style_plugin->getField($id, $group_title_field) : "";
    $vars['rows'][$id] = [];
    $vars['rows'][$id]['content'] = $row;
    $vars['rows'][$id]['title'] = $vars['view']->style_plugin->getField($id, $options['title_field']);
    $vars['rows'][$id]['attributes'] = new Attribute(['class' => ['list-group-item']]);
    if ($row_class = $view->style_plugin->getRowClass($id)) {
      $vars['rows'][$id]['attributes']->addClass($row_class);
    }
  }

}

/**
 * Prepares variables for views media object templates.
 *
 * Default template: views-bootstrap-media-object.html.twig.
 *
 * @param array $vars
 *   An associative array containing:
 *   - view: A ViewExecutable object.
 *   - rows: The raw row data.
 */
function template_preprocess_views_bootstrap_media_object(array &$vars): void {
  $view = $vars['view'];
  $options = $view->style_plugin->options;
  $vars['id'] = ViewsBootstrap::getUniqueId($vars['view']);
  $image_field = $options['image_field'];
  $heading_field = $options['heading_field'];
  $body_field = $options['body_field'];
  $alignment = $options['image_class'];
  $image_placement = $options['image_placement'];
  $body_placement = $image_placement == 'first' ? 'last' : 'first';
  $vars['alignment'] = "align-items-{$alignment}";
  $vars['order_image'] = "order-{$image_placement}";
  $vars['order_body'] = "order-{$body_placement}";

  foreach ($vars['rows'] as $id => $row) {
    $vars['rows'][$id] = [
      'image' => $view->style_plugin->getField($id, $image_field),
      'heading' => $view->style_plugin->getField($id, $heading_field),
      'body' => $view->style_plugin->getField($id, $body_field),
    ];
  }
}

/**
 * Prepares variables for views tab templates.
 *
 * Default template: views-bootstrap-tab.html.twig.
 *
 * @param array $vars
 *   An associative array containing:
 *   - view: A ViewExecutable object.
 *   - rows: The raw row data.
 */
function template_preprocess_views_bootstrap_tab(array &$vars): void {
  $view = $vars['view'];
  $options = $view->style_plugin->options;

  $tab_field = $options['tab_field'] ?? '';
  $group_title_field = isset($options['grouping'][0]) ? $options['grouping'][0]['field'] : "";
  $vars['id'] = ViewsBootstrap::getUniqueId($view);
  $vars['output'] = $options['tab_output'];
  $vars['tab_type'] = $options['tab_type'];
  $vars['tab_position'] = $options['tab_position'];
  $vars['tab_fade'] = $options['tab_fade'] ? 'fade' : '';

  // Define allowed HTML markup for XSS filtering.
  $allowed_markup = ['img', 'br', 'h2', 'h3', 'h4', 'h5', 'h6', 'span', 'strong', 'em', 'i', 'small'];

  // Keeps which id is used for group by field value.
  $tab_ids = [];
  foreach (array_keys($vars['rows']) as $key) {
    $tab_field_value = views_bootstrap_safe_markup(
      $view->style_plugin->getField($key, $tab_field),
      $allowed_markup
    );

    // If tabs are grouped, get previous key for the value.
    if ($vars['output'] == 'grouped' && isset($tab_ids[$tab_field_value])) {
      $key = $tab_ids[$tab_field_value];
    }
    else {
      $tab_ids[$tab_field_value] = $key;
    }
    $vars['tabs'][$key] = $tab_field_value;
  }
  foreach ($vars['rows'] as $id => $row) {
    $vars['group_title'] = $view->style_plugin->getField($id, $group_title_field) ?? "";
    $vars['rows'][$id] = [];

    $tab_field_value = views_bootstrap_safe_markup(
      $view->style_plugin->getField($id, $tab_field),
      $allowed_markup
    );

    // If grouped, the tabs create new array with all the values for the tab
    // content.
    if ($vars['output'] == 'grouped' && isset($tab_ids[$tab_field_value])) {
      $vars['rows'][$id]['content_tabs'] ??= [];

      // Group by the key of first met value of the field grouped by.
      $id = $tab_ids[$tab_field_value];
      $vars['rows'][$id]['content_tabs'][] = $row;
    }
    else {
      $vars['rows'][$id]['content'] = $row;
    }

    $vars['rows'][$id]['attributes'] ??= new Attribute();

    if ($row_class = $view->style_plugin->getRowClass($id)) {
      $vars['rows'][$id]['attributes']->addClass($row_class);
    }
  }
}

/**
 * Prepares variables for views table templates.
 *
 * Default template: views-bootstrap-table.html.twig.
 *
 * @param array $vars
 *   An associative array containing:
 *   - view: A ViewExecutable object.
 *   - rows: The raw row data.
 */
function template_preprocess_views_bootstrap_table(array &$vars): void {
  $options = $vars['view']->style_plugin->options;
  $vars['attributes'] = new Attribute(['class' => ['table']]);
  $vars['bs_responsive'] = $options['responsive'];
  $vars['responsive_size'] = $options['responsive_size'];
  foreach (array_filter($options['bootstrap_styles']) as $style) {
    $vars['attributes']->addClass('table-' . $style);
  }
  if ($options['table_class_custom']) {
    $option_classes = array_filter(explode(' ', $options['table_class_custom']));
    $classes = array_map([Html::class, 'cleanCssIdentifier'], $option_classes);
    $vars['attributes']->addClass($classes);
  }

  // Detect if caption is created from grouping (title field) vs explicit
  // caption.
  // When grouping is used, the title becomes the caption but needs different
  // styling.
  $vars['caption_is_from_grouping'] = FALSE;
  if (!empty($vars['caption_needed']) && !empty($vars['title'])) {
    // Check if there's no explicit caption set - if not, caption comes from
    // title (grouping).
    $handler = $vars['view']->style_plugin;
    if (empty($handler->options['caption'])) {
      $vars['caption_is_from_grouping'] = TRUE;

      // Apply the grouping field's configured wrapper element and classes to
      // the caption content.
      $view = $vars['view'];
      $grouping_level = $vars['grouping_level'] ?? 0;
      if (!empty($view->style_plugin->options['grouping'][$grouping_level]['field'])) {
        $grouping_field_id = $view->style_plugin->options['grouping'][$grouping_level]['field'];
        if (isset($view->field[$grouping_field_id])) {
          $grouping_field = $view->field[$grouping_field_id];
          $wrapper_type = $grouping_field->elementWrapperType(TRUE, TRUE);

          // If a wrapper element is configured, wrap the caption content.
          if ($wrapper_type) {
            $row_index = array_key_first($vars['result'] ?? []);
            $wrapper_classes = $grouping_field->elementWrapperClasses($row_index);
            $caption = $vars['caption'];

            // Build the wrapped caption as a render array.
            $vars['caption'] = [
              '#type' => 'html_tag',
              '#tag' => $wrapper_type,
              'content' => $caption,
            ];

            // Add wrapper classes if configured.
            if ($wrapper_classes) {
              $vars['caption']['#attributes']['class'] = explode(' ', $wrapper_classes);
            }
          }
        }
      }
    }
  }

}
