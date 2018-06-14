Drupal Helpers
==============

A library of Drupal-related PHP helpers for Drupal 7 core and contrib modules.

[![Circle CI](https://circleci.com/gh/alexdesignworks/drupal_helpers.svg?style=shield)](https://circleci.com/gh/alexdesignworks/drupal_helpers)

Functionality
-------------

* Bean
  * Create or load a bean.
* Block
  * Render a block.
  * Place or remove a block in/from a region using core block module.
  * Remove all blocks from all regions oin a specified theme.
  * Set the block visibility.
* Entity
  * Get label for entity bundle.
* Feature
  * Revert a feature.
* Field
  * Delete a field.
  * Delete an instance of a field.
  * Get and Set field configurations.
  * Change the max length of a Text field that contains content.
* Form
  * Get default values from the form.
* General
  * Print CLI and web messages.
* Menu
  * Add, update, delete and find menu items in specified menu.
  * Find children and siblings of a menu item.
  * Import menu from the array-like tree.
* Module
	* Enable, disable or uninstall a module.
* Random
 	* Generate random: string, name, IP address, phone number, email, date of birth, path.
	* Get random array items.
* System
	* Get or set the weight of the module, theme or profile.
	* Check the status of the module, theme or profile.
* Taxonomy
	* Create form element options from terms in provided vocabulary.
	* Find single term by name.
	* Create terms hierarchy from simple tree.
	* Remove all terms from vocabulary.
* Theme
	* Set a theme as the default or admin theme.
	* Enable or disable a theme.
	* Set theme setting.
* User
	* Create user with specified fields and roles.
* Utility
	* Recursively remove empty elements from array.
	* Retrieve array column.
* Variable
	* Set and get variable values using strict match, wildcard or regexp.
	* Variable value storage - store/restore variable values in bulk.

Usage
-----

Use the Drupal helpers classes to perform common tasks during your Drupal module updates.

```php
<?php

/**
 * @file
 * example.install uninstall and update implementations.
 */

use Drupal\drupal_helpers\Module;
use Drupal\drupal_helpers\Feature;
use Drupal\drupal_helpers\General;

/**
 * Enable Views and Revert 'mysite' features.
 */
function example_update_7001 () {
  // Enable views.
  Module::enable('views');

  // Revert mysite features.
  Feature::revert('mysite_features');

  // Print My message.
  General::messageSet('My message');
}
```

Dependencies
------------

- [X Autoload](https://www.drupal.org/project/xautoload)
