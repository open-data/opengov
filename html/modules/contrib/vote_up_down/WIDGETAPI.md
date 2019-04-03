# WIDGET API - Vote Up/Down

## Introduction
Now, widgets are implemented using Drupal 8's [Plugins API](https://www.drupal.org/docs/8/api/plugin-api/plugin-api-overview).
Earlier, widgets in D7 were implemented via [CTools](https://www.drupal.org/project/ctools) which was no longer needed in D8.

## Creating a new widget
To create a new widget, one must follow the following steps:

1) Create a plugin class in `src/Plugin/VoteUpDownWidget` and use the following template:
	```php
	<?php
	
	namespace Drupal\vud\Plugin\VoteUpDownWidget;
	
	use Drupal\Core\Annotation\Translation;
	use Drupal\vud\Plugin\VoteUpDownWidgetBase;
	
	/**
	 * Provides the "your_widget_name" Vote Up/Down widget
	 *
	 * @VoteUpDownWidget(
	 *   id = "your_widget_name",
	 *   admin_label = @Translation("your_widget_name"),
	 *   description = @Translation("Provides two arrows, up and down.")
	 *  )
	 */
	class your_widget_name extends VoteUpDownWidgetBase {
	
	}
	```
2) Define the widget template in `/widget/widget.html.twig` using the variables defined in `vud.theme.inc`.
3) Add the css and image files if necessary in the `/widget` directory.
4) Define widget libraries in `libraries.yml` file. Sample library:
	```yaml
	your_widget_name:
    css:
      theme:
        widgets/your_widget_name/your_widget_name.css: {}

	```
5) To use the widget, goto the field display settings of an entity and Edit the Storage settings for that field. Select
the widget name from the dropown.
 