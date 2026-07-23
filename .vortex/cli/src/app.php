<?php

/**
 * @file
 * Main entry point for the application.
 */

declare(strict_types=1);

use DrevOps\VortexCli\Command\ConfigureCommand;
use DrevOps\VortexCli\Command\Doctor;
use DrevOps\VortexCli\Command\Install;
use DrevOps\VortexCli\Command\RouterCommand;
use DrevOps\VortexCli\Command\Update;
use Symfony\Component\Console\Application;

// @codeCoverageIgnoreStart
$application = new Application('Vortex', '@vortex-cli-version@');

$router = new RouterCommand();
$application->add($router);
$application->add(new ConfigureCommand());
$application->add(new Install());
$application->add(new Update());
$application->add(new Doctor());
$application->setDefaultCommand((string) $router->getName());

$application->run();
// @codeCoverageIgnoreEnd
