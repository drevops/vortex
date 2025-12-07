<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Self;

use DrevOps\VortexTooling\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Self-tests for mocking of mail() function.
 *
 * @phpcs:disable Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
 */
#[CoversClass(UnitTestCase::class)]
class MockMailSelfTest extends UnitTestCase {

  public function testMockMailSuccess(): void {
    $this->mockMail([
      'to' => 'test@example.com',
      'subject' => 'Test Subject',
      'message' => 'Test Message',
    ]);

    require_once __DIR__ . '/../../src/helpers.php';

    $result = \DrevOps\VortexTooling\mail('test@example.com', 'Test Subject', 'Test Message', []);

    $this->assertTrue($result);
  }

  public function testMockMailWithHeadersSuccess(): void {
    $this->mockMail([
      'to' => 'test@example.com',
      'subject' => 'Test Subject',
      'message' => 'Test Message',
      'headers' => ['From: sender@example.com', 'Content-Type: text/html'],
    ]);

    $result = \DrevOps\VortexTooling\mail(
      'test@example.com',
      'Test Subject',
      'Test Message',
      ['From: sender@example.com', 'Content-Type: text/html']
    );

    $this->assertTrue($result);
  }

  public function testMockMailWithResultFalse(): void {
    $this->mockMail([
      'to' => 'test@example.com',
      'subject' => 'Test Subject',
      'message' => 'Test Message',
      'result' => FALSE,
    ]);

    $result = \DrevOps\VortexTooling\mail('test@example.com', 'Test Subject', 'Test Message', []);

    $this->assertFalse($result);
  }

  public function testMockMailMultipleSuccess(): void {
    $this->mockMailMultiple([
      [
        'to' => 'first@example.com',
        'subject' => 'First Subject',
        'message' => 'First Message',
      ],
      [
        'to' => 'second@example.com',
        'subject' => 'Second Subject',
        'message' => 'Second Message',
      ],
    ]);

    $result1 = \DrevOps\VortexTooling\mail('first@example.com', 'First Subject', 'First Message', []);
    $result2 = \DrevOps\VortexTooling\mail('second@example.com', 'Second Subject', 'Second Message', []);

    $this->assertTrue($result1);
    $this->assertTrue($result2);
  }

  public function testMockMailFailureUnexpectedRecipient(): void {
    $this->mockMail([
      'to' => 'expected@example.com',
      'subject' => 'Test Subject',
      'message' => 'Test Message',
    ]);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('mail() called with unexpected recipient. Expected "expected@example.com", got "wrong@example.com".');

    \DrevOps\VortexTooling\mail('wrong@example.com', 'Test Subject', 'Test Message', []);
  }

  public function testMockMailFailureUnexpectedSubject(): void {
    $this->mockMail([
      'to' => 'test@example.com',
      'subject' => 'Expected Subject',
      'message' => 'Test Message',
    ]);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('mail() called with unexpected subject. Expected "Expected Subject", got "Wrong Subject".');

    \DrevOps\VortexTooling\mail('test@example.com', 'Wrong Subject', 'Test Message', []);
  }

  public function testMockMailFailureUnexpectedMessage(): void {
    $this->mockMail([
      'to' => 'test@example.com',
      'subject' => 'Test Subject',
      'message' => 'Expected Message',
    ]);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('mail() called with unexpected message. Expected "Expected Message", got "Wrong Message".');

    \DrevOps\VortexTooling\mail('test@example.com', 'Test Subject', 'Wrong Message', []);
  }

  public function testMockMailFailureUnexpectedHeaders(): void {
    $this->mockMail([
      'to' => 'test@example.com',
      'subject' => 'Test Subject',
      'message' => 'Test Message',
      'headers' => ['From: expected@example.com'],
    ]);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('mail() called with unexpected headers.');

    \DrevOps\VortexTooling\mail('test@example.com', 'Test Subject', 'Test Message', ['From: wrong@example.com']);
  }

  public function testMockMailMultipleMoreCallsFailure(): void {
    $this->mockMailMultiple([
      [
        'to' => 'first@example.com',
        'subject' => 'First Subject',
        'message' => 'First Message',
      ],
      [
        'to' => 'second@example.com',
        'subject' => 'Second Subject',
        'message' => 'Second Message',
      ],
    ]);

    \DrevOps\VortexTooling\mail('first@example.com', 'First Subject', 'First Message', []);
    \DrevOps\VortexTooling\mail('second@example.com', 'Second Subject', 'Second Message', []);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('mail() called more times than mocked responses. Expected 2 call(s), but attempting call #3.');

    \DrevOps\VortexTooling\mail('third@example.com', 'Third Subject', 'Third Message', []);
  }

  public function testMockMailMultipleLessCallsFailure(): void {
    $this->mockMailMultiple([
      [
        'to' => 'first@example.com',
        'subject' => 'First Subject',
        'message' => 'First Message',
      ],
      [
        'to' => 'second@example.com',
        'subject' => 'Second Subject',
        'message' => 'Second Message',
      ],
      [
        'to' => 'third@example.com',
        'subject' => 'Third Subject',
        'message' => 'Third Message',
      ],
    ]);

    \DrevOps\VortexTooling\mail('first@example.com', 'First Subject', 'First Message', []);
    \DrevOps\VortexTooling\mail('second@example.com', 'Second Subject', 'Second Message', []);

    $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
    $this->expectExceptionMessage('Not all mocked mail responses were consumed. Expected 3 call(s), but only 2 call(s) were made.');

    $this->mockMailAssertAllMocksConsumed();
  }

  public function testMockMailFailureMissingToKey(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Mocked mail response must include "to" key to specify expected recipient.');

    // @phpstan-ignore-next-line argument.type
    $this->mockMail([
      'subject' => 'Test Subject',
      'message' => 'Test Message',
    ]);

    \DrevOps\VortexTooling\mail('test@example.com', 'Test Subject', 'Test Message', []);
  }

  public function testMockMailFailureMissingSubjectKey(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Mocked mail response must include "subject" key to specify expected subject.');

    // @phpstan-ignore-next-line argument.type
    $this->mockMail([
      'to' => 'test@example.com',
      'message' => 'Test Message',
    ]);

    \DrevOps\VortexTooling\mail('test@example.com', 'Test Subject', 'Test Message', []);
  }

  public function testMockMailFailureMissingMessageKey(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Mocked mail response must include "message" key to specify expected message.');

    // @phpstan-ignore-next-line argument.type
    $this->mockMail([
      'to' => 'test@example.com',
      'subject' => 'Test Subject',
    ]);

    \DrevOps\VortexTooling\mail('test@example.com', 'Test Subject', 'Test Message', []);
  }

  public function testMockMailWithStringHeadersSuccess(): void {
    $this->mockMail([
      'to' => 'test@example.com',
      'subject' => 'Test Subject',
      'message' => 'Test Message',
      'headers' => "From: sender@example.com\r\nContent-Type: text/html",
    ]);

    $result = \DrevOps\VortexTooling\mail(
      'test@example.com',
      'Test Subject',
      'Test Message',
      "From: sender@example.com\r\nContent-Type: text/html"
    );

    $this->assertTrue($result);
  }

  public function testMockMailWithMixedHeaderFormatsSuccess(): void {
    $this->mockMail([
      'to' => 'test@example.com',
      'subject' => 'Test Subject',
      'message' => 'Test Message',
      'headers' => "From: sender@example.com\r\nContent-Type: text/html",
    ]);

    $result = \DrevOps\VortexTooling\mail(
      'test@example.com',
      'Test Subject',
      'Test Message',
      ['From: sender@example.com', 'Content-Type: text/html']
    );

    $this->assertTrue($result);
  }

  public function testMockMailWithArrayExpectedStringActualSuccess(): void {
    $this->mockMail([
      'to' => 'test@example.com',
      'subject' => 'Test Subject',
      'message' => 'Test Message',
      'headers' => ['From: sender@example.com', 'Content-Type: text/html'],
    ]);

    $result = \DrevOps\VortexTooling\mail(
      'test@example.com',
      'Test Subject',
      'Test Message',
      "From: sender@example.com\r\nContent-Type: text/html"
    );

    $this->assertTrue($result);
  }

  public function testMockMailMultipleCallsSuccess(): void {
    $this->mockMail([
      'to' => 'first@example.com',
      'subject' => 'First Subject',
      'message' => 'First Message',
    ]);

    $this->mockMail([
      'to' => 'second@example.com',
      'subject' => 'Second Subject',
      'message' => 'Second Message',
    ]);

    $result1 = \DrevOps\VortexTooling\mail('first@example.com', 'First Subject', 'First Message', []);
    $result2 = \DrevOps\VortexTooling\mail('second@example.com', 'Second Subject', 'Second Message', []);

    $this->assertTrue($result1);
    $this->assertTrue($result2);
  }

}
