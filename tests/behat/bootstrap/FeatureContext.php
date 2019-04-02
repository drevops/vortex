<?php

/**
 * @file
 * YOURSITE Drupal context for Behat testing.
 */

use Drupal\DrupalExtension\Context\DrupalContext;
use IntegratedExperts\BehatSteps\D8\ContentTrait;
use IntegratedExperts\BehatSteps\D8\TaxonomyTrait;
use IntegratedExperts\BehatSteps\D8\WatchdogTrait;
use IntegratedExperts\BehatSteps\FieldTrait;
use IntegratedExperts\BehatSteps\PathTrait;

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
