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

use Rector\CodeQuality\Rector\ClassMethod\InlineArrayReturnAssignRector;
use Rector\CodeQuality\Rector\Empty_\SimplifyEmptyCheckOnEmptyArrayRector;
use Rector\CodeQuality\Rector\FunctionLike\SimplifyUselessVariableRector;
use Rector\CodingStyle\Rector\ClassMethod\NewlineBeforeNewAssignSetRector;
use Rector\CodingStyle\Rector\FuncCall\CountArrayToEmptyArrayComparisonRector;
use Rector\CodingStyle\Rector\Stmt\NewlineAfterStatementRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\If_\RemoveAlwaysTrueIfConditionRector;
use Rector\PHPUnit\AnnotationsToAttributes\Rector\Class_\CoversAnnotationWithValueToAttributeRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Strict\Rector\Empty_\DisallowedEmptyRuleFixerRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;

return static function (RectorConfig $config): void {
  $config->paths([
    __DIR__ . '/installer.php',
    __DIR__ . '/src',
    __DIR__ . '/tests/Functional',
    __DIR__ . '/tests/Unit',
    __DIR__ . '/tests/Traits',
  ]);

  $config->sets([
    SetList::PHP_82,
    SetList::PHP_83,
    SetList::CODE_QUALITY,
    SetList::CODING_STYLE,
    SetList::DEAD_CODE,
    SetList::INSTANCEOF,
    SetList::TYPE_DECLARATION,
    PHPUnitSetList::PHPUNIT_100,
  ]);

  $config->rule(DeclareStrictTypesRector::class);
  $config->rule(CoversAnnotationWithValueToAttributeRector::class);

  $config->skip([
    // Rules added by Rector's rule sets.
    CountArrayToEmptyArrayComparisonRector::class,
    DisallowedEmptyRuleFixerRector::class,
    InlineArrayReturnAssignRector::class,
    NewlineAfterStatementRector::class,
    NewlineBeforeNewAssignSetRector::class,
    RemoveAlwaysTrueIfConditionRector::class,
    SimplifyEmptyCheckOnEmptyArrayRector::class,
    SimplifyUselessVariableRector::class,
    // Dependencies.
    '*/vendor/*',
    '*/node_modules/*',
  ]);

  $config->fileExtensions([
    'php',
    'inc',
  ]);

  $config->importNames(TRUE, FALSE);
  $config->importShortClasses(FALSE);
};
