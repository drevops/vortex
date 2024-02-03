<?php

namespace DrevOps\Installer\Utils;

/**
 * Validator.
 */
class Validator {

  /**
   * Validate the value is not empty.
   *
   * @param mixed $value
   *   The value.
   *
   * @throws \Exception
   *   If the value is empty.
   */
  public static function notEmpty(mixed $value): void {
    $value = is_array($value) ? $value : [$value];
    $value = array_filter($value);

    if (empty($value)) {
      throw new \Exception('The value cannot be empty.');
    }
  }

  /**
   * Validate the value is a human name.
   *
   * @param mixed $value
   *   The value.
   *
   * @throws \Exception
   *   If the value is not a human name.
   */
  public static function humanName(mixed $value): void {
    if (!preg_match('/^[a-zA-Z0-9\- ]+$/', (string) $value)) {
      throw new \Exception('The name must contain only letters, numbers, and dashes.');
    }
  }

  /**
   * Validate the value is a machine name.
   *
   * @param mixed $value
   *   The value.
   *
   * @throws \Exception
   *   If the value is not a machine name.
   */
  public static function machineName(mixed $value): void {
    if (!preg_match('/^[a-z0-9_]+$/', (string) $value)) {
      throw new \Exception('The name must contain only lowercase letters, numbers, and underscores.');
    }
  }

  /**
   * Validate the value is in a list.
   *
   * @param array $items
   *   The list of items.
   * @param mixed $value
   *   The value.
   * @param bool $is_multiple
   *   Whether the value is a list.
   *
   * @throws \Exception
   *   If the value is not in the list.
   */
  public static function inList($items, mixed $value, $is_multiple = FALSE): void {
    $value = is_array($value) ? $value : [$value];

    if ($is_multiple) {
      $items = array_map('strtolower', $items);
      $value = array_map('strtolower', $value);
    }

    $diff = array_diff($value, $items);
    if ($diff !== []) {
      throw new \Exception(sprintf('The following values are not valid: %s', implode(', ', $diff)));
    }
  }

  /**
   * Validate the value is a valid Docker image name.
   *
   * @param mixed $value
   *   The value.
   */
  public static function dockerImageName(string $value): void {
    $pattern = '%^(?<Name>(?<=^)(?:(?<Domain>(?:(?:localhost|[\w-]+(?:\.[\w-]+)+)(?::\d+)?)|[\w]+:\d+)\/)?\/?(?<Namespace>(?:(?:[a-z0-9]+(?:(?:[._]|__|[-]*)[a-z0-9]+)*)\/)*)(?<Repo>[a-z0-9-]+))[:@]?(?<Reference>(?<=:)(?<Tag>[\w][\w.-]{0,127})|(?<=@)(?<Digest>[A-Za-z][A-Za-z0-9]*(?:[-_+.][A-Za-z][A-Za-z0-9]*)*[:][0-9A-Fa-f]{32,}))?$%m';
    if (!preg_match($pattern, $value)) {
      throw new \Exception('The name must contain only lowercase letters, numbers, dashes, and underscores.');
    }
  }

  /**
   * Validate the value is a valid URL.
   *
   * @param mixed $value
   *   The value.
   * @param bool $require_protocol
   *   Whether the URL must have a protocol.
   *
   * @throws \Exception
   *   If the value is not a valid URL.
   */
  public static function url(mixed $value, $require_protocol = FALSE): void {
    if ($require_protocol === FALSE && !str_contains((string) $value, '://')) {
      // If the URL starts with '//' (protocol-relative), prepend with 'http:'.
      $value = (str_starts_with((string) $value, '//')) ? 'http:' . $value : 'http://' . $value;
    }

    $parsed = parse_url((string) $value);

    if ($parsed === FALSE || !isset($parsed['host'])) {
      throw new \Exception('The URL is not valid.');
    }

    if ($require_protocol && !isset($parsed['scheme'])) {
      throw new \Exception('The URL is not valid.');
    }

    $hos_pattern = '/^([a-z0-9]+|\_[a-z0-9]+\.\_[a-z0-9]+)(?:[.-][a-z0-9]+)*\.[a-z]{2,}$/i';
    if (!preg_match($hos_pattern, $parsed['host'])) {
      throw new \Exception('The URL is not valid.');
    }
  }

}
