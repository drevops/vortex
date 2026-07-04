<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Tests\Unit\Schema;

use DrevOps\Customizer\Config\ConfigLoader;
use DrevOps\Customizer\Schema\AgentHelp;
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
    $config = (new ConfigLoader())->fromArray(['panels' => [['id' => 'p', 'fields' => [
      ['id' => 'name', 'type' => 'text', 'label' => 'Site name', 'required' => TRUE],
      ['id' => 'agree', 'type' => 'confirm', 'label' => 'Agree'],
    ]]]]);

    $help = (new AgentHelp($config, 'VORTEX_'))->generate();

    $this->assertStringContainsString('--no-interaction', $help);
    $this->assertStringContainsString('--prompts', $help);
    $this->assertStringContainsString('VORTEX_<ID>', $help);
    $this->assertStringContainsString('Precedence: --prompts > environment > discovered > derived > default.', $help);
    $this->assertStringContainsString('name [text] (required) - Site name', $help);
    $this->assertStringContainsString('agree [confirm] - Agree', $help);
  }

  public function testNoEnvPrefixOmitsEnvLine(): void {
    $config = (new ConfigLoader())->fromArray(['panels' => [['id' => 'p', 'fields' => [['id' => 'x', 'type' => 'text']]]]]);

    $help = (new AgentHelp($config))->generate();

    $this->assertStringNotContainsString('environment variables named', $help);
  }

}
