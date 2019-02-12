<?php

namespace Utilities\composer;

use Composer\Script\Event;
use Dotenv\Dotenv;
use DrupalFinder\DrupalFinder;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class DrupalSettings.
 */
class DrupalSettings {

  /**
   * Create Drupal settings file.
   */
  public static function create(Event $event) {
    $fs = new Filesystem();
    $drupalFinder = new DrupalFinder();
    $drupalFinder->locateRoot(getcwd());
    $drupalRoot = $drupalFinder->getDrupalRoot();

    $standard_settings_file = $drupalRoot . '/sites/default/settings.php';
    $generated_settings_file_name = 'settings.generated.php';

    $defaults = [
      'mysql_database' => 'drupal',
      'mysql_user' => 'drupal',
      'mysql_password' => 'drupal',
      'mysql_host' => 'localhost',
      'mysql_port' => '',
      'mysql_prefix' => '',
      'settings_path' => $drupalRoot . '/sites/default/' . $generated_settings_file_name,
    ];

    $options = self::extractEnvironmentVariables(array_keys($defaults))
      + self::extractCliOptions($event->getArguments(), array_keys($defaults))
      + $defaults;

    if (!$fs->exists($options['settings_path'])) {
      $content = self::getDefaultDrupalSettingsContent($options);
      $fs->dumpFile($options['settings_path'], $content);
      $fs->chmod($options['settings_path'], 0644);
      $event->getIO()->write(sprintf('Created file %s with chmod 0644', $options['settings_path'] . PHP_EOL . $content));
    }
    else {
      $event->getIO()->write(sprintf('Skipping creation of Drupal settings file "%s" - file already exists', $options['settings_path']));
    }

    // Add inclusion of this file to standard settings file if it exists and
    // such inclusion has not been added previously.
    if ($fs->exists($options['settings_path']) && $fs->exists($standard_settings_file)) {
      if (strpos(file_get_contents($standard_settings_file), $generated_settings_file_name) === FALSE) {
        $string = <<<GENERATEDSETTINGS
// Include generated settings file.
if (file_exists(\$app_root . '/' . \$site_path . '/$generated_settings_file_name')) {
  include \$app_root . '/' . \$site_path . '/$generated_settings_file_name';
}
GENERATEDSETTINGS;
        self::appendToFile($standard_settings_file, $string);
        $event->getIO()->write(sprintf('Added inclusion of generated settings file %s to %s', $generated_settings_file_name, $standard_settings_file));
      }
      else {
        $event->getIO()->write(sprintf('Skipped inclusion of generated settings file %s to %s - inclusion already present', $generated_settings_file_name, $standard_settings_file));
      }
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

  /**
   * Appends content to an existing file.
   *
   * Polyfill for older versions of Filesystem shipped with Composer phar.
   *
   * @param string $filename
   *   The file to which to append content.
   * @param string $content
   *   The content to append.
   *
   * @throws \Symfony\Component\Filesystem\Exception\IOException
   *   If the file is not writable.
   */
  protected static function appendToFile($filename, $content) {
    $fs = new Filesystem();

    $dir = \dirname($filename);

    if (!is_dir($dir)) {
      $fs->mkdir($dir);
    }

    if (!is_writable($dir)) {
      throw new IOException(sprintf('Unable to write to the "%s" directory.', $dir), 0, NULL, $dir);
    }

    if (FALSE === @file_put_contents($filename, $content, FILE_APPEND)) {
      throw new IOException(sprintf('Failed to write file "%s".', $filename), 0, NULL, $filename);
    }
  }

}
