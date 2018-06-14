<?php

namespace Drupal\drupal_helpers;

/**
 * Class General.
 *
 * @package Drupal\drupal_helpers
 */
class General {

  /**
   * Helper to print messages.
   *
   * Prints to stdout if using drush, or drupal_set_message() if the web UI.
   *
   * @param string $message
   *   String containing message.
   * @param string $prefix
   *   Prefix to be used for messages when called through CLI.
   *   Defaults to '-- '.
   * @param int $indent
   *   Indent for messages. Defaults to 2.
   */
  public static function messageSet($message, $prefix = '-- ', $indent = 2) {
    if (function_exists('drush_print')) {
      drush_print(((string) $prefix) . html_entity_decode($message), $indent);
    }
    else {
      drupal_set_message($message);
    }
  }

}
