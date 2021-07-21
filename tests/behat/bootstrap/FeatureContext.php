<?php

/**
 * @file
 * YOURSITE Drupal context for Behat testing.
 */

use Drupal\DrupalExtension\Context\DrupalContext;
use DrevOps\BehatSteps\D8\ContentTrait;
use DrevOps\BehatSteps\D8\TaxonomyTrait;
use DrevOps\BehatSteps\D8\WatchdogTrait;
use DrevOps\BehatSteps\FieldTrait;
use DrevOps\BehatSteps\PathTrait;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends DrupalContext {

  use ContentTrait;
  use FieldTrait;
  use PathTrait;
  use TaxonomyTrait;
  use WatchdogTrait;

}
