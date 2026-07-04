<?php

/**
 * @file
 * Main entry point for the application.
 */

declare(strict_types=1);

use Symfony\Component\Console\Application;

// @codeCoverageIgnoreStart
$application = new Application('Vortex', '@vortex-cli-version@');

$application->run();
// @codeCoverageIgnoreEnd
