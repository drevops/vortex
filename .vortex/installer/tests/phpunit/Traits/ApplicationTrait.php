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
trait ApplicationTrait {

  use ReflectionTrait;

  /**
   * Application tester.
   */
  protected static ApplicationTester $tester;

  /**
   * Initialize application tester.
   *
   * @param string|object $object_or_class
   *   Command class or object.
   * @param bool $is_single_command
   *   Is single command. Defaults to TRUE.
   */
  protected static function applicationInit(string|object $object_or_class, bool $is_single_command = TRUE): ApplicationTester {
    $application = new Application();

    $instance = is_object($object_or_class) ? $object_or_class : new $object_or_class();
    if (!$instance instanceof Command) {
      throw new \InvalidArgumentException('The provided object is not an instance of Command');
    }

    $application->add($instance);

    $name = $instance->getName();
    if (empty($name)) {
      $ret = self::getProtectedValue($instance, 'defaultName');
      if (!empty($ret) || !is_string($ret)) {
        throw new \InvalidArgumentException('The provided object does not have a valid name');
      }
      $name = $ret;
    }

    $application->setDefaultCommand($name, $is_single_command);

    $application->setAutoExit(FALSE);
    $application->setCatchExceptions(FALSE);

    return new ApplicationTester($application);
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
  protected static function applicationRun(array $input = [], array $options = [], bool $expect_fail = FALSE): string {
    $options += ['capture_stderr_separately' => TRUE];

    $output = '';
    try {
      static::$tester->run($input, $options);
      $output = static::$tester->getDisplay();

      if (static::$tester->getStatusCode() !== 0) {
        throw new \Exception(sprintf("Application exited with non-zero code.\nThe output was:\n%s\nThe error output was:\n%s", static::$tester->getDisplay(), static::$tester->getErrorOutput()));
      }

      if ($expect_fail) {
        throw new AssertionFailedError(sprintf("Application exited successfully but should not.\nThe output was:\n%s\nThe error output was:\n%s", static::$tester->getDisplay(), static::$tester->getErrorOutput()));
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

  /**
   * Assert successful tester output.
   *
   * @param string|array $strings
   *   The expected strings.
   */
  protected function assertApplicationSuccessOutputContains(string|array $strings): void {
    $strings = is_array($strings) ? $strings : [$strings];

    if (static::$tester->getStatusCode() !== 0) {
      $this->fail(static::$tester->getDisplay());
    }
    $this->assertSame(0, static::$tester->getStatusCode(), sprintf("The Composer command should have completed successfully:\n%s", static::$tester->getInput()->__toString()));

    $output = static::$tester->getDisplay(TRUE);
    foreach ($strings as $string) {
      $this->assertStringContainsString($string, $output);
    }
  }

}
