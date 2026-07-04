<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Engine;

use DrevOps\Customizer\Config\Config;
use DrevOps\Customizer\Config\Field;
use DrevOps\Customizer\Handler\Context;
use DrevOps\Customizer\Handler\HandlerInterface;
use DrevOps\Customizer\Handler\HandlerRegistry;

/**
 * Orchestrates the question lifecycle generically over a configuration.
 *
 * For each configured field the engine resolves a value (supplied input, else
 * a value discovered in update mode, else the field default), runs the
 * resolved handler's validate() and transform(), then applies every collected
 * answer via process(). It never knows what any field means: all behaviour
 * comes from the handlers, which are optional.
 *
 * @package DrevOps\Customizer\Engine
 */
class Engine {

  /**
   * Construct an engine.
   *
   * @param \DrevOps\Customizer\Config\Config $config
   *   The configuration to run.
   * @param \DrevOps\Customizer\Handler\HandlerRegistry $handlers
   *   The registry resolving a field id to its handler.
   */
  public function __construct(
    protected Config $config,
    protected HandlerRegistry $handlers,
  ) {
  }

  /**
   * Run the lifecycle for all fields and return the collected answers.
   *
   * @param array<string,mixed> $inputs
   *   Pre-supplied values keyed by field id (from flags, env, prompts, ...).
   * @param \DrevOps\Customizer\Handler\Context $context
   *   The run context (destination directory, update flag).
   *
   * @return array<string,mixed>
   *   The collected answers keyed by field id.
   */
  public function run(array $inputs, Context $context): array {
    $fields = [];
    $this->collectFields($this->config->panels, $fields);

    $answers = [];
    foreach ($fields as $field) {
      $handler = $this->handlers->get($field->id);
      $value = $this->resolveValue($field, $handler, $inputs, $context);

      if ($handler instanceof HandlerInterface) {
        $error = $handler->validate($field, $value);
        if ($error !== NULL) {
          throw new EngineException(sprintf('Invalid value for field "%s": %s', $field->id, $error));
        }

        $value = $handler->transform($field, $value);
      }

      $answers[$field->id] = $value;
    }

    $applied = new Context($context->directory, $answers, $context->update);
    foreach ($fields as $field) {
      $this->handlers->get($field->id)?->process($field, $answers[$field->id], $applied);
    }

    return $answers;
  }

  /**
   * Resolve the incoming value for a field before validate/transform.
   *
   * @param \DrevOps\Customizer\Config\Field $field
   *   The field.
   * @param \DrevOps\Customizer\Handler\HandlerInterface|null $handler
   *   The resolved handler, if any.
   * @param array<string,mixed> $inputs
   *   Pre-supplied values keyed by field id.
   * @param \DrevOps\Customizer\Handler\Context $context
   *   The run context.
   *
   * @return mixed
   *   The resolved value.
   */
  protected function resolveValue(Field $field, ?HandlerInterface $handler, array $inputs, Context $context): mixed {
    if (array_key_exists($field->id, $inputs)) {
      return $inputs[$field->id];
    }

    if ($context->update && $handler instanceof HandlerInterface) {
      $discovered = $handler->discover($field, $context);
      if ($discovered !== NULL) {
        return $discovered;
      }
    }

    return $field->default;
  }

  /**
   * Flatten fields across the panel tree, in declaration order.
   *
   * @param \DrevOps\Customizer\Config\Panel[] $panels
   *   The panels to walk.
   * @param \DrevOps\Customizer\Config\Field[] $fields
   *   Accumulator, populated in place.
   */
  protected function collectFields(array $panels, array &$fields): void {
    foreach ($panels as $panel) {
      foreach ($panel->fields as $field) {
        $fields[] = $field;
      }

      $this->collectFields($panel->panels, $fields);
    }
  }

}
