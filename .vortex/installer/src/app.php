<?php

/**
 * @file
 * Main entry point for the application.
 */

declare(strict_types=1);

use DrevOps\Installer\Command\InstallCommand;
use Symfony\Component\Console\Application;

// @todo Replace '@git-tag-ci@' with '@git-tag@' once the installer is moved-out
// from the sub-directory as Box supports tag discovery from the root
// directory that contains .git.
$application = new Application('Vortex CLI installer', '@git-tag-ci@');

$command = new InstallCommand();
$application->add($command);
$application->setDefaultCommand($command->getName(), TRUE);

$application->run();
