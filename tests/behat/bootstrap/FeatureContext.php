<?php

/**
 * @file
 * Drupal context for Behat testing.
 */

declare(strict_types=1);

use DrevOps\BehatSteps\Behat\Context\RawContext;
use DrevOps\BehatSteps\Steps\Drupal\BatchTrait;
use DrevOps\BehatSteps\Steps\Drupal\BigPipeTrait;
use DrevOps\BehatSteps\Steps\Drupal\BlockTrait;
use DrevOps\BehatSteps\Steps\Drupal\CacheTrait;
use DrevOps\BehatSteps\Steps\Drupal\ConfigOverrideTrait;
use DrevOps\BehatSteps\Steps\Drupal\ConfigTrait;
use DrevOps\BehatSteps\Steps\Drupal\ContentBlockTrait;
use DrevOps\BehatSteps\Steps\Drupal\ContentTrait;
use DrevOps\BehatSteps\Steps\Drupal\DraggableviewsTrait;
use DrevOps\BehatSteps\Steps\Drupal\DrushTrait;
use DrevOps\BehatSteps\Steps\Drupal\EckTrait;
use DrevOps\BehatSteps\Steps\Drupal\EmailTrait;
use DrevOps\BehatSteps\Steps\Drupal\EntityTrait;
use DrevOps\BehatSteps\Steps\Drupal\FileTrait;
use DrevOps\BehatSteps\Steps\Drupal\LanguageTrait;
use DrevOps\BehatSteps\Steps\Drupal\MediaTrait;
use DrevOps\BehatSteps\Steps\Drupal\MenuTrait;
use DrevOps\BehatSteps\Steps\Drupal\ModuleTrait;
use DrevOps\BehatSteps\Steps\Drupal\ParagraphsTrait;
use DrevOps\BehatSteps\Steps\Drupal\QueueTrait;
// phpcs:ignore #;< MODULE_REDIRECT
use DrevOps\BehatSteps\Steps\Drupal\RedirectTrait;
// phpcs:ignore #;> MODULE_REDIRECT
use DrevOps\BehatSteps\Steps\Drupal\SearchApiTrait;
use DrevOps\BehatSteps\Steps\Drupal\StateTrait;
use DrevOps\BehatSteps\Steps\Drupal\TaxonomyTrait;
// phpcs:ignore #;< MODULE_TESTMODE
use DrevOps\BehatSteps\Steps\Drupal\TestmodeTrait;
// phpcs:ignore #;> MODULE_TESTMODE
use DrevOps\BehatSteps\Steps\Drupal\UserTrait;
use DrevOps\BehatSteps\Steps\Drupal\WatchdogTrait;
use DrevOps\BehatSteps\Steps\Generic\AccessibilityTrait;
use DrevOps\BehatSteps\Steps\Generic\BasicAuthTrait;
use DrevOps\BehatSteps\Steps\Generic\CommandTrait;
use DrevOps\BehatSteps\Steps\Generic\CookieTrait;
use DrevOps\BehatSteps\Steps\Generic\DateTrait;
use DrevOps\BehatSteps\Steps\Generic\DiagnosticsTrait;
use DrevOps\BehatSteps\Steps\Generic\ElementTrait;
use DrevOps\BehatSteps\Steps\Generic\FieldTrait;
use DrevOps\BehatSteps\Steps\Generic\FileDownloadTrait;
use DrevOps\BehatSteps\Steps\Generic\IframeTrait;
use DrevOps\BehatSteps\Steps\Generic\JavascriptTrait;
use DrevOps\BehatSteps\Steps\Generic\JsonTrait;
use DrevOps\BehatSteps\Steps\Generic\KeyboardTrait;
use DrevOps\BehatSteps\Steps\Generic\LinkTrait;
use DrevOps\BehatSteps\Steps\Generic\MappingTrait;
use DrevOps\BehatSteps\Steps\Generic\MessageTrait;
use DrevOps\BehatSteps\Steps\Generic\MetatagTrait;
use DrevOps\BehatSteps\Steps\Generic\ModalTrait;
use DrevOps\BehatSteps\Steps\Generic\PathTrait;
use DrevOps\BehatSteps\Steps\Generic\RandomTrait;
use DrevOps\BehatSteps\Steps\Generic\RegionTrait;
use DrevOps\BehatSteps\Steps\Generic\ResponseTrait;
use DrevOps\BehatSteps\Steps\Generic\ResponsiveTrait;
use DrevOps\BehatSteps\Steps\Generic\RestTrait;
use DrevOps\BehatSteps\Steps\Generic\TableTrait;
use DrevOps\BehatSteps\Steps\Generic\WaitTrait;
use DrevOps\BehatSteps\Steps\Generic\XmlTrait;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends RawContext {

  use AccessibilityTrait;
  use BasicAuthTrait;
  use BatchTrait;
  use BigPipeTrait;
  use BlockTrait;
  use CacheTrait;
  use CommandTrait;
  use ConfigOverrideTrait;
  use ConfigTrait;
  use ContentBlockTrait;
  use ContentTrait;
  use CookieTrait;
  use DateTrait;
  use DiagnosticsTrait;
  use DraggableviewsTrait;
  use DrushTrait;
  use EckTrait;
  use ElementTrait;
  use EmailTrait;
  use EntityTrait;
  use FieldTrait;
  use FileDownloadTrait;
  use FileTrait;
  use IframeTrait;
  use JavascriptTrait;
  use JsonTrait;
  use KeyboardTrait;
  use LanguageTrait;
  use LinkTrait;
  use MappingTrait;
  use MediaTrait;
  use MenuTrait;
  use MessageTrait;
  use MetatagTrait;
  use ModalTrait;
  use ModuleTrait;
  use ParagraphsTrait;
  use PathTrait;
  use QueueTrait;
  use RandomTrait;
  // phpcs:ignore #;< MODULE_REDIRECT
  use RedirectTrait;
  // phpcs:ignore #;> MODULE_REDIRECT
  use RegionTrait;
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
