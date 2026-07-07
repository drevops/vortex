<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Unit\Schema;

use DrevOps\Tui\Builder\Form;
use DrevOps\Tui\Builder\PanelBuilder;
use DrevOps\Tui\Schema\AgentHelp;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the agent help generator.
 */
#[CoversClass(AgentHelp::class)]
#[Group('schema')]
final class AgentHelpTest extends TestCase {

  public function testGenerate(): void {
    $config = Form::create('T')
      ->panel('p', 'p', function (PanelBuilder $p): void {
        $p->text('name', 'Site name')->required();
        $p->confirm('agree', 'Agree');
      })
      ->build();

    $help = (new AgentHelp($config, 'VORTEX_'))->generate();

    $this->assertStringContainsString('--no-interaction', $help);
    $this->assertStringContainsString('--prompts', $help);
    $this->assertStringContainsString('VORTEX_<ID>', $help);
    $this->assertStringContainsString('Precedence: --prompts > environment > discovered > derived > default.', $help);
    $this->assertStringContainsString('name [text] (required) - Site name', $help);
    $this->assertStringContainsString('agree [confirm] - Agree', $help);
  }

  public function testNoEnvPrefixOmitsEnvLine(): void {
    $config = Form::create('T')
      ->panel('p', 'p', function (PanelBuilder $p): void {
        $p->text('x');
      })
      ->build();

    $help = (new AgentHelp($config))->generate();

    $this->assertStringNotContainsString('environment variables named', $help);
  }

}
