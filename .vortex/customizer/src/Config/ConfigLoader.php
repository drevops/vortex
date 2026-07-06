<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Config;

use DrevOps\Customizer\Derive\Transform;
use Symfony\Component\Yaml\Yaml;

/**
 * Loads and validates YAML configuration into the Config model.
 *
 * @package DrevOps\Customizer\Config
 */
class ConfigLoader {

  /**
   * Load and normalize a config from one or more YAML files (later wins).
   *
   * @param string[] $paths
   *   Paths to YAML files, merged in order.
   */
  public function loadFiles(array $paths): Config {
    $data = [];

    foreach ($paths as $path) {
      if (!is_file($path)) {
        throw new ConfigException(sprintf('Config file not found: %s', $path));
      }

      $parsed = Yaml::parseFile($path);
      if (!is_array($parsed)) {
        throw new ConfigException(sprintf('Config file is not a mapping: %s', $path));
      }

      $data = array_replace_recursive($data, $parsed);
    }

    return $this->fromArray($data);
  }

  /**
   * Build a Config from a decoded array.
   *
   * @param array<array-key,mixed> $data
   *   The decoded configuration.
   */
  public function fromArray(array $data): Config {
    $seen = [];
    $panels = $this->buildPanels($data['panels'] ?? [], $seen);

    $buttons = $data['buttons'] ?? TRUE;
    $show_buttons = is_array($buttons) ? TRUE : (bool) $buttons;
    $submit_label = is_array($buttons) ? $this->toString($buttons['submit'] ?? 'Submit') : 'Submit';
    $cancel_label = is_array($buttons) ? $this->toString($buttons['cancel'] ?? 'Cancel') : 'Cancel';

    return new Config(
      $this->toString($data['title'] ?? 'Customizer'),
      $this->toString($data['subject'] ?? ''),
      $panels,
      $this->buildFixups($data['fixups'] ?? []),
      $this->toString($data['theme'] ?? ''),
      $this->toString($data['banner'] ?? ''),
      $show_buttons,
      $submit_label,
      $cancel_label,
      (bool) ($data['clear_on_exit'] ?? TRUE),
      $this->buildProcessors($data['processors'] ?? []),
    );
  }

  /**
   * Build the list of raw fix-up rules from decoded data.
   *
   * @param mixed $items
   *   The decoded fix-ups list.
   *
   * @return array<int,array<array-key,mixed>>
   *   The fix-up rules, each a raw mapping.
   */
  protected function buildFixups(mixed $items): array {
    if (!is_array($items)) {
      return [];
    }

    $fixups = [];
    foreach ($items as $item) {
      if (is_array($item)) {
        $fixups[] = $item;
      }
    }

    return $fixups;
  }

  /**
   * Build a list of panels from decoded data.
   *
   * @param mixed $items
   *   The decoded panels list.
   * @param array<string,bool> $seen
   *   Field ids already seen, to detect duplicates across the tree.
   *
   * @return \DrevOps\Customizer\Config\Panel[]
   *   The built panels.
   */
  protected function buildPanels(mixed $items, array &$seen): array {
    if (!is_array($items)) {
      throw new ConfigException('The "panels" key must be a list of panels.');
    }

    $panels = [];
    foreach ($items as $item) {
      if (!is_array($item) || !isset($item['id'])) {
        throw new ConfigException('Each panel must be a mapping with an "id".');
      }

      $id = $this->toString($item['id']);
      $panels[] = new Panel(
        $id,
        $this->toString($item['title'] ?? $id),
        $this->toString($item['description'] ?? ''),
        $this->buildFields($item['fields'] ?? [], $seen, $id),
        $this->buildPanels($item['panels'] ?? [], $seen),
      );
    }

    return $panels;
  }

  /**
   * Build a list of fields from decoded data.
   *
   * @param mixed $items
   *   The decoded fields list.
   * @param array<string,bool> $seen
   *   Field ids already seen, to detect duplicates.
   * @param string $panel_id
   *   The owning panel id (for error messages).
   *
   * @return \DrevOps\Customizer\Config\Field[]
   *   The built fields.
   */
  protected function buildFields(mixed $items, array &$seen, string $panel_id): array {
    if (!is_array($items)) {
      throw new ConfigException(sprintf('The "fields" of panel "%s" must be a list.', $panel_id));
    }

    $fields = [];
    foreach ($items as $item) {
      if (!is_array($item) || !isset($item['id'])) {
        throw new ConfigException(sprintf('Each field in panel "%s" must be a mapping with an "id".', $panel_id));
      }

      $id = $this->toString($item['id']);
      if (isset($seen[$id])) {
        throw new ConfigException(sprintf('Duplicate field id "%s".', $id));
      }
      $seen[$id] = TRUE;

      $type = FieldType::tryFrom($this->toString($item['type'] ?? 'text'));
      if (!$type instanceof FieldType) {
        throw new ConfigException(sprintf('Field "%s" has an unknown type "%s".', $id, $this->toString($item['type'] ?? '')));
      }

      $derive = isset($item['derive']) && is_array($item['derive']) ? $item['derive'] : NULL;
      if ($derive !== NULL && isset($derive['transform'])) {
        $transform = $this->toString($derive['transform']);
        if ($transform !== '' && !Transform::supports($transform)) {
          throw new ConfigException(sprintf('Field "%s" uses an unknown derive transform "%s".', $id, $transform));
        }
      }

      $fields[] = new Field(
        $id,
        $this->toString($item['label'] ?? $id),
        $this->toString($item['description'] ?? ''),
        $type,
        $item['default'] ?? $this->defaultFor($type),
        $this->buildOptions($item['options'] ?? [], $id),
        (bool) ($item['required'] ?? FALSE),
        (bool) ($item['machine'] ?? FALSE),
        isset($item['when']) && is_array($item['when']) ? $item['when'] : NULL,
        $derive,
        isset($item['discover']) && is_array($item['discover']) ? $item['discover'] : NULL,
        $this->toInt($item['weight'] ?? 0),
      );
    }

    return $fields;
  }

  /**
   * Build the list of field-less processors from decoded data.
   *
   * @param mixed $items
   *   The decoded processors list.
   *
   * @return array<int,array{id:string,weight:int}>
   *   The processors, each an id and a weight.
   */
  protected function buildProcessors(mixed $items): array {
    if (!is_array($items)) {
      return [];
    }

    $processors = [];
    foreach ($items as $item) {
      if (is_array($item) && isset($item['id'])) {
        $processors[] = ['id' => $this->toString($item['id']), 'weight' => $this->toInt($item['weight'] ?? 0)];
      }
    }

    return $processors;
  }

  /**
   * Build a map of options keyed by value from decoded data.
   *
   * @param mixed $items
   *   The decoded options list.
   * @param string $field_id
   *   The owning field id (for error messages).
   *
   * @return array<string,\DrevOps\Customizer\Config\Option>
   *   Options keyed by value.
   */
  protected function buildOptions(mixed $items, string $field_id): array {
    if (!is_array($items)) {
      throw new ConfigException(sprintf('The "options" of field "%s" must be a list.', $field_id));
    }

    $options = [];
    foreach ($items as $item) {
      if (!is_array($item) || !isset($item['value'])) {
        throw new ConfigException(sprintf('Each option of field "%s" must be a mapping with a "value".', $field_id));
      }

      $value = $this->toString($item['value']);
      $options[$value] = new Option(
        $value,
        $this->toString($item['label'] ?? $value),
        $this->toString($item['description'] ?? ''),
      );
    }

    return $options;
  }

  /**
   * The engine default for a field type when none is declared.
   */
  protected function defaultFor(FieldType $field_type): mixed {
    return match ($field_type) {
      FieldType::MultiSelect => [],
      FieldType::Confirm => FALSE,
      default => '',
    };
  }

  /**
   * Coerce a decoded scalar value to a string (non-scalars become empty).
   *
   * @param mixed $value
   *   The decoded value.
   */
  protected function toString(mixed $value): string {
    return is_scalar($value) ? (string) $value : '';
  }

  /**
   * Coerce a decoded scalar value to an int (non-scalars become zero).
   *
   * @param mixed $value
   *   The decoded value.
   */
  protected function toInt(mixed $value): int {
    return is_scalar($value) ? (int) $value : 0;
  }

}
