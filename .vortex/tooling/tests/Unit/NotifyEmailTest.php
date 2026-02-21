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

    $output = $this->runScript('src/notify-email');

    $this->assertStringContainsString('Started email notification', $output);
    $this->assertStringContainsString('Project        : test-project', $output);
    $this->assertStringContainsString('From           : noreply@example.com', $output);
    $this->assertStringContainsString('Recipients     : to@example.com', $output);
    $this->assertStringContainsString('Sending email notification', $output);
    $this->assertStringContainsString('Email notification sent successfully to 1 recipient(s)', $output);
    $this->assertStringContainsString('Finished email notification', $output);
  }

  public function testSuccessfulNotificationMultipleRecipients(): void {
    $this->envSet('VORTEX_NOTIFY_EMAIL_RECIPIENTS', 'to1@example.com|Jane Doe, to2@example.com|John Doe');

    $this->mockMail([
      'to' => '"Jane Doe" <to1@example.com>',
      'subject' => 'test-project deployment notification of main',
      'message' => $this->defaultMessageMatcher(),
      'result' => TRUE,
    ]);

    $this->mockMail([
      'to' => '"John Doe" <to2@example.com>',
      'subject' => 'test-project deployment notification of main',
      'message' => $this->defaultMessageMatcher(),
      'result' => TRUE,
    ]);

    $output = $this->runScript('src/notify-email');

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

    $output = $this->runScript('src/notify-email');

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

    $output = $this->runScript('src/notify-email');

    $this->assertStringContainsString('Subject        : [test-project] Deployed main', $output);
    $this->assertStringContainsString('Finished email notification', $output);
  }

  public function testPreDeploymentEventSkipped(): void {
    $this->envSet('VORTEX_NOTIFY_EMAIL_EVENT', 'pre_deployment');

    $this->runScriptEarlyPass('src/notify-email', 'Skipping email notification for pre_deployment event');
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

    $this->mockMail([
      'to' => 'to@example.com',
      'subject' => 'generic-project deployment notification of develop',
      'message' => $this->defaultMessageMatcher('generic-project', 'develop', 'https://generic.example.com', 'https://generic.example.com/login'),
      'result' => TRUE,
    ]);

    $output = $this->runScript('src/notify-email');

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

    $output = $this->runScript('src/notify-email');

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

    $output = $this->runScript('src/notify-email');

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

    $output = $this->runScript('src/notify-email');

    $this->assertStringContainsString('Login here: https://example.com/login', $output);
  }

  public function testRecipientsWithoutNames(): void {
    $this->envSet('VORTEX_NOTIFY_EMAIL_RECIPIENTS', 'to1@example.com, to2@example.com, to3@example.com');

    $this->mockMail([
      'to' => 'to1@example.com',
      'subject' => 'test-project deployment notification of main',
      'message' => $this->defaultMessageMatcher(),
      'result' => TRUE,
    ]);

    $this->mockMail([
      'to' => 'to2@example.com',
      'subject' => 'test-project deployment notification of main',
      'message' => $this->defaultMessageMatcher(),
      'result' => TRUE,
    ]);

    $this->mockMail([
      'to' => 'to3@example.com',
      'subject' => 'test-project deployment notification of main',
      'message' => $this->defaultMessageMatcher(),
      'result' => TRUE,
    ]);

    $output = $this->runScript('src/notify-email');

    $this->assertStringContainsString('Email notification sent successfully to 3 recipient(s)', $output);
  }

  public function testRecipientsMixedWithAndWithoutNames(): void {
    $this->envSet('VORTEX_NOTIFY_EMAIL_RECIPIENTS', 'to1@example.com, to2@example.com|Jane Doe, to3@example.com');

    $this->mockMail([
      'to' => 'to1@example.com',
      'subject' => 'test-project deployment notification of main',
      'message' => $this->defaultMessageMatcher(),
      'result' => TRUE,
    ]);

    $this->mockMail([
      'to' => '"Jane Doe" <to2@example.com>',
      'subject' => 'test-project deployment notification of main',
      'message' => $this->defaultMessageMatcher(),
      'result' => TRUE,
    ]);

    $this->mockMail([
      'to' => 'to3@example.com',
      'subject' => 'test-project deployment notification of main',
      'message' => $this->defaultMessageMatcher(),
      'result' => TRUE,
    ]);

    $output = $this->runScript('src/notify-email');

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

    $output = $this->runScript('src/notify-email');

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

    $output = $this->runScript('src/notify-email');

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

    $output = $this->runScript('src/notify-email');

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

    $output = $this->runScript('src/notify-email');

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

    $output = $this->runScript('src/notify-email');

    $this->assertStringContainsString('CC             : cc@example.com|CC User', $output);
    $this->assertStringContainsString('BCC            : bcc@example.com|BCC User', $output);
    $this->assertStringContainsString('Email notification sent successfully to 1 recipient(s)', $output);
  }

  public function testCcWithMultipleToRecipients(): void {
    $this->envSet('VORTEX_NOTIFY_EMAIL_RECIPIENTS', 'to1@example.com, to2@example.com');
    $this->envSet('VORTEX_NOTIFY_EMAIL_CC', 'cc@example.com');

    $this->mockMail([
      'to' => 'to1@example.com',
      'subject' => 'test-project deployment notification of main',
      'message' => $this->defaultMessageMatcher(),
      'headers' => [
        'Cc: cc@example.com',
        'Content-Type: text/plain; charset=UTF-8',
        'From: noreply@example.com',
      ],
      'result' => TRUE,
    ]);

    $this->mockMail([
      'to' => 'to2@example.com',
      'subject' => 'test-project deployment notification of main',
      'message' => $this->defaultMessageMatcher(),
      'headers' => [
        'Cc: cc@example.com',
        'Content-Type: text/plain; charset=UTF-8',
        'From: noreply@example.com',
      ],
      'result' => TRUE,
    ]);

    $output = $this->runScript('src/notify-email');

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

    $output = $this->runScript('src/notify-email');

    $this->assertStringContainsString('CC             : cc1@example.com, cc2@example.com|Named User', $output);
    $this->assertStringContainsString('BCC            : bcc1@example.com|First BCC, bcc2@example.com', $output);
    $this->assertStringContainsString('Email notification sent successfully to 1 recipient(s)', $output);
  }

  public function testRecipientsWithExtraSpaces(): void {
    $this->envSet('VORTEX_NOTIFY_EMAIL_RECIPIENTS', '  to1@example.com  ,  to2@example.com | Jane Doe  ');

    $this->mockMail([
      'to' => 'to1@example.com',
      'subject' => 'test-project deployment notification of main',
      'message' => $this->defaultMessageMatcher(),
      'headers' => [
        'Content-Type: text/plain; charset=UTF-8',
        'From: noreply@example.com',
      ],
      'result' => TRUE,
    ]);

    $this->mockMail([
      'to' => '"Jane Doe" <to2@example.com>',
      'subject' => 'test-project deployment notification of main',
      'message' => $this->defaultMessageMatcher(),
      'headers' => [
        'Content-Type: text/plain; charset=UTF-8',
        'From: noreply@example.com',
      ],
      'result' => TRUE,
    ]);

    $output = $this->runScript('src/notify-email');

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

    $output = $this->runScript('src/notify-email');

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

    $output = $this->runScript('src/notify-email');

    $this->assertStringContainsString('Email notification sent successfully to 1 recipient(s)', $output);
  }

}
