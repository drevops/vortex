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

  protected function defaultMessageMatcher(string $project = 'test-project', string $label = 'main', string $url = 'https://example.com', string $login_url = 'https://example.com/login'): \Closure {
    return fn(string $msg): bool => str_contains($msg, '## This is an automated message ##')
      && str_contains($msg, 'Site ' . $project . ' ' . $label . ' has been deployed at')
      && str_contains($msg, $url)
      && str_contains($msg, 'Login at: ' . $login_url);
  }

  public function testSuccessfulNotificationSingleRecipient(): void {
    $this->mockMail([
      'to' => 'to@example.com',
      'subject' => 'test-project deployment notification of main',
      'message' => $this->defaultMessageMatcher(),
      'result' => TRUE,
    ]);

    $output = $this->runScript('src/vortex-notify-email');

    $this->assertStringContainsString('Started email notification', $output);
    $this->assertStringContainsString('Project        : test-project', $output);
    $this->assertStringContainsString('From           : noreply@example.com', $output);
    $this->assertStringContainsString('Recipients     : to@example.com', $output);
    $this->assertStringContainsString('Sending email notification', $output);
    $this->assertStringContainsString('Email notification sent successfully to 1 recipient(s)', $output);
    $this->assertStringContainsString('Finished email notification', $output);
  }

  public function testFailedNotification(): void {
    $this->mockMail([
      'to' => 'to@example.com',
      'subject' => 'test-project deployment notification of main',
      'message' => $this->defaultMessageMatcher(),
      'result' => FALSE,
    ]);

    $this->runScriptError('src/vortex-notify-email', 'Failed to send email notification via mail().');
  }

  public function testSuccessfulNotificationMultipleRecipients(): void {
    $this->envSet('VORTEX_NOTIFY_EMAIL_RECIPIENTS', 'to1@example.com|Jane Doe, to2@example.com|John Doe');

    $this->mockMail([
      'to' => '"Jane Doe" <to1@example.com>, "John Doe" <to2@example.com>',
      'subject' => 'test-project deployment notification of main',
      'message' => $this->defaultMessageMatcher(),
      'result' => TRUE,
    ]);

    $output = $this->runScript('src/vortex-notify-email');

    $this->assertStringContainsString('Recipients     : to1@example.com|Jane Doe, to2@example.com|John Doe', $output);
    $this->assertStringContainsString('Email notification sent successfully to 2 recipient(s)', $output);
    $this->assertStringContainsString('Finished email notification', $output);
  }

  public function testSuccessfulNotificationWithCustomMessage(): void {
    $this->envSet('VORTEX_NOTIFY_EMAIL_MESSAGE', 'Custom deployment of %project% to %label% at %timestamp%');

    $this->mockMail([
      'to' => 'to@example.com',
      'subject' => 'test-project deployment notification of main',
      'message' => fn(string $msg): bool => str_contains($msg, 'Custom deployment of test-project to main at'),
      'result' => TRUE,
    ]);

    $output = $this->runScript('src/vortex-notify-email');

    $this->assertStringContainsString('Custom deployment of test-project to main', $output);
    $this->assertStringContainsString('Finished email notification', $output);
  }

  public function testSuccessfulNotificationWithCustomSubject(): void {
    $this->envSet('VORTEX_NOTIFY_EMAIL_SUBJECT', '[%project%] Deployed %label%');

    $this->mockMail([
      'to' => 'to@example.com',
      'subject' => '[test-project] Deployed main',
      'message' => $this->defaultMessageMatcher(),
      'result' => TRUE,
    ]);

    $output = $this->runScript('src/vortex-notify-email');

    $this->assertStringContainsString('Subject        : [test-project] Deployed main', $output);
    $this->assertStringContainsString('Finished email notification', $output);
  }

  public function testPreDeploymentEventSkipped(): void {
    $this->envSet('VORTEX_NOTIFY_EMAIL_EVENT', 'pre_deployment');

    $this->runScriptEarlyPass('src/vortex-notify-email', 'Skipped email notification for pre_deployment event');
  }

  public function testNotificationSkippedWhenBranchNotInFilter(): void {
    $this->envSet('VORTEX_NOTIFY_EMAIL_BRANCHES', 'main,master');
    $this->envSet('VORTEX_NOTIFY_BRANCH', 'develop');

    $this->runScriptEarlyPass('src/vortex-notify-email', "Skipped email notification for branch 'develop'.");
  }

  public function testNotificationProceedsWhenBranchInFilter(): void {
    $this->envSet('VORTEX_NOTIFY_EMAIL_BRANCHES', 'main,develop');
    $this->envSet('VORTEX_NOTIFY_BRANCH', 'develop');

    $this->mockMail([
      'to' => 'to@example.com',
      'subject' => 'test-project deployment notification of main',
      'message' => $this->defaultMessageMatcher(),
      'result' => TRUE,
    ]);

    $output = $this->runScript('src/vortex-notify-email');

    $this->assertStringContainsString('Finished email notification', $output);
  }

  #[DataProvider('dataProviderMissingRequiredVariables')]
  public function testMissingRequiredVariables(string $var_name): void {
    $this->envUnset($var_name);
    $this->runScriptError('src/vortex-notify-email', 'Missing required value for ' . $var_name);
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

    $this->mockMail([
      'to' => 'to@example.com',
      'subject' => 'generic-project deployment notification of develop',
      'message' => $this->defaultMessageMatcher('generic-project', 'develop', 'https://generic.example.com', 'https://generic.example.com/login'),
      'result' => TRUE,
    ]);

    $output = $this->runScript('src/vortex-notify-email');

    $this->assertStringContainsString('Project        : generic-project', $output);
    $this->assertStringContainsString('From           : site@example.com', $output);
    $this->assertStringContainsString('Deployment     : develop', $output);
  }

  public function testTokenReplacementInMessage(): void {
    $this->envSet('VORTEX_NOTIFY_EMAIL_MESSAGE', '%project% deployed to %label% at %timestamp% - Visit: %environment_url%');

    $this->mockMail([
      'to' => 'to@example.com',
      'subject' => 'test-project deployment notification of main',
      'message' => fn(string $msg): bool => str_contains($msg, 'test-project deployed to main at') && str_contains($msg, 'Visit: https://example.com'),
      'result' => TRUE,
    ]);

    $output = $this->runScript('src/vortex-notify-email');

    $this->assertStringContainsString('test-project deployed to main at', $output);
    $this->assertStringContainsString('example.com', $output);
  }

  public function testTokenReplacementInSubject(): void {
    $this->envSet('VORTEX_NOTIFY_EMAIL_SUBJECT', 'Deployed %label% to %environment_url%');

    $this->mockMail([
      'to' => 'to@example.com',
      'subject' => 'Deployed main to https://example.com',
      'message' => $this->defaultMessageMatcher(),
      'result' => TRUE,
    ]);

    $output = $this->runScript('src/vortex-notify-email');

    $this->assertStringContainsString('Subject        : Deployed main to https://example.com', $output);
  }

  public function testTokenReplacementWithLoginUrl(): void {
    $this->envSet('VORTEX_NOTIFY_EMAIL_MESSAGE', 'Login here: %login_url%');

    $this->mockMail([
      'to' => 'to@example.com',
      'subject' => 'test-project deployment notification of main',
      'message' => 'Login here: https://example.com/login',
      'result' => TRUE,
    ]);

    $output = $this->runScript('src/vortex-notify-email');

    $this->assertStringContainsString('Login here: https://example.com/login', $output);
  }

  public function testRecipientsWithoutNames(): void {
    $this->envSet('VORTEX_NOTIFY_EMAIL_RECIPIENTS', 'to1@example.com, to2@example.com, to3@example.com');

    $this->mockMail([
      'to' => 'to1@example.com, to2@example.com, to3@example.com',
      'subject' => 'test-project deployment notification of main',
      'message' => $this->defaultMessageMatcher(),
      'result' => TRUE,
    ]);

    $output = $this->runScript('src/vortex-notify-email');

    $this->assertStringContainsString('Email notification sent successfully to 3 recipient(s)', $output);
  }

  public function testRecipientsMixedWithAndWithoutNames(): void {
    $this->envSet('VORTEX_NOTIFY_EMAIL_RECIPIENTS', 'to1@example.com, to2@example.com|Jane Doe, to3@example.com');

    $this->mockMail([
      'to' => 'to1@example.com, "Jane Doe" <to2@example.com>, to3@example.com',
      'subject' => 'test-project deployment notification of main',
      'message' => $this->defaultMessageMatcher(),
      'result' => TRUE,
    ]);

    $output = $this->runScript('src/vortex-notify-email');

    $this->assertStringContainsString('Email notification sent successfully to 3 recipient(s)', $output);
  }

  public function testCcSingleRecipient(): void {
    $this->envSet('VORTEX_NOTIFY_EMAIL_CC', 'cc@example.com');

    $this->mockMail([
      'to' => 'to@example.com',
      'subject' => 'test-project deployment notification of main',
      'message' => $this->defaultMessageMatcher(),
      'headers' => [
        'Cc: cc@example.com',
        'Content-Type: text/plain; charset=UTF-8',
        'From: noreply@example.com',
      ],
      'result' => TRUE,
    ]);

    $output = $this->runScript('src/vortex-notify-email');

    $this->assertStringContainsString('CC             : cc@example.com', $output);
    $this->assertStringContainsString('Email notification sent successfully to 1 recipient(s)', $output);
  }

  public function testCcMultipleRecipients(): void {
    $this->envSet('VORTEX_NOTIFY_EMAIL_CC', 'cc1@example.com|Jane Doe, cc2@example.com|John Doe');

    $this->mockMail([
      'to' => 'to@example.com',
      'subject' => 'test-project deployment notification of main',
      'message' => $this->defaultMessageMatcher(),
      'headers' => [
        'Cc: "Jane Doe" <cc1@example.com>, "John Doe" <cc2@example.com>',
        'Content-Type: text/plain; charset=UTF-8',
        'From: noreply@example.com',
      ],
      'result' => TRUE,
    ]);

    $output = $this->runScript('src/vortex-notify-email');

    $this->assertStringContainsString('CC             : cc1@example.com|Jane Doe, cc2@example.com|John Doe', $output);
    $this->assertStringContainsString('Email notification sent successfully to 1 recipient(s)', $output);
  }

  public function testBccSingleRecipient(): void {
    $this->envSet('VORTEX_NOTIFY_EMAIL_BCC', 'bcc@example.com');

    $this->mockMail([
      'to' => 'to@example.com',
      'subject' => 'test-project deployment notification of main',
      'message' => $this->defaultMessageMatcher(),
      'headers' => [
        'Bcc: bcc@example.com',
        'Content-Type: text/plain; charset=UTF-8',
        'From: noreply@example.com',
      ],
      'result' => TRUE,
    ]);

    $output = $this->runScript('src/vortex-notify-email');

    $this->assertStringContainsString('BCC            : bcc@example.com', $output);
    $this->assertStringContainsString('Email notification sent successfully to 1 recipient(s)', $output);
  }

  public function testBccMultipleRecipients(): void {
    $this->envSet('VORTEX_NOTIFY_EMAIL_BCC', 'bcc1@example.com, bcc2@example.com');

    $this->mockMail([
      'to' => 'to@example.com',
      'subject' => 'test-project deployment notification of main',
      'message' => $this->defaultMessageMatcher(),
      'headers' => [
        'Bcc: bcc1@example.com, bcc2@example.com',
        'Content-Type: text/plain; charset=UTF-8',
        'From: noreply@example.com',
      ],
      'result' => TRUE,
    ]);

    $output = $this->runScript('src/vortex-notify-email');

    $this->assertStringContainsString('BCC            : bcc1@example.com, bcc2@example.com', $output);
    $this->assertStringContainsString('Email notification sent successfully to 1 recipient(s)', $output);
  }

  public function testCcAndBccTogether(): void {
    $this->envSet('VORTEX_NOTIFY_EMAIL_CC', 'cc@example.com|CC User');
    $this->envSet('VORTEX_NOTIFY_EMAIL_BCC', 'bcc@example.com|BCC User');

    $this->mockMail([
      'to' => 'to@example.com',
      'subject' => 'test-project deployment notification of main',
      'message' => $this->defaultMessageMatcher(),
      'headers' => [
        'Bcc: "BCC User" <bcc@example.com>',
        'Cc: "CC User" <cc@example.com>',
        'Content-Type: text/plain; charset=UTF-8',
        'From: noreply@example.com',
      ],
      'result' => TRUE,
    ]);

    $output = $this->runScript('src/vortex-notify-email');

    $this->assertStringContainsString('CC             : cc@example.com|CC User', $output);
    $this->assertStringContainsString('BCC            : bcc@example.com|BCC User', $output);
    $this->assertStringContainsString('Email notification sent successfully to 1 recipient(s)', $output);
  }

  public function testCcWithMultipleToRecipients(): void {
    $this->envSet('VORTEX_NOTIFY_EMAIL_RECIPIENTS', 'to1@example.com, to2@example.com');
    $this->envSet('VORTEX_NOTIFY_EMAIL_CC', 'cc@example.com');

    // A single message is sent to both TO recipients with the CC header present
    // exactly once, so the CC recipient is not copied once per TO recipient.
    $this->mockMail([
      'to' => 'to1@example.com, to2@example.com',
      'subject' => 'test-project deployment notification of main',
      'message' => $this->defaultMessageMatcher(),
      'headers' => [
        'Cc: cc@example.com',
        'Content-Type: text/plain; charset=UTF-8',
        'From: noreply@example.com',
      ],
      'result' => TRUE,
    ]);

    $output = $this->runScript('src/vortex-notify-email');

    $this->assertStringContainsString('CC             : cc@example.com', $output);
    $this->assertStringContainsString('Email notification sent successfully to 2 recipient(s)', $output);
  }

  public function testCcAndBccMixedFormats(): void {
    $this->envSet('VORTEX_NOTIFY_EMAIL_CC', 'cc1@example.com, cc2@example.com|Named User');
    $this->envSet('VORTEX_NOTIFY_EMAIL_BCC', 'bcc1@example.com|First BCC, bcc2@example.com');

    $this->mockMail([
      'to' => 'to@example.com',
      'subject' => 'test-project deployment notification of main',
      'message' => $this->defaultMessageMatcher(),
      'headers' => [
        'Bcc: "First BCC" <bcc1@example.com>, bcc2@example.com',
        'Cc: cc1@example.com, "Named User" <cc2@example.com>',
        'Content-Type: text/plain; charset=UTF-8',
        'From: noreply@example.com',
      ],
      'result' => TRUE,
    ]);

    $output = $this->runScript('src/vortex-notify-email');

    $this->assertStringContainsString('CC             : cc1@example.com, cc2@example.com|Named User', $output);
    $this->assertStringContainsString('BCC            : bcc1@example.com|First BCC, bcc2@example.com', $output);
    $this->assertStringContainsString('Email notification sent successfully to 1 recipient(s)', $output);
  }

  public function testRecipientsWithExtraSpaces(): void {
    $this->envSet('VORTEX_NOTIFY_EMAIL_RECIPIENTS', '  to1@example.com  ,  to2@example.com | Jane Doe  ');

    $this->mockMail([
      'to' => 'to1@example.com, "Jane Doe" <to2@example.com>',
      'subject' => 'test-project deployment notification of main',
      'message' => $this->defaultMessageMatcher(),
      'headers' => [
        'Content-Type: text/plain; charset=UTF-8',
        'From: noreply@example.com',
      ],
      'result' => TRUE,
    ]);

    $output = $this->runScript('src/vortex-notify-email');

    $this->assertStringContainsString('Email notification sent successfully to 2 recipient(s)', $output);
  }

  public function testEmailWithPlusSignAndSubdomain(): void {
    $this->envSet('VORTEX_NOTIFY_EMAIL_RECIPIENTS', 'user+tag@mail.example.com|Tagged User');

    $this->mockMail([
      'to' => '"Tagged User" <user+tag@mail.example.com>',
      'subject' => 'test-project deployment notification of main',
      'message' => $this->defaultMessageMatcher(),
      'headers' => [
        'Content-Type: text/plain; charset=UTF-8',
        'From: noreply@example.com',
      ],
      'result' => TRUE,
    ]);

    $output = $this->runScript('src/vortex-notify-email');

    $this->assertStringContainsString('Email notification sent successfully to 1 recipient(s)', $output);
  }

  public function testNameWithApostrophe(): void {
    $this->envSet('VORTEX_NOTIFY_EMAIL_RECIPIENTS', "user@example.com|O'Brien");

    $this->mockMail([
      'to' => '"O\'Brien" <user@example.com>',
      'subject' => 'test-project deployment notification of main',
      'message' => $this->defaultMessageMatcher(),
      'headers' => [
        'Content-Type: text/plain; charset=UTF-8',
        'From: noreply@example.com',
      ],
      'result' => TRUE,
    ]);

    $output = $this->runScript('src/vortex-notify-email');

    $this->assertStringContainsString('Email notification sent successfully to 1 recipient(s)', $output);
  }

  public function testDeploymentLogIncludedInBody(): void {
    $log_file = self::$tmp . '/provision.log';
    file_put_contents($log_file, "Provision line one\nProvision line two\n");

    $this->envSet('VORTEX_NOTIFY_EMAIL_LOG', '1');
    $this->envSet('VORTEX_NOTIFY_EMAIL_LOG_FILE', $log_file);

    $this->mockMail([
      'to' => 'to@example.com',
      'subject' => 'test-project deployment notification of main',
      'message' => fn(string $msg): bool => str_contains($msg, 'Provision line one') && str_contains($msg, 'Provision line two'),
      'result' => TRUE,
    ]);

    $output = $this->runScript('src/vortex-notify-email');

    $this->assertStringContainsString('Log file       : ' . $log_file, $output);
    $this->assertStringContainsString('Finished email notification', $output);
  }

  public function testDeploymentLogMissingFileLeavesBodyIntact(): void {
    $this->envSet('VORTEX_NOTIFY_EMAIL_LOG', '1');
    $this->envSet('VORTEX_NOTIFY_EMAIL_LOG_FILE', self::$tmp . '/nonexistent.log');

    $this->mockMail([
      'to' => 'to@example.com',
      'subject' => 'test-project deployment notification of main',
      'message' => $this->defaultMessageMatcher(),
      'result' => TRUE,
    ]);

    $output = $this->runScript('src/vortex-notify-email');

    $this->assertStringContainsString('Log file       : <missing>', $output);
  }

  public function testDeploymentLogEmptyLeavesBodyIntact(): void {
    $log_file = self::$tmp . '/provision.log';
    touch($log_file);

    $this->envSet('VORTEX_NOTIFY_EMAIL_LOG', '1');
    $this->envSet('VORTEX_NOTIFY_EMAIL_LOG_FILE', $log_file);

    $this->mockMail([
      'to' => 'to@example.com',
      'subject' => 'test-project deployment notification of main',
      'message' => $this->defaultMessageMatcher(),
      'result' => TRUE,
    ]);

    $output = $this->runScript('src/vortex-notify-email');

    $this->assertStringContainsString('Log file       : <missing>', $output);
  }

  public function testDeploymentLogTreatedAsLiteralText(): void {
    $log_file = self::$tmp . '/provision.log';
    file_put_contents($log_file, '%project% literal $(touch pwned)');

    $this->envSet('VORTEX_NOTIFY_EMAIL_LOG', '1');
    $this->envSet('VORTEX_NOTIFY_EMAIL_LOG_FILE', $log_file);

    // The log is inserted last and verbatim: the '%project%' token and the
    // command substitution stay literal and are never expanded.
    $this->mockMail([
      'to' => 'to@example.com',
      'subject' => 'test-project deployment notification of main',
      'message' => fn(string $msg): bool => str_contains($msg, '%project% literal $(touch pwned)'),
      'result' => TRUE,
    ]);

    $output = $this->runScript('src/vortex-notify-email');

    $this->assertStringContainsString('Finished email notification', $output);
  }

  public function testDeploymentLogExcludedWhenDisabled(): void {
    $log_file = self::$tmp . '/provision.log';
    file_put_contents($log_file, "Provision line one\n");

    // The log exists, but the email channel flag is left disabled.
    $this->envSet('VORTEX_NOTIFY_EMAIL_LOG', '0');
    $this->envSet('VORTEX_NOTIFY_EMAIL_LOG_FILE', $log_file);

    $this->mockMail([
      'to' => 'to@example.com',
      'subject' => 'test-project deployment notification of main',
      'message' => fn(string $msg): bool => str_contains($msg, 'has been deployed') && !str_contains($msg, 'Provision line one'),
      'result' => TRUE,
    ]);

    $output = $this->runScript('src/vortex-notify-email');

    $this->assertStringContainsString('Log file       : <disabled>', $output);
  }

  public function testDeploymentLogTokenInCustomTemplate(): void {
    $log_file = self::$tmp . '/provision.log';
    file_put_contents($log_file, "CUSTOM log line one\nCUSTOM log line two\n");

    $this->envSet('VORTEX_NOTIFY_EMAIL_MESSAGE', 'Custom body. Log below: %deployment_log%');
    $this->envSet('VORTEX_NOTIFY_EMAIL_LOG', '1');
    $this->envSet('VORTEX_NOTIFY_EMAIL_LOG_FILE', $log_file);

    $this->mockMail([
      'to' => 'to@example.com',
      'subject' => 'test-project deployment notification of main',
      'message' => fn(string $msg): bool => str_contains($msg, 'Custom body. Log below:') && str_contains($msg, 'CUSTOM log line one') && str_contains($msg, 'CUSTOM log line two'),
      'result' => TRUE,
    ]);

    $output = $this->runScript('src/vortex-notify-email');

    $this->assertStringContainsString('CUSTOM log line one', $output);
  }

  public function testDeploymentLogPerChannelFlagOverridesCommon(): void {
    $log_file = self::$tmp . '/provision.log';
    file_put_contents($log_file, "Provision line one\n");

    // Enabled globally, but disabled for the email channel specifically.
    $this->envSet('VORTEX_NOTIFY_LOG', '1');
    $this->envSet('VORTEX_NOTIFY_EMAIL_LOG', '0');
    $this->envSet('VORTEX_NOTIFY_EMAIL_LOG_FILE', $log_file);

    $this->mockMail([
      'to' => 'to@example.com',
      'subject' => 'test-project deployment notification of main',
      'message' => fn(string $msg): bool => !str_contains($msg, 'Provision line one'),
      'result' => TRUE,
    ]);

    $output = $this->runScript('src/vortex-notify-email');

    $this->assertStringContainsString('Log file       : <disabled>', $output);
  }

}
