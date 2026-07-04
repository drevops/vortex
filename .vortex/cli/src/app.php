<?php

/**
 * @file
 * Main entry point for the application.
 */

declare(strict_types=1);

use Symfony\Component\Console\Application;
use DrevOps\VortexCli\Command\JokeCommand;
use DrevOps\VortexCli\Command\SayHelloCommand;

// @codeCoverageIgnoreStart
$application = new Application('YourProject', '@vortex-cli-version@');

$command = new JokeCommand();
$application->add($command);
$application->setDefaultCommand((string) $command->getName());

$command = new SayHelloCommand();
$application->add($command);

$application->run();
// @codeCoverageIgnoreEnd
