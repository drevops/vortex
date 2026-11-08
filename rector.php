<?php

/**
 * @file
 * Rector configuration.
 *
 * Rector automatically refactors PHP code to:
 * - Upgrade deprecated Drupal APIs
 * - Modernize PHP syntax to leverage new language features
 * - Improve code quality and maintainability
 *
 * @see https://github.com/palantirnet/drupal-rector
 * @see https://getrector.com/documentation
 * @see https://getrector.com/documentation/set-lists
 */

declare(strict_types=1);

use Rector\CodeQuality\Rector\ClassMethod\InlineArrayReturnAssignRector;
use Rector\CodeQuality\Rector\Empty_\SimplifyEmptyCheckOnEmptyArrayRector;
use Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector;
use Rector\CodingStyle\Rector\ClassLike\NewlineBetweenClassLikeStmtsRector;
use Rector\CodingStyle\Rector\ClassMethod\NewlineBeforeNewAssignSetRector;
use Rector\CodingStyle\Rector\Stmt\NewlineAfterStatementRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPublicMethodParameterRector;
use Rector\DeadCode\Rector\If_\RemoveAlwaysTrueIfConditionRector;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector;
use Rector\Php81\Rector\Array_\ArrayToFirstClassCallableRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\YieldDataProviderRector;
use Rector\Privatization\Rector\ClassMethod\PrivatizeFinalClassMethodRector;
use Rector\Privatization\Rector\MethodCall\PrivatizeLocalGetterToPropertyRector;
use Rector\Privatization\Rector\Property\PrivatizeFinalClassPropertyRector;

return RectorConfig::configure()
  ->withPaths([
    __DIR__ . '/web/modules/custom',
    __DIR__ . '/web/themes/custom',
    __DIR__ . '/web/sites/default/settings.php',
    __DIR__ . '/web/sites/default/includes',
    __DIR__ . '/tests',
  ])
  ->withSkip([
    // Specific rules to skip based on project coding standards.
    ArrayToFirstClassCallableRector::class,
    CatchExceptionNameMatchingTypeRector::class,
    ChangeSwitchToMatchRector::class,
    InlineArrayReturnAssignRector::class,
    NewlineAfterStatementRector::class,
    NewlineBeforeNewAssignSetRector::class,
    NewlineBetweenClassLikeStmtsRector::class,
    PrivatizeFinalClassMethodRector::class,
    PrivatizeFinalClassPropertyRector::class,
    PrivatizeLocalGetterToPropertyRector::class,
    RemoveAlwaysTrueIfConditionRector::class,
    RemoveUnusedPublicMethodParameterRector::class => [
      __DIR__ . '/web/modules/custom/*/src/Hook/*',
      __DIR__ . '/web/themes/custom/*/src/Hook/*',
    ],
    SimplifyEmptyCheckOnEmptyArrayRector::class,
    StringClassNameToClassConstantRector::class => [
      __DIR__ . '/web/sites/default/includes/*',
    ],
    // Directories to skip.
    '*/vendor/*',
    '*/node_modules/*',
  ])
  // PHP version upgrade sets. Called without an argument, the target version
  // comes from `composer.json`, so the sets follow the project's PHP version.
  ->withPhpSets()
  #;< TOOL_BEHAT
  // Behat attribute sets - converts annotations to PHP 8 attributes.
  ->withAttributesSets(behat: TRUE)
  #;> TOOL_BEHAT
  // Code quality improvement sets.
  ->withPreparedSets(
    codeQuality: TRUE,
    codingStyle: TRUE,
    deadCode: TRUE,
    privatization: TRUE,
    typeDeclarations: TRUE,
  )
  // Drupal deprecation fixes for the installed `drupal/core` version. The sets
  // carry the autoload paths and the file extensions Drupal executes PHP
  // from, so this file declares neither.
  ->withComposerBased(drupal: TRUE)
  #;< TOOL_PHPUNIT
  // Additional rules.
  ->withRules([
    YieldDataProviderRector::class,
  ])
  #;> TOOL_PHPUNIT
  // Import configuration.
  ->withImportNames(importNames: FALSE, importDocBlockNames: FALSE);
