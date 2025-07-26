#!/usr/bin/env php
<?php

/**
 * @file
 * Vortex Installer entry point.
 */

declare(strict_types=1);

use DrevOps\VortexInstaller\Command\InstallCommand;
use Symfony\Component\Console\Application;

require_once $GLOBALS['_composer_autoload_path'] ?? __DIR__ . '/vendor/autoload.php';

$application = new Application('Vortex Installer', '@vortex-installer-version@');

$command = new InstallCommand();
$application->add($command);
$application->setDefaultCommand($command->getName(), TRUE);

$application->run();
