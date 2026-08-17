<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Unit;

use Drupal\Tests\ys_base\Traits\MockTrait;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * Tests the MockTrait shipped to consumer sites.
 *
 * The trait builds mocks that only stub return values, so the mocks under test
 * carry no expectations by design.
 */
#[AllowMockObjectsWithoutExpectations]
class MockTraitTest extends TestCase {

  use MockTrait;

  /**
   * Tests that mapped methods return their configured values.
   */
  public function testPrepareMockReturnsMappedValues(): void {
    $mock = $this->prepareMock(MockSubject::class, [
      'greet' => 'mocked greeting',
      'total' => 42,
    ]);
    $this->assertInstanceOf(MockSubject::class, $mock);

    $this->assertSame('mocked greeting', $mock->greet());
    $this->assertSame(42, $mock->total());
  }

  /**
   * Tests that unmapped methods keep their original behaviour.
   */
  public function testPrepareMockLeavesUnmappedMethods(): void {
    $mock = $this->prepareMock(MockSubject::class, ['greet' => 'mocked greeting']);
    $this->assertInstanceOf(MockSubject::class, $mock);

    $this->assertSame(0, $mock->total());
  }

  /**
   * Tests that a callable value is used as a return callback.
   */
  public function testPrepareMockAcceptsCallable(): void {
    $mock = $this->prepareMock(MockSubject::class, ['echoBack' => strtoupper(...)]);
    $this->assertInstanceOf(MockSubject::class, $mock);

    $this->assertSame('VALUE', $mock->echoBack('value'));
  }

  /**
   * Tests that an empty argument list still runs the original constructor.
   */
  public function testPrepareMockWithoutArguments(): void {
    $mock = $this->prepareMock(MockSubject::class, ['greet' => 'mocked greeting']);
    $this->assertInstanceOf(MockSubject::class, $mock);

    $this->assertSame(['name' => ''], $mock->constructorArgs());
  }

  /**
   * Tests that constructor arguments are passed through.
   */
  public function testPrepareMockPassesConstructorArguments(): void {
    $mock = $this->prepareMock(MockSubject::class, ['greet' => 'mocked greeting'], ['name' => 'constructed name']);
    $this->assertInstanceOf(MockSubject::class, $mock);

    $this->assertSame(['name' => 'constructed name'], $mock->constructorArgs());
  }

  /**
   * Tests that FALSE disables the original constructor.
   */
  public function testPrepareMockDisablesConstructor(): void {
    $mock = $this->prepareMock(MockSubject::class, ['greet' => 'mocked greeting'], FALSE);
    $this->assertInstanceOf(MockSubject::class, $mock);

    $this->assertNull($mock->constructorArgs());
  }

  /**
   * Tests mocking a class that does not exist.
   */
  public function testPrepareMockMissingClass(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Class NoSuchClass does not exist');

    $this->prepareMock('NoSuchClass');
  }

}

/**
 * Subject class used to exercise mock preparation.
 */
class MockSubject {

  /**
   * Arguments the constructor received, NULL when the constructor did not run.
   *
   * @var array<string, string>|null
   */
  protected ?array $constructorArgs = NULL;

  /**
   * Constructs the subject.
   */
  public function __construct(string $name = '') {
    $this->constructorArgs = ['name' => $name];
  }

  /**
   * Returns the arguments the constructor received.
   *
   * @return array<string, string>|null
   *   Constructor arguments, or NULL when the constructor did not run.
   */
  public function constructorArgs(): ?array {
    return $this->constructorArgs;
  }

  /**
   * Returns a fixed greeting.
   */
  public function greet(): string {
    return 'original greeting';
  }

  /**
   * Returns a fixed total.
   */
  public function total(): int {
    return 0;
  }

  /**
   * Returns the given value unchanged.
   */
  public function echoBack(string $value): string {
    return $value;
  }

}
