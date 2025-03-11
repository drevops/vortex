<?php

/**
 * @file
 * Main entry point for the application.
 */

declare(strict_types=1);

use DrevOps\Installer\Command\InstallCommand;
use Symfony\Component\Console\Application;

$application = new Application('Vortex CLI installer', '@git-tag-ci@');

$command = new InstallCommand();
$application->add($command);
$application->setDefaultCommand($command->getName(), TRUE);

$application->run();
