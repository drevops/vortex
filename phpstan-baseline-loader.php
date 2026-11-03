<?php

/**
 * @file
 * PHPStan configuration that loads the baseline when the project has one.
 *
 * A missing file listed in 'includes' of 'phpstan.neon' stops the analysis,
 * so the baseline is included from here only when it exists.
 *
 * @see https://phpstan.org/user-guide/baseline
 */

declare(strict_types=1);

$baseline = __DIR__ . '/phpstan-baseline.neon';

return ['includes' => file_exists($baseline) ? [$baseline] : []];
