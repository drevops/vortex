<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Unit\Config;

use DrevOps\Tui\Config\Config;
use DrevOps\Tui\Config\ConfigException;
use DrevOps\Tui\Config\ConfigLoader;
use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Config\FieldType;
use DrevOps\Tui\Config\Option;
use DrevOps\Tui\Config\Panel;
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

  public function testBuildsNestedConfig(): void {
    $config = (new ConfigLoader())->fromArray([
      'title' => 'Demo',
      'subject' => 'Acme',
      'panels' => [
        ['id' => 'general', 'fields' => [
          ['id' => 'name', 'type' => 'text', 'default' => 'Acme', 'required' => TRUE],
          ['id' => 'email', 'type' => 'text'],
        ]],
        ['id' => 'drupal', 'fields' => [
          ['id' => 'profile', 'type' => 'select', 'options' => [['value' => 'standard', 'label' => 'Standard']]],
        ], 'panels' => [
          ['id' => 'advanced', 'fields' => [['id' => 'theme_debug', 'type' => 'confirm']]],
        ]],
      ],
    ]);

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
    $this->assertCount(4, $config->fields());
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
    yield 'panels not a list' => [['panels' => 'x'], 'must be a list of panels'];
    yield 'fields not a list' => [['panels' => [['id' => 'p', 'fields' => 'x']]], 'must be a list'];
    yield 'options not a list' => [['panels' => [['id' => 'p', 'fields' => [['id' => 'f', 'type' => 'select', 'options' => 'x']]]]], 'must be a list'];
    yield 'unknown transform' => [['panels' => [['id' => 'p', 'fields' => [['id' => 'f', 'derive' => ['template' => '{{x}}', 'transform' => 'bogus']]]]]], 'unknown derive transform'];
  }

  public function testFixups(): void {
    // A non-array fixups value is ignored; only array items are kept.
    $ignored = (new ConfigLoader())->fromArray(['fixups' => 'notalist', 'panels' => []]);
    $this->assertSame([], $ignored->fixups);

    $config = (new ConfigLoader())->fromArray([
      'fixups' => [['when' => ['field' => 'a', 'eq' => 'b']], 'skip-me'],
      'panels' => [],
    ]);
    $this->assertCount(1, $config->fixups);
  }

  public function testButtonsAndClearOnExit(): void {
    $default = (new ConfigLoader())->fromArray(['panels' => []]);
    $this->assertTrue($default->buttons);
    $this->assertSame('Submit', $default->submitLabel);
    $this->assertSame('Cancel', $default->cancelLabel);
    $this->assertTrue($default->clearOnExit);

    $off = (new ConfigLoader())->fromArray(['buttons' => FALSE, 'clear_on_exit' => FALSE, 'panels' => []]);
    $this->assertFalse($off->buttons);
    $this->assertFalse($off->clearOnExit);

    $custom = (new ConfigLoader())->fromArray(['buttons' => ['submit' => 'Finish', 'cancel' => 'Abort'], 'panels' => []]);
    $this->assertTrue($custom->buttons);
    $this->assertSame('Finish', $custom->submitLabel);
    $this->assertSame('Abort', $custom->cancelLabel);
  }

  public function testBanner(): void {
    $this->assertSame('', (new ConfigLoader())->fromArray(['panels' => []])->banner);
    $this->assertSame("a\nb", (new ConfigLoader())->fromArray(['banner' => "a\nb", 'panels' => []])->banner);
  }

  public function testColorAndUnicode(): void {
    // Absent keys stay NULL so the TUI auto-detects.
    $default = (new ConfigLoader())->fromArray(['panels' => []]);
    $this->assertNull($default->color);
    $this->assertNull($default->unicode);

    // Present keys force the mode.
    $forced = (new ConfigLoader())->fromArray(['color' => FALSE, 'unicode' => TRUE, 'panels' => []]);
    $this->assertFalse($forced->color);
    $this->assertTrue($forced->unicode);
  }

  public function testProcessors(): void {
    // A non-array processors value yields none.
    $this->assertSame([], (new ConfigLoader())->fromArray(['processors' => 'x', 'panels' => []])->processors);

    // Only array items with an "id" are kept; the rest are ignored.
    $config = (new ConfigLoader())->fromArray([
      'processors' => [['id' => 'dotenv', 'weight' => -10], 'skip', ['weight' => 5]],
      'panels' => [],
    ]);
    $this->assertSame([['id' => 'dotenv', 'weight' => -10]], $config->processors);
  }

}
