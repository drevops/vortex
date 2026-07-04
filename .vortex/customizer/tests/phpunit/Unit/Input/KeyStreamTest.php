<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Tests\Unit\Input;

use DrevOps\Customizer\Input\ArrayKeyStream;
use DrevOps\Customizer\Input\Key;
use DrevOps\Customizer\Input\KeyName;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the key model and the in-memory key stream.
 */
#[CoversClass(Key::class)]
#[CoversClass(ArrayKeyStream::class)]
#[Group('input')]
final class KeyStreamTest extends TestCase {

  public function testCharAndNamed(): void {
    $char = Key::char('a');
    $this->assertTrue($char->isChar());
    $this->assertSame('a', $char->char);
    $this->assertFalse($char->is(KeyName::Enter));

    $named = Key::named(KeyName::Enter);
    $this->assertFalse($named->isChar());
    $this->assertTrue($named->is(KeyName::Enter));
  }

  public function testOfExpandsStringsAndKeys(): void {
    $stream = ArrayKeyStream::of('ab', Key::named(KeyName::Enter), '');

    $first = $stream->read();
    $this->assertInstanceOf(Key::class, $first);
    $this->assertSame('a', $first->char);

    $second = $stream->read();
    $this->assertInstanceOf(Key::class, $second);
    $this->assertSame('b', $second->char);

    $third = $stream->read();
    $this->assertInstanceOf(Key::class, $third);
    $this->assertTrue($third->is(KeyName::Enter));

    $this->assertNotInstanceOf(Key::class, $stream->read());
  }

  public function testConstructReindexes(): void {
    $stream = new ArrayKeyStream([5 => Key::char('x')]);

    $key = $stream->read();
    $this->assertInstanceOf(Key::class, $key);
    $this->assertSame('x', $key->char);
    $this->assertNotInstanceOf(Key::class, $stream->read());
  }

}
