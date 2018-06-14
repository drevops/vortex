# Composer preserve paths

Composer plugin for preserving paths while installing, updating or uninstalling packages.

This way you can:

* provide custom files or directories that will not be overwritten on `composer install` or `composer update`
* place packages within the directory of another package (using a composer installer like
[composer/installers](https://packagist.org/packages/composer/installers) or
[davidbarratt/custom-installer](https://packagist.org/packages/davidbarratt/custom-installer))


## Installation

Simply install the plugin with composer: `composer require derhasi/composer-preserve-paths`

## Configuration

For configuring the paths you need to set `preserve-paths` within the `extra` of your root `composer.json`.

```json
{
    "extra": {
        "preserve-paths": [
          "web/sites/all/modules/contrib",
          "web/sites/all/themes/contrib",
          "web/sites/all/libraries",
          "web/sites/all/drush"
        ]
      }
}
```

## Example

An example composer.json using [composer/installers](https://packagist.org/packages/composer/installers):

```json
{
  "repositories": [
    {
      "type": "composer",
      "url": "https://packages.drupal.org/7"
    }
  ],
  "require": {
    "composer/installers": "^1.2",
    "derhasi/composer-preserve-paths": "0.1.*",
    "drupal/views": "3.*",
    "drupal/drupal": "7.*"
  },
  "config": {
    "vendor-dir": "vendor"
  },
  "extra": {
    "installer-paths": {
      "web/": ["type:drupal-core"],
      "web/sites/all/modules/contrib/{$name}/": ["type:drupal-module"],
      "web/sites/all/themes/contrib/{$name}/": ["type:drupal-theme"],
      "web/sites/all/libraries/{$name}/": ["type:drupal-library"],
      "web/sites/all/drush/{$name}/": ["type:drupal-drush"],
      "web/profiles/{$name}/": ["type:drupal-profile"]
    },
    "preserve-paths": [
      "web/sites/all/modules/contrib",
      "web/sites/all/themes/contrib",
      "web/sites/all/libraries",
      "web/sites/all/drush",
      "web/sites/default/settings.php",
      "web/sites/default/files"
    ]
  }
}
```


