<?php

/**
 * @file
 * YOURSITE Drupal context for Behat testing.
 */

use Drupal\DrupalExtension\Context\DrupalContext;
use IntegratedExperts\BehatSteps\D8\WatchdogTrait;
use IntegratedExperts\BehatSteps\PathTrait;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends DrupalContext {

  use PathTrait;
  use WatchdogTrait;

}
