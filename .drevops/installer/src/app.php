<?php

/**
 * @file
 * Main entry point for the application.
 */

use DrevOps\Installer\Command\InstallCommand;
use Symfony\Component\Console\Application;

$application = new Application();

$command = new InstallCommand();
$application->add($command);
$application->setDefaultCommand($command->getName(), TRUE);

$application->run();
