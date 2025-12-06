#!/usr/bin/env php
<?php

/**
 * @file
 * Vortex Installer entry point.
 */

declare(strict_types=1);

use DrevOps\VortexInstaller\Command\BuildCommand;
use DrevOps\VortexInstaller\Command\CheckRequirementsCommand;
use DrevOps\VortexInstaller\Command\InstallCommand;
use Symfony\Component\Console\Application;

require_once $GLOBALS['_composer_autoload_path'] ?? __DIR__ . '/vendor/autoload.php';

$application = new Application('Vortex Installer', getenv('VORTEX_INSTALLER_VERSION') ?: '@vortex-installer-version@');

$application->add(new InstallCommand());
$application->add(new CheckRequirementsCommand());
$application->add(new BuildCommand());

$application->setDefaultCommand('install');

$application->run();
