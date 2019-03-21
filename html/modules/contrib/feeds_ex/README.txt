Feeds extensible parsers
========================

A set of extensible parsers for Feeds.
http://drupal.org/project/feeds_ex

Provided parsers
================
- XPath XML & HTML
- QueryPath XML & HTML (requires the QueryPath library)
- JSONPath JSON & JSON lines parser (requires a JSONPath library)
- JMESPath JSON & JSON linesparser (requires the JMESPath library)

Requirements
============

- Feeds
  http://drupal.org/project/feeds

Installation
============

- Download and enable just like a normal module.

QueryPath
=========
To use the QueryPath parsers, you will need the QueryPath library. If you
installed this module through composer, you already have this library. Else you
would need to require it with composer:

$ composer require querypath/QueryPath:^3.0

The source code for this library can be found at:
https://github.com/technosophos/querypath

JSONPath
========
To use the JSONPath parsers, you will need a JSONPath library. If you
installed this module through composer, you already have this library. Else you
would need to require it with composer:

$ composer require peekmo/jsonpath:^1.0

The source code for this library can be found at:
https://github.com/Peekmo/JsonPath

The plan is to support the JSONPath library from Flow communications in the
future as well and use this as the default one. The source code for this library
can be found at:
https://github.com/FlowCommunications/JSONPath

JMESPath
========
To use the JMESPath parsers, you will need the JMESPath library. If you
installed this module through composer, you already have this library. Else you
would need to require it with composer:

$ composer require mtdowling/jmespath.php:^2.0

The source code for this library can be found at
https://github.com/jmespath/jmespath.php
