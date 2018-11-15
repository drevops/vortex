<?php

namespace Utilities\composer;

use Composer\Script\Event;
use Dotenv\Dotenv;
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
      'mysql_database' => 'drupal',
      'mysql_user' => 'drupal',
      'mysql_password' => 'drupal',
      'mysql_host' => 'localhost',
      'mysql_port' => '',
      'mysql_prefix' => '',
      'settings_path' => 'docroot/sites/default/settings.generated.php',
    ];

    $options = self::extractEnvironmentVariables(array_keys($defaults))
      + self::extractCliOptions($event->getArguments(), array_keys($defaults))
      + $defaults;

    $fs = new Filesystem();
    if (!$fs->exists($options['settings_path'])) {
      $content = self::getDefaultDrupalSettingsContent($options);
      $fs->dumpFile($options['settings_path'], $content);
      $fs->chmod($options['settings_path'], 0666);
      $event->getIO()->write(sprintf('Created file %s', $options['settings_path'] . PHP_EOL . $content));
    }
    else {
      $event->getIO()->write(sprintf('Skipping creation of Drupal settings file "%s" - file already exists', $options['settings_path']));
    }
  }

  /**
   * Delete Drupal settings file.
   */
  public static function delete(Event $event) {
    $defaults = [
      'settings_path' => 'docroot/sites/default/settings.generated.php',
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
 * Generated settings.
 *
 * Do not modify this file if you need to override default settings.
 */

// Local DB settings.
\$databases = [
  'default' =>
    [
      'default' =>
        [
          'database' => '${options['mysql_database']}',
          'username' => '${options['mysql_user']}',
          'password' => '${options['mysql_password']}',
          'host' => '${options['mysql_host']}',
          'port' => '${options['mysql_port']}',
          'driver' => 'mysql',
          'prefix' => '${options['mysql_prefix']}',
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

    $dotenv = new Dotenv(__DIR__ . '/../..');
    $dotenv->load();

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
