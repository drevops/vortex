<?php

/**
 * @file
 * Main entry point for the application.
 */

use DrevOps\DevTool\Command\ScaffoldUpdateCommand;
use Symfony\Component\Console\Application;

// @codeCoverageIgnoreStart
$application = new Application();
$application->setName('DrevOps development tool');

$command = new ScaffoldUpdateCommand();
$application->add($command);

$application->run();
// @codeCoverageIgnoreEnd
