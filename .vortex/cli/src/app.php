<?php

/**
 * @file
 * Main entry point for the application.
 */

declare(strict_types=1);

use DrevOps\VortexCli\Command\Customize;
use DrevOps\VortexCli\Command\Install;
use Symfony\Component\Console\Application;

// @codeCoverageIgnoreStart
$application = new Application('Vortex', '@vortex-cli-version@');

$command = new Customize();
$application->add($command);
$application->add(new Install());
$application->setDefaultCommand((string) $command->getName());

$application->run();
// @codeCoverageIgnoreEnd
