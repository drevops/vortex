<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Tests\Unit\Engine;

use DrevOps\Customizer\Config\ConfigLoader;
use DrevOps\Customizer\Engine\Engine;
use DrevOps\Customizer\Handler\Context;
use DrevOps\Customizer\Handler\HandlerRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests conditional gating and post-submit fix-ups in the engine.
 */
#[CoversClass(Engine::class)]
#[Group('engine')]
final class EngineConditionalTest extends TestCase {

  public function testInactiveFieldExcluded(): void {
    $engine = $this->engine([
      'panels' => [['id' => 'p', 'fields' => [
        ['id' => 'theme', 'default' => 'olivero'],
        ['id' => 'custom_theme', 'default' => 'mytheme', 'when' => ['field' => 'theme', 'eq' => 'custom']],
      ]]],
    ]);

    $answers = $engine->run([], new Context());
    $this->assertArrayHasKey('theme', $answers);
    $this->assertArrayNotHasKey('custom_theme', $answers);

    $answers = $engine->run(['theme' => 'custom'], new Context());
    $this->assertArrayHasKey('custom_theme', $answers);
    $this->assertSame('mytheme', $answers['custom_theme']);
  }

  public function testForceFixupAutoResolves(): void {
    $engine = $this->engine([
      'panels' => [['id' => 'p', 'fields' => [
        ['id' => 'provision', 'default' => 'database'],
        ['id' => 'database_source', 'default' => 'url'],
      ]]],
      'fixups' => [
        ['when' => ['field' => 'provision', 'eq' => 'profile'], 'set' => 'database_source', 'to' => 'none'],
      ],
    ]);

    $this->assertSame('url', $engine->run([], new Context())['database_source']);
    // No input for database_source: the fix-up resolves it without prompting.
    $this->assertSame('none', $engine->run(['provision' => 'profile'], new Context())['database_source']);
  }

  public function testFixupWithoutTargetIsSkipped(): void {
    $engine = $this->engine([
      'panels' => [['id' => 'p', 'fields' => [['id' => 'a', 'default' => 'x']]]],
      'fixups' => [
        // The "when" matches but there is no "set" target: the rule is ignored.
        ['when' => ['field' => 'a', 'eq' => 'x']],
      ],
    ]);

    $this->assertSame(['a' => 'x'], $engine->run([], new Context()));
  }

  public function testMultiFieldConditional(): void {
    // A when can depend on any number of fields via all / any / not.
    $engine = $this->engine([
      'panels' => [['id' => 'p', 'fields' => [
        ['id' => 'a', 'default' => 'x'],
        ['id' => 'b', 'default' => 'y'],
        ['id' => 'c', 'default' => 'z', 'when' => ['all' => [['field' => 'a', 'eq' => 'x'], ['field' => 'b', 'eq' => 'y']]]],
      ]]],
    ]);

    // Both conditions hold: c is active.
    $this->assertArrayHasKey('c', $engine->run([], new Context()));

    // One condition fails: c is gated out.
    $this->assertArrayNotHasKey('c', $engine->run(['b' => 'other'], new Context()));
  }

  public function testMergeCustomFixup(): void {
    $engine = $this->engine([
      'panels' => [['id' => 'p', 'fields' => [
        ['id' => 'profile', 'default' => 'standard'],
        ['id' => 'profile_custom', 'default' => ''],
      ]]],
      'fixups' => [
        ['when' => ['field' => 'profile', 'eq' => 'custom'], 'set' => 'profile', 'to' => ['field' => 'profile_custom']],
      ],
    ]);

    $answers = $engine->run(['profile' => 'custom', 'profile_custom' => 'my_profile'], new Context());
    $this->assertSame('my_profile', $answers['profile']);
  }

  public function testCascadingDeactivation(): void {
    $engine = $this->engine([
      'panels' => [['id' => 'p', 'fields' => [
        ['id' => 'a', 'default' => 'x'],
        ['id' => 'b', 'default' => 'y', 'when' => ['field' => 'a', 'eq' => 'x']],
        ['id' => 'c', 'default' => 'z', 'when' => ['field' => 'b', 'eq' => 'y']],
      ]]],
    ]);

    $this->assertSame(['a' => 'x', 'b' => 'y', 'c' => 'z'], $engine->run([], new Context()));
    $this->assertSame(['a' => 'off'], $engine->run(['a' => 'off'], new Context()));
  }

  /**
   * Build an engine over decoded config data with no handlers.
   *
   * @param array<string,mixed> $data
   *   The decoded configuration.
   */
  protected function engine(array $data): Engine {
    $config = (new ConfigLoader())->fromArray($data);

    return new Engine($config, new HandlerRegistry());
  }

}
