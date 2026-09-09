<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Prompts;

use DrevOps\VortexInstaller\Prompts\Handlers\Dotenv;
use DrevOps\VortexInstaller\Prompts\Handlers\HandlerInterface;
use DrevOps\VortexInstaller\Prompts\Handlers\Internal;
use DrevOps\VortexInstaller\Prompts\Handlers\Webroot;
use DrevOps\VortexInstaller\Prompts\PromptManager;
use DrevOps\VortexInstaller\Prompts\PromptSection;
use DrevOps\VortexInstaller\Tests\Unit\UnitTestCase;
use DrevOps\VortexInstaller\Utils\Config;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests that the prompt and processing orders are derived from the handlers.
 */
#[CoversClass(PromptManager::class)]
class PromptManagerTest extends UnitTestCase {

  public function testEveryHandlerIsProcessedOnce(): void {
    $manager = $this->manager();

    $this->assertEqualsCanonicalizing(array_keys($manager->getHandlers()), array_keys($manager->getProcessHandlers()), 'Processing order does not cover every registered handler exactly once.');
  }

  public function testPromptChainCoversSectionedHandlers(): void {
    $manager = $this->manager();

    $expected = array_keys(array_filter($manager->getHandlers(), fn(HandlerInterface $handler): bool => $handler::section() instanceof PromptSection));

    $this->assertEqualsCanonicalizing($expected, array_keys($manager->getPromptHandlers()), 'Prompt chain does not cover every handler with a section exactly once.');
  }

  public function testWeightsAreUnique(): void {
    $handlers = $this->manager()->getHandlers();

    $weights = array_map(fn(HandlerInterface $handler): int => $handler::weight(), $handlers);
    $this->assertCount(count($handlers), array_unique($weights), 'Prompt weights are not unique, so the prompt order is not deterministic.');

    $process_weights = array_map(fn(HandlerInterface $handler): int => $handler::processWeight(), $handlers);
    $this->assertCount(count($handlers), array_unique($process_weights), 'Processing weights are not unique, so the processing order is not deterministic.');
  }

  public function testSectionsAreContiguousAndInCaseOrder(): void {
    $positions = [];

    foreach ($this->manager()->getPromptHandlers() as $id => $handler) {
      $section = $handler::section();

      $this->assertInstanceOf(PromptSection::class, $section, sprintf('Handler "%s" is in the prompt chain without a section.', $id));

      $position = array_search($section, PromptSection::cases(), TRUE);

      if ($positions === [] || end($positions) !== $position) {
        $positions[] = $position;
      }
    }

    $sorted = $positions;
    sort($sorted);

    $this->assertSame($sorted, $positions, 'Sections are not introduced in the order of the enum cases.');
    $this->assertSame(array_unique($positions), $positions, 'A section is introduced more than once.');
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

  public function testHandlersWithoutPromptAreStillProcessed(): void {
    $manager = $this->manager();

    $prompted = array_keys($manager->getPromptHandlers());
    $processed = array_keys($manager->getProcessHandlers());

    $this->assertNotContains(Dotenv::id(), $prompted);
    $this->assertNotContains(Internal::id(), $prompted);
    $this->assertContains(Dotenv::id(), $processed);
    $this->assertContains(Internal::id(), $processed);
  }

  protected function manager(): PromptManager {
    return new PromptManager(Config::fromString('{}'));
  }

}
