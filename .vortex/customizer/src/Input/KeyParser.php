<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Input;

/**
 * Parses a raw terminal byte buffer into a list of keys.
 *
 * Recognizes printable characters, Enter/Backspace/Tab/Space, bare Escape,
 * CSI arrows and navigation keys (Home/End/PageUp/PageDown/Delete), and SGR
 * mouse-wheel events. Unrecognized escape sequences degrade to Escape, and
 * unknown mouse events are consumed without emitting a key.
 *
 * @package DrevOps\Customizer\Input
 */
class KeyParser {

  /**
   * Parse a byte buffer into keys.
   *
   * @param string $bytes
   *   The raw bytes.
   *
   * @return \DrevOps\Customizer\Input\Key[]
   *   The parsed keys.
   */
  public function parse(string $bytes): array {
    $keys = [];
    $length = strlen($bytes);
    $i = 0;

    while ($i < $length) {
      if ($bytes[$i] === "\033") {
        [$key, $consumed] = $this->parseEscape($bytes, $i);
        if ($key instanceof Key) {
          $keys[] = $key;
        }

        $i += $consumed;
        continue;
      }

      $keys[] = $this->parseSimple($bytes[$i]);
      $i++;
    }

    return $keys;
  }

  /**
   * Parse a single non-escape byte.
   *
   * @param string $char
   *   The byte.
   *
   * @return \DrevOps\Customizer\Input\Key
   *   The key.
   */
  protected function parseSimple(string $char): Key {
    return match ($char) {
      "\r", "\n" => Key::named(KeyName::Enter),
      "\x7f", "\x08" => Key::named(KeyName::Backspace),
      "\t" => Key::named(KeyName::Tab),
      ' ' => Key::named(KeyName::Space),
      default => Key::char($char),
    };
  }

  /**
   * Parse an escape sequence starting at an offset.
   *
   * @param string $bytes
   *   The raw bytes.
   * @param int $start
   *   The offset of the ESC byte.
   *
   * @return array{\DrevOps\Customizer\Input\Key|null,int}
   *   The key (or NULL) and the number of bytes consumed.
   */
  protected function parseEscape(string $bytes, int $start): array {
    $length = strlen($bytes);

    if ($start + 1 >= $length || $bytes[$start + 1] !== '[') {
      return [Key::named(KeyName::Escape), 1];
    }

    if ($start + 2 < $length && $bytes[$start + 2] === '<') {
      return $this->parseMouse($bytes, $start);
    }

    $j = $start + 2;
    $params = '';
    while ($j < $length && ctype_digit($bytes[$j])) {
      $params .= $bytes[$j];
      $j++;
    }

    if ($j >= $length) {
      return [Key::named(KeyName::Escape), 1];
    }

    $name = $this->csiName($bytes[$j], $params);

    return [Key::named($name ?? KeyName::Escape), $j - $start + 1];
  }

  /**
   * Resolve a CSI final byte (and parameters) to a key name.
   *
   * @param string $final
   *   The final byte.
   * @param string $params
   *   The numeric parameters.
   *
   * @return \DrevOps\Customizer\Input\KeyName|null
   *   The key name, or NULL when unrecognized.
   */
  protected function csiName(string $final, string $params): ?KeyName {
    return match ($final) {
      'A' => KeyName::Up,
      'B' => KeyName::Down,
      'C' => KeyName::Right,
      'D' => KeyName::Left,
      'H' => KeyName::Home,
      'F' => KeyName::End,
      '~' => $this->tildeName($params),
      default => NULL,
    };
  }

  /**
   * Resolve a `CSI <n> ~` parameter to a key name.
   *
   * @param string $params
   *   The numeric parameters.
   *
   * @return \DrevOps\Customizer\Input\KeyName|null
   *   The key name, or NULL when unrecognized.
   */
  protected function tildeName(string $params): ?KeyName {
    return match ($params) {
      '1', '7' => KeyName::Home,
      '4', '8' => KeyName::End,
      '3' => KeyName::Delete,
      '5' => KeyName::PageUp,
      '6' => KeyName::PageDown,
      default => NULL,
    };
  }

  /**
   * Parse an SGR mouse sequence starting at an offset.
   *
   * @param string $bytes
   *   The raw bytes.
   * @param int $start
   *   The offset of the ESC byte.
   *
   * @return array{\DrevOps\Customizer\Input\Key|null,int}
   *   The key (or NULL) and the number of bytes consumed.
   */
  protected function parseMouse(string $bytes, int $start): array {
    $length = strlen($bytes);
    $j = $start + 3;
    $data = '';
    while ($j < $length && $bytes[$j] !== 'M' && $bytes[$j] !== 'm') {
      $data .= $bytes[$j];
      $j++;
    }

    if ($j >= $length) {
      return [Key::named(KeyName::Escape), 1];
    }

    $parts = explode(';', $data);
    $button = (int) $parts[0];
    $name = match ($button) {
      64 => KeyName::MouseWheelUp,
      65 => KeyName::MouseWheelDown,
      default => NULL,
    };

    return [$name instanceof KeyName ? Key::named($name) : NULL, $j - $start + 1];
  }

}
