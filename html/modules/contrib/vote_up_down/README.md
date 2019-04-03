# Introduction

The Vote Up/Down module allows the user to cast votes on some entities in
a Drupal site with different widgets. This module uses Voting API to implement
votes and Chaos Tools to provide AJAX support and make custom widgets.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/vote_up_down

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/vote_up_down


# Requirements

This module requires the following modules:

 * Voting API (https://www.drupal.org/project/votingapi)
 * Chaos Tools (https://www.drupal.org/project/ctools)


# Features

 The module is implemented as a field, you may vote over any field-able entity, node, comment and term are examples of entities.


# Installation

 Install as usual, see https://www.drupal.org/docs/8/extending-drupal-8/installing-modules for further information.

# Configuration

 Configure permissions in Administer >> People >> Permissions >> **Vote Up/Down**:

  - access vote up/down statistics:
     Users in roles with the 'access vote up/down statistics' permission
     will be able to see the votes performed by each user on its 'Votes' tab.

  - administer vote up/down:
     Users in roles with the 'administer vote up/down' permission will be able
     to modify the Voting API tag for Vote Up/Down votes.

  - reset vote up/down votes:
     Users in roles with the 'reset vote up/down votes' permission will be able
     to undo their own votes if it's also permitted in the configuration for
     the respective module.

  - use vote up/down:
     Users in roles with the 'use vote up/down' permission will be able to
     actually cast a vote with vote up/down(for the callback).

 Start voting!

# Customization

 You can write your own widget for Vote Up/Down, and you can put it in
  your module or in your theme. Please take a look to
  [WIDGETAPI.md](WIDGETAPI.md) for more information.

 ## Theming widgets and votes
   
 Since widgets are implemented through plugins, and we have many plugins
 to choose at runtime, we can not use direct theme templates, so instead
 we render by hands the templates.

 NOTE: It is not possible to decide dynamically the path where the
 template is located. In contrast we can dynamically define function and
 template names.


# Frequently Asked Questions

<!--- These needs to be changed but putting them as it as for now --->

How to display voting widget by API for nodes?::
If you want to custom how and where to show the widget, you need to
render manually each part of the standard node `$content` variable
(fields, body, etc).
```php
<?php
// save the rendered value of the widget
$vud_widget = $node->content['vud_node_widget_display']['#value'];
// avoid show it twice
unset($node->content['vud_node_widget_display']);

// render each value inside $content
print $node->content['body']['#value'];

// print vud widget, here is the place where we want to show it
print $vud_widget;

// render more values inside $content
```

How can I prevent bots to vote when anonymous voting is enabled?::
When anonymous voting is enabled, you need to modify your `robots.txt`
file to prevent bots to vote.
```
Disallow: /vote/
Disallow: /?q=vote/
```

# Credits

Original Author - Fredrik Jonsson fredrik at combonet dot se

Orginal 2.x version - Pratul Kalia (lut4rp)

Orginal Port to Drupal 8: Sudhanshu Gautam (sudhanshug)

Current Maintainer: Marco Villegas (marvil07)
