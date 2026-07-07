<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Unit\Engine;

use DrevOps\Tui\Config\ConfigLoader;
use DrevOps\Tui\Engine\Engine;
use DrevOps\Tui\Handler\Context;
use DrevOps\Tui\Handler\HandlerRegistry;
use DrevOps\Tui\Resolver\InputResolver;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the full non-interactive precedence chain end to end.
 */
#[CoversClass(Engine::class)]
#[CoversClass(InputResolver::class)]
#[Group('engine')]
final class EngineNonInteractiveTest extends TestCase {

  public function testFullPrecedence(): void {
    vfsStream::setup('proj', NULL, ['.env' => "DETECTED=from_env\n"]);
    $dir = vfsStream::url('proj');

    $config = (new ConfigLoader())->fromArray([
      'panels' => [['id' => 'p', 'fields' => [
        ['id' => 'src', 'default' => 'seed'],
        ['id' => 'target', 'type' => 'text', 'default' => 'static', 'derive' => ['template' => 'd-{{src}}'], 'discover' => ['dotenv' => 'DETECTED']],
      ]]],
    ]);
    $resolver = new InputResolver('VORTEX_');
    $engine = new Engine($config, new HandlerRegistry());

    // Static default is overtaken by the derived value (fresh install).
    $inputs = $resolver->resolve($config->fields(), '', []);
    $this->assertSame('d-seed', $engine->run($inputs, new Context($dir, [], FALSE))['target']);

    // Detected (update mode) wins over derived.
    $inputs = $resolver->resolve($config->fields(), '', []);
    $this->assertSame('from_env', $engine->run($inputs, new Context($dir, [], TRUE))['target']);

    // Env wins over detected.
    $inputs = $resolver->resolve($config->fields(), '', ['VORTEX_TARGET' => 'from_env_var']);
    $this->assertSame('from_env_var', $engine->run($inputs, new Context($dir, [], TRUE))['target']);

    // --prompts wins over env.
    $inputs = $resolver->resolve($config->fields(), '{"target": "from_prompts"}', ['VORTEX_TARGET' => 'from_env_var']);
    $this->assertSame('from_prompts', $engine->run($inputs, new Context($dir, [], TRUE))['target']);
  }

}
