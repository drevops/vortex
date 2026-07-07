<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Process;

use DrevOps\Tui\Config\Config;
use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Config\FieldType;
use DrevOps\Tui\Handler\Context;
use DrevOps\Tui\Handler\HandlerInterface;
use DrevOps\Tui\Handler\HandlerRegistry;

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
   * @param \DrevOps\Tui\Config\Config $config
   *   The configuration.
   * @param \DrevOps\Tui\Handler\HandlerRegistry $handlers
   *   The handler registry.
   * @param array<string,mixed> $answers
   *   The collected answers.
   * @param \DrevOps\Tui\Handler\Context $context
   *   The run context.
   * @param array<int,array{id:string,weight:int}> $processors
   *   The field-less processors that always run, each an id and a weight.
   */
  public function apply(Config $config, HandlerRegistry $handlers, array $answers, Context $context, array $processors): void {
    $fields = $config->fields();
    $count = count($fields);
    $items = [];

    foreach ($fields as $index => $field) {
      if (!array_key_exists($field->id, $answers)) {
        continue;
      }

      // Equal-weight fields process in reverse declaration order.
      $items[] = ['id' => $field->id, 'field' => $field, 'weight' => $field->weight, 'tie' => $count - $index];
    }

    foreach ($processors as $processor) {
      $items[] = ['id' => $processor['id'], 'field' => NULL, 'weight' => $processor['weight'], 'tie' => 0];
    }

    usort($items, static fn(array $a, array $b): int => [$a['weight'], $a['tie']] <=> [$b['weight'], $b['tie']]);

    $placeholder = new Field('', '', '', FieldType::Text, NULL);

    foreach ($items as $item) {
      $handler = $handlers->get($item['id']);
      if ($handler instanceof HandlerInterface) {
        $handler->process($item['field'] ?? $placeholder, $answers[$item['id']] ?? NULL, $context);
      }
    }
  }

}
