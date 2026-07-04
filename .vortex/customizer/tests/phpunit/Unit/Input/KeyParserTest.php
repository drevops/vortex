<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Tests\Unit\Input;

use DrevOps\Customizer\Input\Key;
use DrevOps\Customizer\Input\KeyName;
use DrevOps\Customizer\Input\KeyParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the raw key parser.
 */
#[CoversClass(KeyParser::class)]
#[Group('input')]
final class KeyParserTest extends TestCase {

  /**
   * Byte buffers parse into the expected key sequence.
   *
   * @param string $bytes
   *   The raw bytes.
   * @param list<string> $expected
   *   The expected key descriptions.
   */
  #[DataProvider('dataProviderParse')]
  public function testParse(string $bytes, array $expected): void {
    $keys = array_map($this->describe(...), (new KeyParser())->parse($bytes));

    $this->assertSame($expected, $keys);
  }

  /**
   * Describe a key for assertion.
   *
   * @param \DrevOps\Customizer\Input\Key $key
   *   The key.
   *
   * @return string
   *   The description.
   */
  protected function describe(Key $key): string {
    if ($key->isChar()) {
      return 'char:' . ($key->char ?? '');
    }

    return $key->name instanceof KeyName ? $key->name->name : '?';
  }

  /**
   * Data provider for testParse().
   *
   * @return \Iterator<string,array{string,list<string>}>
   *   Byte buffers and expected key descriptions.
   */
  public static function dataProviderParse(): \Iterator {
    yield 'chars' => ['abc', ['char:a', 'char:b', 'char:c']];
    yield 'enter cr' => ["\r", ['Enter']];
    yield 'enter lf' => ["\n", ['Enter']];
    yield 'backspace' => ["\x7f", ['Backspace']];
    yield 'tab' => ["\t", ['Tab']];
    yield 'space' => [' ', ['Space']];
    yield 'up' => ["\033[A", ['Up']];
    yield 'down' => ["\033[B", ['Down']];
    yield 'right' => ["\033[C", ['Right']];
    yield 'left' => ["\033[D", ['Left']];
    yield 'home csi' => ["\033[H", ['Home']];
    yield 'end csi' => ["\033[F", ['End']];
    yield 'pageup' => ["\033[5~", ['PageUp']];
    yield 'pagedown' => ["\033[6~", ['PageDown']];
    yield 'delete' => ["\033[3~", ['Delete']];
    yield 'home tilde' => ["\033[1~", ['Home']];
    yield 'end tilde' => ["\033[4~", ['End']];
    yield 'bare escape' => ["\033", ['Escape']];
    yield 'escape then char' => ["\033x", ['Escape', 'char:x']];
    yield 'unknown csi degrades' => ["\033[Z", ['Escape']];
    yield 'mouse wheel up' => ["\033[<64;10;5M", ['MouseWheelUp']];
    yield 'mouse wheel down' => ["\033[<65;10;5M", ['MouseWheelDown']];
    yield 'mouse other ignored' => ["\033[<0;1;1M", []];
    yield 'combo' => ["a\033[Bb", ['char:a', 'Down', 'char:b']];
  }

}
