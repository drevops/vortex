<?php

/**
 * @file
 * Drupal context for Behat testing.
 */

declare(strict_types=1);

use DrevOps\BehatSteps\AccessibilityTrait;
use DrevOps\BehatSteps\CommandTrait;
use DrevOps\BehatSteps\CookieTrait;
use DrevOps\BehatSteps\DateTrait;
use DrevOps\BehatSteps\DiagnosticsTrait;
use DrevOps\BehatSteps\Drupal\BigPipeTrait;
use DrevOps\BehatSteps\Drupal\BlockTrait;
use DrevOps\BehatSteps\Drupal\CacheTrait;
use DrevOps\BehatSteps\Drupal\ConfigTrait;
use DrevOps\BehatSteps\Drupal\ContentBlockTrait;
use DrevOps\BehatSteps\Drupal\ContentTrait;
use DrevOps\BehatSteps\Drupal\DraggableviewsTrait;
use DrevOps\BehatSteps\Drupal\EckTrait;
use DrevOps\BehatSteps\Drupal\EmailTrait;
use DrevOps\BehatSteps\Drupal\FileTrait;
use DrevOps\BehatSteps\Drupal\MediaTrait;
use DrevOps\BehatSteps\Drupal\MenuTrait;
use DrevOps\BehatSteps\Drupal\ModuleTrait;
use DrevOps\BehatSteps\MetatagTrait;
use DrevOps\BehatSteps\Drupal\OverrideTrait;
use DrevOps\BehatSteps\Drupal\ParagraphsTrait;
use DrevOps\BehatSteps\Drupal\QueueTrait;
// phpcs:ignore #;< MODULE_REDIRECT
use DrevOps\BehatSteps\Drupal\RedirectTrait;
// phpcs:ignore #;> MODULE_REDIRECT
use DrevOps\BehatSteps\Drupal\SearchApiTrait;
use DrevOps\BehatSteps\Drupal\StateTrait;
use DrevOps\BehatSteps\Drupal\TaxonomyTrait;
// phpcs:ignore #;< MODULE_TESTMODE
use DrevOps\BehatSteps\Drupal\TestmodeTrait;
// phpcs:ignore #;> MODULE_TESTMODE
use DrevOps\BehatSteps\Drupal\UserTrait;
use DrevOps\BehatSteps\Drupal\WatchdogTrait;
use DrevOps\BehatSteps\ElementTrait;
use DrevOps\BehatSteps\FieldTrait;
use DrevOps\BehatSteps\FileDownloadTrait;
use DrevOps\BehatSteps\IframeTrait;
use DrevOps\BehatSteps\JavascriptTrait;
use DrevOps\BehatSteps\JsonTrait;
use DrevOps\BehatSteps\KeyboardTrait;
use DrevOps\BehatSteps\LinkTrait;
use DrevOps\BehatSteps\ModalTrait;
use DrevOps\BehatSteps\PathTrait;
use DrevOps\BehatSteps\ResponseTrait;
use DrevOps\BehatSteps\ResponsiveTrait;
use DrevOps\BehatSteps\RestTrait;
use DrevOps\BehatSteps\TableTrait;
use DrevOps\BehatSteps\WaitTrait;
use DrevOps\BehatSteps\XmlTrait;
use Drupal\DrupalExtension\Context\DrupalContext;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends DrupalContext {

  use AccessibilityTrait;
  use BigPipeTrait;
  use BlockTrait;
  use CacheTrait;
  use CommandTrait;
  use ConfigTrait;
  use ContentBlockTrait;
  use ContentTrait;
  use CookieTrait;
  use DateTrait;
  use DiagnosticsTrait;
  use DraggableviewsTrait;
  use EckTrait;
  use ElementTrait;
  use EmailTrait;
  use FieldTrait;
  use FileDownloadTrait;
  use FileTrait;
  use IframeTrait;
  use JavascriptTrait;
  use JsonTrait;
  use KeyboardTrait;
  use LinkTrait;
  use MediaTrait;
  use MenuTrait;
  use MetatagTrait;
  use ModalTrait;
  use ModuleTrait;
  use OverrideTrait;
  use ParagraphsTrait;
  use PathTrait;
  use QueueTrait;
  // phpcs:ignore #;< MODULE_REDIRECT
  use RedirectTrait;
  // phpcs:ignore #;> MODULE_REDIRECT
  use ResponseTrait;
  use ResponsiveTrait;
  use RestTrait;
  use SearchApiTrait;
  use StateTrait;
  use TableTrait;
  use TaxonomyTrait;
  // phpcs:ignore #;< MODULE_TESTMODE
  use TestmodeTrait;
  // phpcs:ignore #;> MODULE_TESTMODE
  use UserTrait;
  use WaitTrait;
  use WatchdogTrait;
  use XmlTrait;

}
