<?php

/**
 * @file
 * YOURSITE Drupal context for Behat testing.
 */

use DrevOps\BehatSteps\SearchApiTrait;
use DrevOps\BehatSteps\WaitTrait;
use Drupal\DrupalExtension\Context\DrupalContext;
use DrevOps\BehatSteps\ContentTrait;
use DrevOps\BehatSteps\TaxonomyTrait;
use DrevOps\BehatSteps\WatchdogTrait;
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
  use SearchApiTrait;
  use WaitTrait;
  use WatchdogTrait;

}
