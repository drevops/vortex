<?php

namespace Drupal\your_site_core;

use Drush\Drush;
use Drush\Log\LogLevel;

/**
 * Class to register and execute post config import commands.
 *
 * @package Drupal\your_site_core
 *
 * @see https://www.drupal.org/project/drupal/issues/2901418
 */
class PostConfigImportUpdateHelper {

  /**
   * Helper to print messages.
   *
   * Prints to stdout if using drush, or drupal_set_message() if the web UI.
   *
   * It is important to note that using \Drupal::messenger() when running Drush
   * commands have side effects where messages are displayed only after the
   * command has finished rather then during the command run.
   *
   * @param string $message
   *   String containing message.
   * @param string $prefix
   *   Prefix to be used for messages when called through CLI.
   *   Defaults to '-- '.
   * @param int $indent
   *   Indent for messages. Defaults to 2.
   */
  public static function log($message, $prefix = '-- ', $indent = 2) {
    if (class_exists('\Drush\Drush')) {
      /** @var \Drush\Log\Logger $logger */
      $logger = Drush::getContainer()->get('logger');
      $logger->log(LogLevel::SUCCESS, str_pad(((string) $prefix) . html_entity_decode($message), $indent, ' ', STR_PAD_LEFT));
    }
    elseif (PHP_SAPI === 'cli') {
      print str_pad(((string) $prefix) . html_entity_decode($message), $indent, ' ', STR_PAD_LEFT) . PHP_EOL;
    }
    else {
      $messenger = \Drupal::messenger();
      if (isset($message)) {
        $messenger->addMessage($message);
      }
    }
  }

  /**
   * Registers update hook to run after configuration import.
   *
   * This is a lightweight implementation of hook_post_config_import_NAME()
   * organised as a temporary storage of registered updates to be run after
   * configuration import.
   *
   * Note that there is no integration with any config import events, and
   * running registered updates via static::runPostConfigImportUpdates()
   * must be done explicitly after configuration import.
   *
   * @code
   * function mymodule_post_update_some_content() {
   *   $added = static::registerPostConfigImportUpdate();
   *   if ($added) {
   *     return TRUE;
   *   }
   *
   *   ...
   * }
   * @endcode
   *
   * @param bool $verbose
   *   Optional flag to output verbose message. Defaults to TRUE.
   *
   * @return bool
   *   TRUE if update was added to the registry, FALSE otherwise. Note that
   *   callers (update function) can use this status to exist at the very start
   *   of execution.
   *
   * @see static::runPostConfigImportUpdates()
   * @see https://www.drupal.org/project/drupal/issues/2901418
   */
  public static function registerPostConfigImportUpdate($verbose = TRUE) {
    $trace = debug_backtrace(FALSE, 2);
    $file = $trace[0]['file'];
    $update = $trace[1]['function'];

    $updates = \Drupal::state()->get('post_config_import_update', []);

    /** @var \Drupal\Core\Update\UpdateRegistry $post_update_registry */
    $post_update_registry = \Drupal::service('update.post_update_registry');
    $pending_updates = $post_update_registry->getPendingUpdateFunctions();

    // Add only if not added to own registry and is considered as pending by
    // core.
    if (!in_array($update, $updates) && in_array($update, $pending_updates)) {
      $updates[$update] = ['function' => $update, 'file' => $file];
      \Drupal::state()->set('post_config_import_update', $updates);
      if ($verbose) {
        self::log(sprintf('Registered post config import update "%s"', $update));
      }

      return TRUE;
    }

    return FALSE;
  }

  /**
   * Run post config import updates.
   *
   * This is a sister function to
   * static::registerPostConfigImportUpdate(), which is responsible for
   * running previously stored update functions.
   *
   * Note that it simply calls update hooks and does not implement any exception
   * handling - it relies on update hooks to throw correct update exceptions.
   *
   * @see static::registerPostConfigImportUpdate()
   */
  public static function runPostConfigImportUpdates() {
    self::log('Started post config import updates');

    while ($updates = \Drupal::state()->get('post_config_import_update')) {
      $update = array_shift($updates);

      if (!is_readable($update['file'])) {
        throw new \Exception(sprintf('File "%s" for registered update is not readable', $update['file']));
      }

      require_once $update['file'];

      if (!function_exists($update['function'])) {
        throw new \Exception(sprintf('Function "%s" for registered update does not exist', $update['function']));
      }

      self::log(sprintf('Started post config import update "%s"', $update['function']));
      call_user_func($update['function']);
      self::log(sprintf('Finished post config import update "%s"', $update['function']));

      \Drupal::state()->set('post_config_import_update', $updates);
    }

    self::log('Finished post config import updates');
  }

}
