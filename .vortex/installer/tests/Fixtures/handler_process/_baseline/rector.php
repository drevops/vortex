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
use DrupalRector\Set\DrupalSetProvider;
use Rector\CodeQuality\Rector\Class_\CompleteDynamicPropertiesRector;
use Rector\CodeQuality\Rector\ClassMethod\InlineArrayReturnAssignRector;
use Rector\CodeQuality\Rector\Empty_\SimplifyEmptyCheckOnEmptyArrayRector;
use Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector;
use Rector\CodingStyle\Rector\ClassLike\NewlineBetweenClassLikeStmtsRector;
use Rector\CodingStyle\Rector\ClassMethod\NewlineBeforeNewAssignSetRector;
use Rector\CodingStyle\Rector\Stmt\NewlineAfterStatementRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPublicMethodParameterRector;
use Rector\DeadCode\Rector\If_\RemoveAlwaysTrueIfConditionRector;
use Rector\Naming\Rector\Assign\RenameVariableToMatchMethodCallReturnTypeRector;
use Rector\Naming\Rector\ClassMethod\RenameParamToMatchTypeRector;
use Rector\Naming\Rector\ClassMethod\RenameVariableToMatchNewTypeRector;
use Rector\Naming\Rector\Foreach_\RenameForeachValueVariableToMatchExprVariableRector;
use Rector\Naming\Rector\Foreach_\RenameForeachValueVariableToMatchMethodCallReturnTypeRector;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector;
use Rector\Php81\Rector\Array_\ArrayToFirstClassCallableRector;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\YieldDataProviderRector;
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
    ArrayToFirstClassCallableRector::class,
    CatchExceptionNameMatchingTypeRector::class,
    ChangeSwitchToMatchRector::class,
    CompleteDynamicPropertiesRector::class,
    DisallowedEmptyRuleFixerRector::class,
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
    RenameForeachValueVariableToMatchExprVariableRector::class,
    RenameForeachValueVariableToMatchMethodCallReturnTypeRector::class,
    RenameParamToMatchTypeRector::class,
    RenameVariableToMatchMethodCallReturnTypeRector::class,
    RenameVariableToMatchNewTypeRector::class,
    SimplifyEmptyCheckOnEmptyArrayRector::class,
    StringClassNameToClassConstantRector::class => [
      __DIR__ . '/web/sites/default/includes/*',
    ],
    // Directories to skip.
    '*/vendor/*',
    '*/node_modules/*',
  ])
  // PHP version upgrade sets - modernizes syntax to PHP 8.4.
  // Includes all rules from PHP 5.3 through 8.4.
  ->withPhpSets(php84: TRUE)
  // Behat attribute sets - converts annotations to PHP 8 attributes.
  ->withAttributesSets(behat: TRUE)
  // Code quality improvement sets.
  ->withPreparedSets(
    codeQuality: TRUE,
    codingStyle: TRUE,
    deadCode: TRUE,
    naming: TRUE,
    privatization: TRUE,
    typeDeclarations: TRUE,
  )
  // Drupal-specific deprecation fixes. The provider binds each set to a
  // `drupal/core` version and only the sets the installed core satisfies are
  // loaded, so the set tracks core upgrades without changing this
  // configuration. Both calls are required: the provider supplies the sets,
  // `withComposerBased()` enables the group.
  ->withSetProviders(DrupalSetProvider::class)
  ->withComposerBased(drupal: TRUE)
  // Additional rules.
  ->withRules([
    DeclareStrictTypesRector::class,
    YieldDataProviderRector::class,
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
  ->withImportNames(importNames: FALSE, importDocBlockNames: FALSE);
