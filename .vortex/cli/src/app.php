<?php

/**
 * @file
 * Main entry point for the application.
 */

declare(strict_types=1);

use DrevOps\VortexCli\Command\ConfigureCommand;
use DrevOps\VortexCli\Command\Doctor;
use DrevOps\VortexCli\Command\Install;
use DrevOps\VortexCli\Command\Update;
use Symfony\Component\Console\Application;

// @codeCoverageIgnoreStart
$application = new Application('Vortex', '@vortex-cli-version@');

$command = new ConfigureCommand();
$application->add($command);
$application->add(new Install());
$application->add(new Update());
$application->add(new Doctor());
$application->setDefaultCommand((string) $command->getName());

$application->run();
// @codeCoverageIgnoreEnd
