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

use Rector\CodeQuality\Rector\Class_\CompleteDynamicPropertiesRector;
use Rector\CodeQuality\Rector\ClassMethod\InlineArrayReturnAssignRector;
use Rector\CodeQuality\Rector\Empty_\SimplifyEmptyCheckOnEmptyArrayRector;
use Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector;
use Rector\CodingStyle\Rector\ClassMethod\NewlineBeforeNewAssignSetRector;
use Rector\CodingStyle\Rector\FuncCall\CountArrayToEmptyArrayComparisonRector;
use Rector\CodingStyle\Rector\Stmt\NewlineAfterStatementRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\If_\RemoveAlwaysTrueIfConditionRector;
use Rector\DeadCode\Rector\Plus\RemoveDeadZeroAndOneOperationRector;
use Rector\Naming\Rector\Assign\RenameVariableToMatchMethodCallReturnTypeRector;
use Rector\Naming\Rector\ClassMethod\RenameParamToMatchTypeRector;
use Rector\Naming\Rector\ClassMethod\RenameVariableToMatchNewTypeRector;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector;
use Rector\Privatization\Rector\ClassMethod\PrivatizeFinalClassMethodRector;
use Rector\Privatization\Rector\MethodCall\PrivatizeLocalGetterToPropertyRector;
use Rector\Privatization\Rector\Property\PrivatizeFinalClassPropertyRector;
use Rector\Strict\Rector\Empty_\DisallowedEmptyRuleFixerRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;

return RectorConfig::configure()
  ->withPaths([
    __DIR__ . '/check-no-exit.php',
    __DIR__ . '/src/notify',
    __DIR__ . '/src/notify-email',
    __DIR__ . '/src/notify-github',
    __DIR__ . '/src/notify-jira',
    __DIR__ . '/src/notify-newrelic',
    __DIR__ . '/src/notify-slack',
    __DIR__ . '/src/notify-webhook',
    __DIR__ . '/playground',
    __DIR__ . '/tests',
  ])
  ->withSkip([
    // Specific rules to skip based on project coding standards.
    CatchExceptionNameMatchingTypeRector::class,
    ChangeSwitchToMatchRector::class,
    CompleteDynamicPropertiesRector::class,
    CountArrayToEmptyArrayComparisonRector::class,
    DisallowedEmptyRuleFixerRector::class,
    InlineArrayReturnAssignRector::class,
    NewlineAfterStatementRector::class,
    NewlineBeforeNewAssignSetRector::class,
    PrivatizeFinalClassMethodRector::class,
    PrivatizeFinalClassPropertyRector::class,
    PrivatizeLocalGetterToPropertyRector::class,
    RemoveAlwaysTrueIfConditionRector::class,
    RemoveDeadZeroAndOneOperationRector::class,
    RenameParamToMatchTypeRector::class,
    RenameVariableToMatchMethodCallReturnTypeRector::class,
    RenameVariableToMatchNewTypeRector::class,
    SimplifyEmptyCheckOnEmptyArrayRector::class,
    StringClassNameToClassConstantRector::class,
    // Directories to skip.
    '*/vendor/*',
    '*/node_modules/*',
  ])
  // PHP version upgrade sets - modernizes syntax to PHP 8.3.
  // Includes all rules from PHP 5.3 through 8.3.
  ->withPhpSets(php82: TRUE)
  // Code quality improvement sets.
  ->withPreparedSets(
    codeQuality: TRUE,
    codingStyle: TRUE,
    deadCode: TRUE,
    naming: TRUE,
    privatization: TRUE,
    typeDeclarations: TRUE,
  )
  // Additional rules.
  ->withRules([
    DeclareStrictTypesRector::class,
  ])
  ->withFileExtensions([
    'php',
    'inc',
  ])
  // Import configuration.
  ->withImportNames(importNames: FALSE, importDocBlockNames: FALSE);
