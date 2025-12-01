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

use DrupalFinder\DrupalFinderComposerRuntime;
use DrupalRector\Set\Drupal9SetList;
use DrupalRector\Set\Drupal10SetList;
use Rector\CodeQuality\Rector\Class_\CompleteDynamicPropertiesRector;
use Rector\CodeQuality\Rector\ClassMethod\InlineArrayReturnAssignRector;
use Rector\CodeQuality\Rector\Empty_\SimplifyEmptyCheckOnEmptyArrayRector;
use Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector;
use Rector\CodingStyle\Rector\ClassLike\NewlineBetweenClassLikeStmtsRector;
use Rector\CodingStyle\Rector\ClassMethod\NewlineBeforeNewAssignSetRector;
use Rector\CodingStyle\Rector\FuncCall\CountArrayToEmptyArrayComparisonRector;
use Rector\CodingStyle\Rector\FunctionLike\FunctionLikeToFirstClassCallableRector;
use Rector\CodingStyle\Rector\Stmt\NewlineAfterStatementRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\If_\RemoveAlwaysTrueIfConditionRector;
use Rector\Naming\Rector\ClassMethod\RenameParamToMatchTypeRector;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector;
use Rector\Php81\Rector\Array_\FirstClassCallableRector;
use Rector\Naming\Rector\Assign\RenameVariableToMatchMethodCallReturnTypeRector;
use Rector\Naming\Rector\ClassMethod\RenameVariableToMatchNewTypeRector;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\Privatization\Rector\ClassMethod\PrivatizeFinalClassMethodRector;
use Rector\Privatization\Rector\MethodCall\PrivatizeLocalGetterToPropertyRector;
use Rector\Privatization\Rector\Property\PrivatizeFinalClassPropertyRector;
use Rector\Strict\Rector\Empty_\DisallowedEmptyRuleFixerRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;

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
    AddOverrideAttributeToOverriddenMethodsRector::class,
    CatchExceptionNameMatchingTypeRector::class,
    ChangeSwitchToMatchRector::class,
    CompleteDynamicPropertiesRector::class,
    CountArrayToEmptyArrayComparisonRector::class,
    DisallowedEmptyRuleFixerRector::class,
    FirstClassCallableRector::class,
    FunctionLikeToFirstClassCallableRector::class,
    InlineArrayReturnAssignRector::class,
    NewlineAfterStatementRector::class,
    NewlineBeforeNewAssignSetRector::class,
    NewlineBetweenClassLikeStmtsRector::class,
    PrivatizeFinalClassMethodRector::class,
    PrivatizeFinalClassPropertyRector::class,
    PrivatizeLocalGetterToPropertyRector::class,
    RemoveAlwaysTrueIfConditionRector::class,
    RenameParamToMatchTypeRector::class,
    RenameVariableToMatchMethodCallReturnTypeRector::class,
    RenameVariableToMatchNewTypeRector::class,
    SimplifyEmptyCheckOnEmptyArrayRector::class,
    StringClassNameToClassConstantRector::class => [
      __DIR__ . '/web/sites/default/includes/**/*',
    ],
    // Directories to skip.
    '*/vendor/*',
    '*/node_modules/*',
  ])
  // PHP version upgrade sets - modernizes syntax to PHP 8.3.
  // Includes all rules from PHP 5.3 through 8.3.
  ->withPhpSets(php83: true)
  // Code quality improvement sets.
  ->withPreparedSets(
    codeQuality: true,
    codingStyle: true,
    deadCode: true,
    naming: true,
    privatization: true,
    typeDeclarations: true,
  )
  // Drupal-specific deprecation fixes.
  ->withSets([
    Drupal9SetList::DRUPAL_9,
    Drupal10SetList::DRUPAL_10,
  ])
  // Additional rules.
  ->withRules([
    DeclareStrictTypesRector::class,
  ])
  // Configure Drupal autoloading.
  ->withAutoloadPaths((function (): array {
    $drupalFinder = new DrupalFinderComposerRuntime();
    $drupalRoot = $drupalFinder->getDrupalRoot();

    return [
      $drupalRoot . '/core',
      $drupalRoot . '/modules',
      $drupalRoot . '/themes',
      $drupalRoot . '/profiles',
    ];
  })())
  // Drupal file extensions.
  ->withFileExtensions([
    'php',
    'module',
    'install',
    'profile',
    'theme',
    'inc',
    'engine',
  ])
  // Import configuration.
  ->withImportNames(importNames: false, importDocBlockNames: false);
