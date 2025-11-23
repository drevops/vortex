<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests for notify-email script.
 *
 * @phpcs:disable Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
 */
#[RunTestsInSeparateProcesses]
#[Group('notify')]
class NotifyEmailTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();

    $this->envSetMultiple([
      'VORTEX_NOTIFY_EMAIL_PROJECT' => 'test-project',
      'VORTEX_NOTIFY_EMAIL_FROM' => 'noreply@example.com',
      'VORTEX_NOTIFY_EMAIL_RECIPIENTS' => 'to@example.com',
      'VORTEX_NOTIFY_EMAIL_LABEL' => 'main',
      'VORTEX_NOTIFY_EMAIL_ENVIRONMENT_URL' => 'https://example.com',
      'VORTEX_NOTIFY_EMAIL_LOGIN_URL' => 'https://example.com/login',
      'VORTEX_NOTIFY_EMAIL_EVENT' => 'post_deployment',
    ]);
  }

  public function testSuccessfulNotificationWithSendmail(): void {
    // Mock exec() calls for command checking and email sending.
    $exec_calls = 0;
    $exec = $this->getFunctionMock('DrevOps\\VortexTooling', 'exec');
    $exec
      ->expects($this->any())
      ->willReturnCallback(function ($cmd, &$output, &$return_var) use (&$exec_calls): void {
        $exec_calls++;
        if ($exec_calls === 1 && str_contains($cmd, 'sendmail')) {
          // First call: check for sendmail command.
          $return_var = 0;
          return;
        }
        elseif ($exec_calls === 2) {
          // Second call: send email via sendmail.
          $return_var = 0;
          return;
        }
        $return_var = 1;
      });

    $output = $this->runScript('src/notify-email');

    $this->assertStringContainsString('Started email notification', $output);
    $this->assertStringContainsString('Using sendmail command', $output);
    $this->assertStringContainsString('Project        : test-project', $output);
    $this->assertStringContainsString('From           : noreply@example.com', $output);
    $this->assertStringContainsString('Recipients     : to@example.com', $output);

    $this->assertStringContainsString('Sending email notification', $output);
    $this->assertStringContainsString('Email notification sent successfully to 1 recipient(s)', $output);
    $this->assertStringContainsString('Finished email notification', $output);
  }

  public function testSuccessfulNotificationWithMailCommand(): void {
    // Mock exec() to show sendmail not available, but mail is.
    $exec_calls = 0;
    $exec = $this->getFunctionMock('DrevOps\\VortexTooling', 'exec');
    $exec
      ->expects($this->any())
      ->willReturnCallback(function ($cmd, &$output, &$return_var) use (&$exec_calls): void {
        $exec_calls++;
        if ($exec_calls === 1 && str_contains($cmd, 'sendmail')) {
          // First call: sendmail not available.
          $return_var = 1;
          return;
        }
        elseif ($exec_calls === 2 && str_contains($cmd, 'command -v mail')) {
          // Second call: check for mail command.
          $return_var = 0;
          return;
        }
        elseif ($exec_calls === 3) {
          // Third call: send email via mail.
          $return_var = 0;
          return;
        }
        $return_var = 1;
      });

    $output = $this->runScript('src/notify-email');

    $this->assertStringContainsString('Sending email notification', $output);
    $this->assertStringContainsString('Email notification sent successfully to 1 recipient(s)', $output);
    $this->assertStringContainsString('Finished email notification', $output);
  }

  public function testSuccessfulNotificationWithMultipleRecipients(): void {
    $this->envSet('VORTEX_NOTIFY_EMAIL_RECIPIENTS', 'to1@example.com|Jane Doe, to2@example.com|John Doe');

    // Mock exec() calls for command checking and email sending.
    $exec_calls = 0;
    $exec = $this->getFunctionMock('DrevOps\\VortexTooling', 'exec');
    $exec
      ->expects($this->any())
      ->willReturnCallback(function ($cmd, &$output, &$return_var) use (&$exec_calls): void {
        $exec_calls++;
        if ($exec_calls === 1 && str_contains($cmd, 'sendmail')) {
          $return_var = 0;
          return;
        }
        // Calls 2 and 3: send emails.
        $return_var = 0;
      });

    $output = $this->runScript('src/notify-email');

    $this->assertStringContainsString('Recipients     : to1@example.com|Jane Doe, to2@example.com|John Doe', $output);
    $this->assertStringContainsString('Email notification sent successfully to 2 recipient(s)', $output);
    $this->assertStringContainsString('Finished email notification', $output);
  }

  public function testSuccessfulNotificationWithCustomMessage(): void {
    $this->envSet('VORTEX_NOTIFY_EMAIL_MESSAGE', 'Custom deployment of %project% to %label% at %timestamp%');

    $exec = $this->getFunctionMock('DrevOps\\VortexTooling', 'exec');
    $exec
      ->expects($this->any())
      ->willReturnCallback(function ($cmd, &$output, &$return_var): void {
        $return_var = 0;
      });

    $output = $this->runScript('src/notify-email');

    $this->assertStringContainsString('Custom deployment of test-project to main', $output);
    $this->assertStringContainsString('Finished email notification', $output);
  }

  public function testPreDeploymentEventSkipped(): void {
    $this->envSet('VORTEX_NOTIFY_EMAIL_EVENT', 'pre_deployment');

    $this->runScriptEarlyPass('src/notify-email', 'Skipping email notification for pre_deployment event');
  }

  public function testFailureWhenNoMailCommandAvailable(): void {
    // Mock exec() to show neither command is available.
    $exec = $this->getFunctionMock('DrevOps\\VortexTooling', 'exec');
    $exec
      ->expects($this->any())
      ->willReturnCallback(function ($cmd, &$output, &$return_var): void {
        $return_var = 1;
      });

    $this->runScriptError('src/notify-email', 'Neither "mail" nor "sendmail" commands are available');
  }

  #[DataProvider('dataProviderMissingRequiredVariables')]
  public function testMissingRequiredVariables(string $var_name): void {
    $this->envUnset($var_name);
    $this->runScriptError('src/notify-email', 'Missing required value for ' . $var_name);
  }

  public static function dataProviderMissingRequiredVariables(): array {
    return [
      'project' => ['VORTEX_NOTIFY_EMAIL_PROJECT'],
      'from' => ['VORTEX_NOTIFY_EMAIL_FROM'],
      'recipients' => ['VORTEX_NOTIFY_EMAIL_RECIPIENTS'],
      'label' => ['VORTEX_NOTIFY_EMAIL_LABEL'],
      'environment_url' => ['VORTEX_NOTIFY_EMAIL_ENVIRONMENT_URL'],
    ];
  }

  public function testFallbackToGenericVariables(): void {
    $this->envUnsetMultiple([
      'VORTEX_NOTIFY_EMAIL_PROJECT',
      'VORTEX_NOTIFY_EMAIL_FROM',
      'VORTEX_NOTIFY_EMAIL_LABEL',
      'VORTEX_NOTIFY_EMAIL_ENVIRONMENT_URL',
      'VORTEX_NOTIFY_EMAIL_LOGIN_URL',
      'VORTEX_NOTIFY_EMAIL_EVENT',
    ]);

    $this->envSet('VORTEX_NOTIFY_PROJECT', 'generic-project');
    $this->envSet('DRUPAL_SITE_EMAIL', 'site@example.com');
    $this->envSet('VORTEX_NOTIFY_LABEL', 'develop');
    $this->envSet('VORTEX_NOTIFY_ENVIRONMENT_URL', 'https://generic.example.com');
    $this->envSet('VORTEX_NOTIFY_LOGIN_URL', 'https://generic.example.com/login');
    $this->envSet('VORTEX_NOTIFY_EVENT', 'post_deployment');

    $exec = $this->getFunctionMock('DrevOps\\VortexTooling', 'exec');
    $exec
      ->expects($this->any())
      ->willReturnCallback(function ($cmd, &$output, &$return_var): void {
        $return_var = 0;
      });

    $output = $this->runScript('src/notify-email');

    $this->assertStringContainsString('Project        : generic-project', $output);
    $this->assertStringContainsString('From           : site@example.com', $output);
    $this->assertStringContainsString('Deployment     : develop', $output);
  }

  public function testTokenReplacementInMessage(): void {
    $this->envSet('VORTEX_NOTIFY_EMAIL_MESSAGE', '%project% deployed to %label% at %timestamp% - Visit: %environment_url%');

    $exec = $this->getFunctionMock('DrevOps\\VortexTooling', 'exec');
    $exec
      ->expects($this->any())
      ->willReturnCallback(function ($cmd, &$output, &$return_var): void {
        $return_var = 0;
      });

    $output = $this->runScript('src/notify-email');

    $this->assertStringContainsString('test-project deployed to main at', $output);
    $this->assertStringContainsString('example.com', $output);
  }

}
