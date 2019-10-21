# Composer Project for open.canada.ca

[![Build Status](https://travis-ci.org/RabiaSajjad/og.svg?branch=master)](https://travis-ci.org/RabiaSajjad/og)

Drupal codebase for [open.canada.ca](https://open.canada.ca)

## Requirements

* [Composer](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx)


## Installation

1. Create project

```
composer create-project opengov/opengov-project:dev-master MYPROJECT --no-interaction
```

2. Install using interface, choose `Open Government` as your installation profile. As an alternative, 
you can also use drush for installation

```
drush site:install og
```

## Updating core and/or contributed modules

1. Check for outdated modules
```
composer outdated "drupal/*"
```

2. If updates are required, it is very important to make a backup of both codebase and database before updating

3. Update modules that are outdated
```
composer update drupal/MODULE --with-dependencies
drush updatedb
drush cr
```
If you want to know all packages that will be updated by the composer update command, 
use the `--dry-run` option first.

For more detailed information on updating Drupal, check [Drupal Documentation](https://www.drupal.org/docs/8/update).
