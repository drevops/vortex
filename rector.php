<?php

/**
 * @file
 * Rector configuration.
 *
 * Usage:
 * ./vendor/bin/rector process .
 *
 * @see https://github.com/palantirnet/drupal-rector/blob/main/rector.php
 */

declare(strict_types=1);

use DrupalFinder\DrupalFinder;
use DrupalRector\Set\Drupal10SetList;
use DrupalRector\Set\Drupal8SetList;
use DrupalRector\Set\Drupal9SetList;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
  $rectorConfig->sets([
    // Provided by Rector.
    SetList::TYPE_DECLARATION,
    // Provided by Drupal Rector.
    Drupal8SetList::DRUPAL_8,
    Drupal9SetList::DRUPAL_9,
    Drupal10SetList::DRUPAL_10,
  ]);

  $drupalFinder = new DrupalFinder();
  $drupalFinder->locateRoot(__DIR__);
  $drupalRoot = $drupalFinder->getDrupalRoot();
  $rectorConfig->autoloadPaths([
    $drupalRoot . '/core',
    $drupalRoot . '/modules',
    $drupalRoot . '/themes',
    $drupalRoot . '/profiles',
  ]);

  $rectorConfig->skip([
    // Dependencies.
    '*/vendor/*',
    '*/node_modules/*',
    // Core and contribs.
    '*/core/*',
    '*/modules/contrib/*',
    '*/themes/contrib/*',
    '*/profiles/contrib/*',
    // Files.
    '*/sites/simpletest/*',
    '*/sites/default/files/*',
    // Composer scripts.
    '*/scripts/composer/*',
  ]);

  $rectorConfig->fileExtensions([
    'engine',
    'inc',
    'install',
    'module',
    'php',
    'profile',
    'theme',
  ]);

  $rectorConfig->importNames(TRUE, FALSE);
  $rectorConfig->importShortClasses(FALSE);
};
