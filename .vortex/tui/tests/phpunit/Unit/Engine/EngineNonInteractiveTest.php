<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Unit\Engine;

use DrevOps\Tui\Builder\Form;
use DrevOps\Tui\Builder\PanelBuilder;
use DrevOps\Tui\Derive\Derive;
use DrevOps\Tui\Discovery\Dotenv;
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

    $config = Form::create('T')
      ->panel('p', 'p', function (PanelBuilder $p): void {
        $p->text('src')->default('seed');
        $p->text('target')->default('static')->derive(new Derive('d-{{src}}'))->discover(new Dotenv('DETECTED'));
      })
      ->build();
    $resolver = new InputResolver('VORTEX_');
    $engine = new Engine($config, new HandlerRegistry());

    // Static default is overtaken by the derived value (fresh install).
    $inputs = $resolver->resolve($config->fields(), '', []);
    $this->assertSame('d-seed', $engine->collect($inputs, new Context($dir, [], FALSE))['target']);

    // Detected (update mode) wins over derived.
    $inputs = $resolver->resolve($config->fields(), '', []);
    $this->assertSame('from_env', $engine->collect($inputs, new Context($dir, [], TRUE))['target']);

    // Env wins over detected.
    $inputs = $resolver->resolve($config->fields(), '', ['VORTEX_TARGET' => 'from_env_var']);
    $this->assertSame('from_env_var', $engine->collect($inputs, new Context($dir, [], TRUE))['target']);

    // --prompts wins over env.
    $inputs = $resolver->resolve($config->fields(), '{"target": "from_prompts"}', ['VORTEX_TARGET' => 'from_env_var']);
    $this->assertSame('from_prompts', $engine->collect($inputs, new Context($dir, [], TRUE))['target']);
  }

}
