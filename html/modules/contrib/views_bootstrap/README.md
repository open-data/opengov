# Views Bootstrap

The Views Bootstrap module adds styles to Views to output the results of a view
as several common Bootstrap components.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/views_bootstrap).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/views_bootstrap)


## Table of contents

- Requirements
- Installation
- Configuration
- Using Views
- Maintainers


## Requirements

This module requires the following modules:

- [Views](https://www.drupal.org/project/views)
- [Bootstrap Theme](https://www.drupal.org/project/bootstrap/)

Optional modules:

- [Views Reference](https://www.drupal.org/project/viewsreference) - For per-reference grid/card settings


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

1. Navigate to Administration > Extend and enable the module.


## Using Views

1. Add style type and select which bootstrap component style you wish to use
2. Change the settings for that specific component

### Carousel Responsive Behavior

The Bootstrap Carousel component supports responsive layouts for multi-column
carousels. This allows you to display different numbers of items per slide
based on screen size.

**How it works:**
- On small screens (below your selected breakpoint): Shows 1 item per slide
- On larger screens (at and above your breakpoint): Shows multiple items per slide

**Example configuration:**
To show 3 items per slide on desktop and 1 item on mobile:
1. Set "Columns per slide" to 3
2. Set "Multi-column breakpoint" to Medium (992px)

Result:
- Mobile phones (< 992px): 1 item per slide at full width
- Tablets and desktops (≥ 992px): 3 items per slide side-by-side


## Maintainers

- Dmitry Demenchuk - [mrded](https://www.drupal.org/u/mrded)
- Alex Burrows - [aburrows](https://www.drupal.org/u/aburrows)
- Shelane French - [shelane](https://www.drupal.org/u/shelane)
- Eric Pugh - [ericpugh](https://www.drupal.org/u/ericpugh)
- Bart Verheyde - [ikeigenwijs](https://www.drupal.org/u/ikeigenwijs)
