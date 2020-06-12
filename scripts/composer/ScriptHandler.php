<?php

namespace DrupalProject\composer;

use Composer\Script\Event;
use Composer\Semver\Comparator;
use DrupalFinder\DrupalFinder;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

/**
 * Class ScriptHandler.
 *
 * @package DrupalProject\composer
 */
class ScriptHandler {

  /**
   * Create files and directories required for Drupal.
   */
  public static function createRequiredFiles(Event $event) {
    $fs = new Filesystem();
    $drupalFinder = new DrupalFinder();
    $drupalFinder->locateRoot(getcwd());
    $drupalRoot = $drupalFinder->getDrupalRoot();

    if (!$drupalRoot) {
      $event->getIO()->writeError(sprintf('Unable to find Drupal root at "%s"', $drupalRoot));
      exit(1);
    }

    $dirs = [
      'modules',
      'profiles',
      'themes',
    ];

    // Required for unit testing.
    foreach ($dirs as $dir) {
      if (!$fs->exists($drupalRoot . DIRECTORY_SEPARATOR . $dir)) {
        $fs->mkdir($drupalRoot . DIRECTORY_SEPARATOR . $dir);
        $fs->touch($drupalRoot . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . '.gitkeep');
      }
    }

    $sitesDefault = $drupalRoot . DIRECTORY_SEPARATOR . 'sites' . DIRECTORY_SEPARATOR . 'default';
    if ($fs->exists($sitesDefault)) {
      $fs->chmod($sitesDefault, 0777);
    }

    // Create settings and services files from default settings, if they do not
    // already exist.
    $defaultSettingsFile = $sitesDefault . DIRECTORY_SEPARATOR . 'default.settings.php';
    $settingsFile = $sitesDefault . DIRECTORY_SEPARATOR . 'settings.php';
    if (!$fs->exists($settingsFile) && $fs->exists($defaultSettingsFile)) {
      $fs->copy($defaultSettingsFile, $settingsFile);
      $fs->chmod($settingsFile, 0666);
      $event->getIO()->write(sprintf('Created a "%s" file from default settings', $settingsFile));
    }
    $defaultServicesFile = $sitesDefault . DIRECTORY_SEPARATOR . 'default.services.yml';
    $servicesFile = $sitesDefault . DIRECTORY_SEPARATOR . 'services.yml';
    if (!$fs->exists($servicesFile) && $fs->exists($defaultServicesFile)) {
      $fs->copy($defaultServicesFile, $servicesFile);
      $event->getIO()->write(sprintf('Created a "%s" file from default settings', $servicesFile));
    }

    // Add 'config_sync_directory' settings to the settings file.
    if ($fs->exists($settingsFile)) {
      $configPath = Path::makeRelative($drupalFinder->getComposerRoot() . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'default', $drupalRoot);
      if (strpos(file_get_contents($settingsFile), 'config_sync_directory') === FALSE) {
        $settings_string = <<<SETTINGS
\$settings['config_sync_directory'] = '$configPath';
SETTINGS;
        self::appendToFile($settingsFile, $settings_string);
        $event->getIO()->write('Added config_sync_directory to settings file');
      }
    }

    // Create the files directory and set permissions.
    $filesDirectory = $sitesDefault . DIRECTORY_SEPARATOR . 'files';
    if (!$fs->exists($filesDirectory)) {
      $oldmask = umask(0);
      $fs->mkdir($filesDirectory, 0777);
      umask($oldmask);
      $event->getIO()->write(sprintf('Created a "%s" directory with chmod 0777', $filesDirectory));
    }
  }

  /**
   * Checks if the installed version of Composer is compatible.
   *
   * Composer 1.0.0 and higher consider a `composer install` without having a
   * lock file present as equal to `composer update`. We do not ship with a lock
   * file to avoid merge conflicts downstream, meaning that if a project is
   * installed with an older version of Composer the scaffolding of Drupal will
   * not be triggered. We check this here instead of in drupal-scaffold to be
   * able to give immediate feedback to the end user, rather than failing the
   * installation after going through the lengthy process of compiling and
   * downloading the Composer dependencies.
   *
   * @see https://github.com/composer/composer/pull/5035
   */
  public static function checkComposerVersion(Event $event) {
    $composer = $event->getComposer();
    $io = $event->getIO();

    $version = $composer::VERSION;

    // The dev-channel of composer uses the git revision as version number,
    // try to the branch alias instead.
    if (preg_match('/^[0-9a-f]{40}$/i', $version)) {
      $version = $composer::BRANCH_ALIAS_VERSION;
    }

    // If Composer is installed through git we have no easy way to determine if
    // it is new enough, just display a warning.
    if ($version === '@package_version@' || $version === '@package_branch_alias_version@') {
      $io->writeError('<warning>You are running a development version of Composer. If you experience problems, please update Composer to the latest stable version.</warning>');
    }
    elseif (Comparator::lessThan($version, '1.0.0')) {
      $io->writeError('<error>Drupal-project requires Composer version 1.0.0 or higher. Please update your Composer before continuing</error>.');
      exit(1);
    }
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
      throw new \Exception(sprintf('Unable to write to the "%s" directory.', $dir), 0, NULL, $dir);
    }

    if (FALSE === @file_put_contents($filename, $content, FILE_APPEND)) {
      throw new \Exception(sprintf('Failed to write file "%s".', $filename), 0, NULL, $filename);
    }
  }

}
