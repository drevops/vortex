<?php

/**
 * @file
 * MYSITE Drupal context for Behat testing.
 */

use Drupal\DrupalExtension\Context\DrupalContext;
use IntegratedExperts\BehatSteps\D8\WatchdogTrait;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends DrupalContext {

  use WatchdogTrait;

}
