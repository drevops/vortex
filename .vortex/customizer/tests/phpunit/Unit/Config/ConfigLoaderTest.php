<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Tests\Unit\Config;

use DrevOps\Customizer\Config\Config;
use DrevOps\Customizer\Config\ConfigException;
use DrevOps\Customizer\Config\ConfigLoader;
use DrevOps\Customizer\Config\Field;
use DrevOps\Customizer\Config\FieldType;
use DrevOps\Customizer\Config\Option;
use DrevOps\Customizer\Config\Panel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the configuration loader and model.
 */
#[CoversClass(ConfigLoader::class)]
#[CoversClass(Config::class)]
#[CoversClass(Panel::class)]
#[CoversClass(Field::class)]
#[CoversClass(Option::class)]
#[Group('config')]
final class ConfigLoaderTest extends TestCase {

  public function testLoadValidFile(): void {
    $config = (new ConfigLoader())->loadFiles([__DIR__ . '/../../Fixtures/config/valid.yml']);

    $this->assertSame('Demo', $config->title);
    $this->assertSame('Acme', $config->subject);
    $this->assertCount(2, $config->panels);

    $general = $config->panels[0];
    $this->assertSame('general', $general->id);
    $this->assertCount(2, $general->fields);

    $name = $general->fields[0];
    $this->assertSame(FieldType::Text, $name->type);
    $this->assertSame('Acme', $name->default);
    $this->assertTrue($name->required);

    $drupal = $config->panels[1];
    $profile = $drupal->fields[0];
    $this->assertSame(FieldType::Select, $profile->type);
    $standard = $profile->option('standard');
    $this->assertInstanceOf(Option::class, $standard);
    $this->assertSame('Standard', $standard->label);
    $this->assertNotInstanceOf(Option::class, $profile->option('missing'));

    $this->assertCount(1, $drupal->panels);
    $this->assertSame('advanced', $drupal->panels[0]->id);

    // field() resolves nested fields across sub-panels.
    $this->assertSame('theme_debug', $config->field('theme_debug')?->id);
    $this->assertNotInstanceOf(Field::class, $config->field('nope'));
  }

  public function testTypeDefaults(): void {
    $config = (new ConfigLoader())->fromArray([
      'panels' => [
        ['id' => 'p', 'fields' => [
          ['id' => 'ms', 'type' => 'multiselect'],
          ['id' => 'cb', 'type' => 'confirm'],
          ['id' => 'tx', 'type' => 'text'],
        ]],
      ],
    ]);

    $this->assertSame([], $config->field('ms')?->default);
    $this->assertFalse($config->field('cb')?->default);
    $this->assertSame('', $config->field('tx')?->default);
  }

  /**
   * Malformed configs raise a ConfigException.
   *
   * @param array<array-key,mixed> $data
   *   The malformed configuration.
   * @param string $message
   *   The expected exception message fragment.
   */
  #[DataProvider('dataProviderMalformedThrows')]
  public function testMalformedThrows(array $data, string $message): void {
    $this->expectException(ConfigException::class);
    $this->expectExceptionMessage($message);
    (new ConfigLoader())->fromArray($data);
  }

  /**
   * Data provider for testMalformedThrows().
   *
   * @return \Iterator<string, array{array<mixed>, string}>
   *   Malformed configs and the expected message fragment.
   */
  public static function dataProviderMalformedThrows(): \Iterator {
    yield 'panel missing id' => [['panels' => [['title' => 'x']]], 'must be a mapping with an "id"'];
    yield 'field missing id' => [['panels' => [['id' => 'p', 'fields' => [['label' => 'x']]]]], 'must be a mapping with an "id"'];
    yield 'unknown type' => [['panels' => [['id' => 'p', 'fields' => [['id' => 'f', 'type' => 'bogus']]]]], 'unknown type'];
    yield 'bad option' => [['panels' => [['id' => 'p', 'fields' => [['id' => 'f', 'type' => 'select', 'options' => [['label' => 'x']]]]]]], 'must be a mapping with a "value"'];
    yield 'duplicate id' => [['panels' => [['id' => 'p', 'fields' => [['id' => 'dup'], ['id' => 'dup']]]]], 'Duplicate field id'];
  }

}
