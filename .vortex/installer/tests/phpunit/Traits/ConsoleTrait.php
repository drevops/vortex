<?php

declare(strict_types=1);

namespace DrevOps\Installer\Tests\Traits;

use PHPUnit\Framework\AssertionFailedError;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Trait ConsoleTrait.
 *
 * Helpers to work with Console.
 */
trait ConsoleTrait {

  use ReflectionTrait;

  /**
   * Application tester.
   */
  protected ApplicationTester $tester;

  /**
   * Initialize application tester.
   *
   * @param string|object $object_or_class
   *   Command class or object.
   * @param bool $is_single_command
   *   Is single command. Defaults to TRUE.
   */
  protected function consoleInitApplicationTester(string|object $object_or_class, bool $is_single_command = TRUE): void {
    $application = new Application();

    $instance = is_object($object_or_class) ? $object_or_class : new $object_or_class();
    if (!$instance instanceof Command) {
      throw new \InvalidArgumentException('The provided object is not an instance of Command');
    }

    $application->add($instance);

    $name = $instance->getName();
    if (empty($name)) {
      $ret = $this->getProtectedValue($instance, 'defaultName');
      if (!empty($ret) || !is_string($ret)) {
        throw new \InvalidArgumentException('The provided object does not have a valid name');
      }
      $name = $ret;
    }

    $application->setDefaultCommand($name, $is_single_command);

    $application->setAutoExit(FALSE);
    $application->setCatchExceptions(FALSE);

    $this->tester = new ApplicationTester($application);
  }

  /**
   * Run console application.
   *
   * @param array<string, string> $input
   *   Input arguments.
   * @param array<string, string> $options
   *   Options.
   * @param bool $expect_fail
   *   Whether a failure is expected. Defaults to FALSE.
   *
   * @return string
   *   Run output (stdout or stderr).
   */
  protected function consoleApplicationRun(array $input = [], array $options = [], bool $expect_fail = FALSE): string {
    $options += ['capture_stderr_separately' => TRUE];

    $output = '';
    try {
      $this->tester->run($input, $options);
      $output = $this->tester->getDisplay();

      if ($this->tester->getStatusCode() !== 0) {
        throw new \Exception(sprintf("Application exited with non-zero code.\nThe output was:\n%s\nThe error output was:\n%s", $this->tester->getDisplay(), $this->tester->getErrorOutput()));
      }

      if ($expect_fail) {
        throw new AssertionFailedError(sprintf("Application exited successfully but should not.\nThe output was:\n%s\nThe error output was:\n%s", $this->tester->getDisplay(), $this->tester->getErrorOutput()));
      }
    }
    catch (\RuntimeException $exception) {
      if (!$expect_fail) {
        throw new AssertionFailedError('Application exited with an error:' . PHP_EOL . $exception->getMessage());
      }
      $output = $exception->getMessage();
    }
    catch (\Exception $exception) {
      if (!$expect_fail) {
        throw new AssertionFailedError('Application exited with an error:' . PHP_EOL . $exception->getMessage());
      }
    }

    return $output;
  }

}
