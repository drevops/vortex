<?php

/**
 * @file
 * Trait to check that there are no watchdog messages after scenario run.
 */

use Behat\Behat\Hook\Scope\AfterScenarioScope;

/**
 * Trait WatchdogTrait.
 */
trait WatchdogTrait {

  /**
   * Start time for each scenario.
   *
   * @var int
   */
  protected $watchdogScenarioStartTime;

  /**
   * Store current time.
   *
   * @BeforeScenario
   */
  public function watchdogSetScenarioStartTime() {
    $this->watchdogScenarioStartTime = time();
  }

  /**
   * Check for errors since the scenario started.
   *
   * @AfterScenario ~@error
   */
  public function checkWatchdog(AfterScenarioScope $scope) {
    // Bypass the error checking if the scenario is expected to trigger an
    // error. Such scenarios should be tagged with "@error".
    if (in_array('error', $scope->getScenario()->getTags())) {
      return;
    }

    if (db_table_exists('watchdog')) {
      // Select all logged entries for PHP channel that appeared from the start
      // of the scenario.
      $entries = db_select('watchdog', 'w')
        ->fields('w')
        ->condition('w.type', 'php', '=')
        ->condition('w.timestamp', $this->watchdogScenarioStartTime, '>=')
        ->execute()
        ->fetchAll();
      if (!empty($entries)) {
        foreach ($entries as $k => $error) {
          if ($error->severity > WATCHDOG_WARNING) {
            unset($entries[$k]);
            continue;
          }
          $error->variables = unserialize($error->variables);
          print_r($error);
        }

        if (!empty($entries)) {
          throw new \Exception('PHP errors were logged to watchdog during this scenario.');
        }
      }
    }
  }

}
