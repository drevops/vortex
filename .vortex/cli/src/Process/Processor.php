<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Process;

use DrevOps\Tui\Config\Config as TuiConfig;
use DrevOps\Tui\Handler\HandlerRegistry;
use DrevOps\VortexCli\Handler\HandlerInterface;
use DrevOps\VortexCli\Utils\Config;

/**
 * Applies collected answers by running field handlers in a fixed order.
 *
 * Active fields (present in the answers) process in ascending weight, ties
 * broken by reverse declaration order - so specific replacements run before
 * generic ones. The field-less processors passed in (an ".env" carry first, a
 * final cleanup last) interleave by their own weight and always run.
 *
 * @package DrevOps\VortexCli\Process
 */
class Processor {

  /**
   * Apply the collected answers to the project directory.
   *
   * @param \DrevOps\Tui\Config\Config $form
   *   The form configuration providing fields and their weights.
   * @param \DrevOps\Tui\Handler\HandlerRegistry $handlers
   *   The handler registry resolving a field id to its handler class.
   * @param array<string,mixed> $answers
   *   The collected answers.
   * @param \DrevOps\VortexCli\Utils\Config $config
   *   The installer configuration the handlers operate on.
   * @param array<int,array{id:string,weight:int}> $processors
   *   The field-less processors that always run, each an id and a weight.
   */
  public function apply(TuiConfig $form, HandlerRegistry $handlers, array $answers, Config $config, array $processors): void {
    $fields = $form->fields();
    $count = count($fields);
    $items = [];

    foreach ($fields as $index => $field) {
      if (!array_key_exists($field->id, $answers)) {
        continue;
      }

      // Equal-weight fields process in reverse declaration order.
      $items[] = ['id' => $field->id, 'weight' => $field->weight, 'tie' => $count - $index];
    }

    foreach ($processors as $processor) {
      $items[] = ['id' => $processor['id'], 'weight' => $processor['weight'], 'tie' => 0];
    }

    usort($items, static fn(array $a, array $b): int => [$a['weight'], $a['tie']] <=> [$b['weight'], $b['tie']]);

    foreach ($items as $item) {
      $class = $handlers->resolve($item['id']);
      if ($class !== NULL && is_a($class, HandlerInterface::class, TRUE)) {
        $handler = new $class($config);
        $handler->setResponses($answers);
        $handler->process();
      }
    }
  }

}
