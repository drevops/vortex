<?php

/**
 * @file
 * Drupal context for Behat testing.
 */

declare(strict_types=1);

use DrevOps\BehatSteps\ContentTrait;
use DrevOps\BehatSteps\FieldTrait;
use DrevOps\BehatSteps\FileTrait;
use DrevOps\BehatSteps\PathTrait;
use DrevOps\BehatSteps\SearchApiTrait;
use DrevOps\BehatSteps\TaxonomyTrait;
use DrevOps\BehatSteps\WaitTrait;
use DrevOps\BehatSteps\WatchdogTrait;
use Drupal\DrupalExtension\Context\DrupalContext;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends DrupalContext {

  use ContentTrait;
  use FieldTrait;
  use FileTrait;
  use PathTrait;
  use TaxonomyTrait;
  use SearchApiTrait;
  use WaitTrait;
  use WatchdogTrait;

}
