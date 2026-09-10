<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Prompts;

use DrevOps\VortexInstaller\Prompts\Handlers\Dotenv;
use DrevOps\VortexInstaller\Prompts\Handlers\HandlerInterface;
use DrevOps\VortexInstaller\Prompts\Handlers\Internal;
use DrevOps\VortexInstaller\Prompts\Handlers\Webroot;
use DrevOps\VortexInstaller\Prompts\PromptManager;
use DrevOps\VortexInstaller\Tests\Unit\UnitTestCase;
use DrevOps\VortexInstaller\Utils\Config;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests that the processing order is derived from the registered handlers.
 */
#[CoversClass(PromptManager::class)]
class PromptManagerTest extends UnitTestCase {

  public function testEveryHandlerIsProcessedOnce(): void {
    $manager = $this->manager();

    $this->assertEqualsCanonicalizing(array_keys($manager->getHandlers()), array_keys($manager->getProcessHandlers()), 'Processing order does not cover every registered handler exactly once.');
  }

  public function testProcessWeightsAreUnique(): void {
    $handlers = $this->manager()->getHandlers();

    $weights = array_map(fn(HandlerInterface $handler): int => $handler::processWeight(), $handlers);

    $this->assertCount(count($handlers), array_unique($weights), 'Processing weights are not unique, so the processing order is not deterministic.');
  }

  public function testProcessingOrderAnchors(): void {
    $ids = array_keys($this->manager()->getProcessHandlers());

    // Dotenv carries the destination's own values into the staged copy before
    // anything rewrites them, and Webroot renames the web root directory that
    // the handlers after it write into.
    $this->assertSame(Dotenv::id(), $ids[0], 'Dotenv is not processed first.');
    $this->assertSame(Webroot::id(), $ids[1], 'Webroot is not processed second.');
    $this->assertSame(Internal::id(), end($ids), 'Internal is not processed last.');
  }

  protected function manager(): PromptManager {
    return new PromptManager(Config::fromString('{}'));
  }

}
