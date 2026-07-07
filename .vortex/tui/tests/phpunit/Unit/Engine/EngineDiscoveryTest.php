<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Unit\Engine;

use DrevOps\Tui\Builder\Form;
use DrevOps\Tui\Builder\PanelBuilder;
use DrevOps\Tui\Engine\Engine;
use DrevOps\Tui\Handler\Context;
use DrevOps\Tui\Handler\HandlerRegistry;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests discovery precedence and provenance in the engine.
 */
#[CoversClass(Engine::class)]
#[Group('engine')]
final class EngineDiscoveryTest extends TestCase {

  /**
   * The virtual project directory.
   */
  protected string $dir;

  protected function setUp(): void {
    parent::setUp();
    vfsStream::setup('project', NULL, [
      '.env' => "DRUPAL_PROFILE=minimal\nPROFILE=from_env\n",
      'composer.json' => '{"name": "acme/site"}',
    ]);
    $this->dir = vfsStream::url('project');
  }

  public function testDetectsInUpdateMode(): void {
    $engine = $this->engine(function (PanelBuilder $p): void {
      $p->text('profile')->default('standard')->discover(['dotenv' => 'DRUPAL_PROFILE']);
      $p->text('name')->default('')->discover(['json' => ['file' => 'composer.json', 'path' => 'name']]);
    });

    $answers = $engine->run([], new Context($this->dir, [], TRUE));

    $this->assertSame('minimal', $answers['profile']);
    $this->assertSame('acme/site', $answers['name']);
    $this->assertSame('detected', $engine->provenance()['profile']);
    $this->assertSame('detected', $engine->provenance()['name']);
  }

  public function testFreshInstallDiscoversNothing(): void {
    $engine = $this->engine(function (PanelBuilder $p): void {
      $p->text('profile')->default('standard')->discover(['dotenv' => 'DRUPAL_PROFILE']);
    });

    $answers = $engine->run([], new Context($this->dir, [], FALSE));

    $this->assertSame('standard', $answers['profile']);
    $this->assertSame('default', $engine->provenance()['profile']);
  }

  public function testInputWinsOverDetected(): void {
    $engine = $this->engine(function (PanelBuilder $p): void {
      $p->text('profile')->default('standard')->discover(['dotenv' => 'DRUPAL_PROFILE']);
    });

    $answers = $engine->run(['profile' => 'demo'], new Context($this->dir, [], TRUE));

    $this->assertSame('demo', $answers['profile']);
    $this->assertSame('edited', $engine->provenance()['profile']);
  }

  public function testDetectedWinsOverDerived(): void {
    $engine = $this->engine(function (PanelBuilder $p): void {
      $p->text('src')->default('seed');
      $p->text('profile')->default('')->derive(['template' => '{{src}}'])->discover(['dotenv' => 'PROFILE']);
    });

    $answers = $engine->run([], new Context($this->dir, [], TRUE));

    $this->assertSame('from_env', $answers['profile']);
    $this->assertSame('detected', $engine->provenance()['profile']);
  }

  /**
   * Build an engine over a single panel with no handlers.
   *
   * @param \Closure $build
   *   The callback receiving the panel builder to declare its fields.
   */
  protected function engine(\Closure $build): Engine {
    return new Engine(Form::create('T')->panel('p', 'p', $build)->build(), new HandlerRegistry());
  }

}
