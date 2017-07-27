<?php

namespace Utilities\composer;

use Composer\Script\Event;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class DrupalSettings.
 */
class DrupalSettings {

  /**
   * Create Drupal settings file.
   */
  public static function create(Event $event) {
    $defaults = [
      'db_name' => 'beetbox',
      'db_user' => 'beetbox',
      'db_pass' => 'beetbox',
      'db_host' => 'localhost',
      'db_port' => '',
      'db_prefix' => '',
      'settings_path' => 'docroot/sites/default/settings.beetbox.php',
    ];

    $options = self::extractEnvironmentVariables(array_keys($defaults))
      + self::extractCliOptions($event->getArguments(), array_keys($defaults))
      + $defaults;

    $fs = new Filesystem();
    if (!$fs->exists($options['settings_path'])) {
      $fs->dumpFile($options['settings_path'], self::getDefaultDrupalSettingsContent($options));
      $event->getIO()->write(sprintf('Created file %s', $options['settings_path']));
    }
    else {
      $event->getIO()->write('Skipping creation of Drupal settings file - file already exists');
    }
  }

  /**
   * Delete Drupal settings file.
   */
  public static function delete(Event $event) {
    $defaults = [
      'settings_path' => 'docroot/sites/default/settings.beetbox.php',
    ];

    $options = self::extractEnvironmentVariables(array_keys($defaults))
      + self::extractCliOptions($event->getArguments(), array_keys($defaults))
      + $defaults;

    $fs = new Filesystem();
    if (!$fs->exists($options['settings_path'])) {
      $event->getIO()->write('Skipping deletion of Drupal settings file - file does not exists');
    }
    else {
      $fs->remove($options['settings_path']);
      $event->getIO()->write(sprintf('Deleted file %s', $options['settings_path']));
    }
  }

  /**
   * Return content for default Drupal settings file.
   */
  protected static function getDefaultDrupalSettingsContent($options) {
    return <<<FILE
<?php

/**
 * @file
 * Beetbox settings.
 *
 * Do not modify this file if you need to override default settings.
 */

// Local DB settings.
\$databases = [
  'default' =>
    [
      'default' =>
        [
          'database' => '${options['db_name']}',
          'username' => '${options['db_user']}',
          'password' => '${options['db_pass']}',
          'host' => '${options['db_host']}',
          'port' => '${options['db_port']}',
          'driver' => 'mysql',
          'prefix' => '${options['db_prefix']}',
        ],
    ],
];
FILE;
  }

  /**
   * Extract options from environment variables.
   *
   * @param bool|array $allowed
   *   Array of allowed options.
   *
   * @return array
   *   Array of extracted options.
   */
  protected static function extractEnvironmentVariables(array $allowed) {
    $options = [];

    foreach ($allowed as $name) {
      $value = getenv(strtoupper($name));
      if ($value !== FALSE) {
        $options[$name] = $value;
      }
    }

    return $options;
  }

  /**
   * Extract options from CLI arguments.
   *
   * @param array $arguments
   *   Array of arguments.
   * @param bool|array $allowed
   *   Array of allowed options.
   *
   * @return array
   *   Array of extracted options.
   */
  protected static function extractCliOptions(array $arguments, array $allowed) {
    $options = [];

    foreach ($arguments as $argument) {
      if (strpos($argument, '--') === 0) {
        list($name, $value) = explode('=', $argument);
        $name = substr($name, strlen('--'));
        $options[$name] = $value;
        if (array_key_exists($name, $allowed) && !is_null($value)) {
          $options[$name] = $value;
        }
      }
    }

    return $options;
  }

}
