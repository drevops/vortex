<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Process;

use DrevOps\Customizer\Config\Config;
use DrevOps\Customizer\Config\Field;
use DrevOps\Customizer\Config\FieldType;
use DrevOps\Customizer\Handler\Context;
use DrevOps\Customizer\Handler\HandlerInterface;
use DrevOps\Customizer\Handler\HandlerRegistry;

/**
 * Applies collected answers by running field handlers in a config-driven order.
 *
 * The order lives entirely in the config: active fields process in ascending
 * weight, ties broken by reverse declaration order - so specific replacements
 * run before generic ones with no weights at all. Field-less processors
 * declared on the config (an ".env" carry first, a final cleanup last)
 * interleave by their own weight. Only active fields (present in the answers)
 * process; declared processors always run.
 *
 * @package DrevOps\Customizer\Process
 */
class Processor {

  /**
   * Apply the collected answers to the project directory.
   *
   * @param \DrevOps\Customizer\Config\Config $config
   *   The configuration.
   * @param \DrevOps\Customizer\Handler\HandlerRegistry $handlers
   *   The handler registry.
   * @param array<string,mixed> $answers
   *   The collected answers.
   * @param \DrevOps\Customizer\Handler\Context $context
   *   The run context.
   */
  public function apply(Config $config, HandlerRegistry $handlers, array $answers, Context $context): void {
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

    foreach ($config->processors as $processor) {
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
