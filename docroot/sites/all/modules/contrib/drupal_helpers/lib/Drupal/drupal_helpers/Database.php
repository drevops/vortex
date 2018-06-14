<?php

namespace Drupal\drupal_helpers;

/**
 * Class Database.
 */
class Database {

  /**
   * Import DB dumb one query at a time.
   *
   * @param string $filename
   *   Full path to filename.
   */
  public static function importDump($filename) {
    $fp = fopen($filename, 'r');

    if (!$fp) {
      throw new \Exception(strtr('Unable to open file @filename', ['@filename' => $filename]));
    }

    $query = '';
    while ($line = fgets($fp, 1024000)) {
      if (substr($line, 0, 2) == '--' || empty(trim($line))) {
        continue;
      }

      $query .= $line;
      if (substr(trim($query), -1) == ';') {
        db_query($query);
        $query = '';
      }
    }

    fclose($fp);
  }

}
