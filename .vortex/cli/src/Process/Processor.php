<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Process;

use DrevOps\Tui\Answers\Answers;
use DrevOps\Tui\Handler\HandlerRegistry;
use DrevOps\VortexCli\Handler\HandlerInterface;
use DrevOps\VortexCli\Utils\Config;

/**
 * Applies collected answers by running field handlers in a fixed order.
 *
 * Answers process in ascending weight (each answer carries its question's
 * weight), ties broken by reverse form order - so specific replacements run
 * before generic ones. The field-less processors passed in (an ".env" carry
 * first, a final cleanup last) interleave by their own weight and always run.
 *
 * @package DrevOps\VortexCli\Process
 */
class Processor {

  /**
   * Apply the collected answers to the project directory.
   *
   * @param \DrevOps\Tui\Answers\Answers $answers
   *   The self-describing answer set.
   * @param \DrevOps\Tui\Handler\HandlerRegistry $handlers
   *   The handler registry resolving a field id to its handler class.
   * @param \DrevOps\VortexCli\Utils\Config $config
   *   The installer configuration the handlers operate on.
   * @param array<int,array{id:string,weight:int}> $processors
   *   The field-less processors that always run, each an id and a weight.
   */
  public function apply(Answers $answers, HandlerRegistry $handlers, Config $config, array $processors): void {
    $count = count($answers->items);
    $items = [];

    $position = 0;
    foreach ($answers->items as $answer) {
      // Equal-weight answers process in reverse form order.
      $items[] = ['id' => $answer->id, 'weight' => $answer->weight, 'tie' => $count - $position];
      $position++;
    }

    foreach ($processors as $processor) {
      $items[] = ['id' => $processor['id'], 'weight' => $processor['weight'], 'tie' => 0];
    }

    usort($items, static fn(array $a, array $b): int => [$a['weight'], $a['tie']] <=> [$b['weight'], $b['tie']]);

    foreach ($items as $item) {
      $class = $handlers->resolve($item['id']);
      if ($class !== NULL && is_a($class, HandlerInterface::class, TRUE)) {
        $handler = new $class($config);
        $handler->setResponses($answers->values);
        $handler->process();
      }
    }
  }

}
