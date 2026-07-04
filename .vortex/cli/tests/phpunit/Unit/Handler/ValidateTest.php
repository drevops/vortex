<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Unit\Handler;

use DrevOps\VortexCli\Handler\Validate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the Validate helpers.
 */
#[CoversClass(Validate::class)]
#[Group('handler')]
final class ValidateTest extends TestCase {

  public function testMachineName(): void {
    $this->assertTrue(Validate::isMachineName('my_site'));
    $this->assertFalse(Validate::isMachineName('My Site'));
    $this->assertFalse(Validate::isMachineName(''));
  }

  public function testPhpPackageName(): void {
    $this->assertTrue(Validate::isPhpPackageName('my-site_1'));
    $this->assertFalse(Validate::isPhpPackageName('My Site'));
  }

  public function testDirname(): void {
    $this->assertTrue(Validate::isDirname('web'));
    $this->assertFalse(Validate::isDirname('..'));
  }

  public function testContainerImage(): void {
    $this->assertTrue(Validate::isContainerImage('org/site-data:latest'));
    $this->assertFalse(Validate::isContainerImage('bad image'));
  }

  public function testDomain(): void {
    $this->assertTrue(Validate::isDomain('example.com'));
    $this->assertFalse(Validate::isDomain('localhost'));
    $this->assertFalse(Validate::isDomain('127.0.0.1'));
  }

  public function testFilledLabel(): void {
    $this->assertTrue(Validate::isFilledLabel('My Site'));
    $this->assertFalse(Validate::isFilledLabel('   '));
  }

  public function testDomainTransform(): void {
    $this->assertSame('example.com', Validate::domain('https://Example.com/'));
  }

}
