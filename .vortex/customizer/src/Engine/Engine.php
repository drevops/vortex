<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Engine;

use DrevOps\Customizer\Condition\ConditionEvaluator;
use DrevOps\Customizer\Config\Config;
use DrevOps\Customizer\Config\Field;
use DrevOps\Customizer\Derive\Deriver;
use DrevOps\Customizer\Handler\Context;
use DrevOps\Customizer\Handler\HandlerInterface;
use DrevOps\Customizer\Handler\HandlerRegistry;

/**
 * Orchestrates the question lifecycle generically over a configuration.
 *
 * For each configured field the engine resolves a value (supplied input, else
 * a value discovered in update mode, else the field default), runs the
 * resolved handler's validate() and transform(), then settles derived values,
 * conditional activation and fix-ups to a fixpoint before applying every
 * active answer via process(). It never knows what any field means: all
 * behaviour comes from the handlers, which are optional.
 *
 * @package DrevOps\Customizer\Engine
 */
class Engine {

  /**
   * The condition evaluator for `when` gating and fix-up guards.
   */
  protected ConditionEvaluator $evaluator;

  /**
   * The deriver for computed field values.
   */
  protected Deriver $deriver;

  /**
   * The provenance of each active field from the most recent run().
   *
   * @var array<string,string>
   */
  protected array $lastProvenance = [];

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
    $this->evaluator = new ConditionEvaluator();
    $this->deriver = new Deriver();
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
   *   The collected answers of the active fields, keyed by field id.
   */
  public function run(array $inputs, Context $context): array {
    $fields = [];
    $this->collectFields($this->config->panels, $fields);

    $values = [];
    $sources = [];
    foreach ($fields as $field) {
      $handler = $this->handlers->get($field->id);
      [$value, $source] = $this->resolveInitial($field, $handler, $inputs, $context);
      $sources[$field->id] = $source;

      if ($handler instanceof HandlerInterface) {
        $error = $handler->validate($field, $value);
        if ($error !== NULL) {
          throw new EngineException(sprintf('Invalid value for field "%s": %s', $field->id, $error));
        }

        $value = $handler->transform($field, $value);
      }

      $values[$field->id] = $value;
    }

    $derive_rules = [];
    $overridden = [];
    foreach ($fields as $field) {
      if ($field->derive !== NULL) {
        $derive_rules[$field->id] = $field->derive;
        $overridden[$field->id] = ($sources[$field->id] ?? '') === 'input';
      }
    }

    [$active, $values] = $this->stabilize($fields, $values, $derive_rules, $overridden);
    $this->lastProvenance = $this->provenanceFor($fields, $sources, $overridden, $active);

    $answers = $this->activeAnswers($fields, $values, $active);
    $applied = new Context($context->directory, $answers, $context->update);
    foreach ($fields as $field) {
      if ($active[$field->id] ?? FALSE) {
        $this->handlers->get($field->id)?->process($field, $answers[$field->id], $applied);
      }
    }

    return $answers;
  }

  /**
   * The provenance of each active field from the most recent run().
   *
   * @return array<string,string>
   *   One of default / detected / edited / derived / override, keyed by id.
   */
  public function provenance(): array {
    return $this->lastProvenance;
  }

  /**
   * Resolve the initial value and its source for a field.
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
   * @return array{mixed,string}
   *   The resolved value and its source (input / detected / default).
   */
  protected function resolveInitial(Field $field, ?HandlerInterface $handler, array $inputs, Context $context): array {
    if (array_key_exists($field->id, $inputs)) {
      return [$inputs[$field->id], 'input'];
    }

    if ($context->update && $handler instanceof HandlerInterface) {
      $discovered = $handler->discover($field, $context);
      if ($discovered !== NULL) {
        return [$discovered, 'detected'];
      }
    }

    return [$field->default, 'default'];
  }

  /**
   * Settle derived values, conditional activation and fix-ups to a fixpoint.
   *
   * @param \DrevOps\Customizer\Config\Field[] $fields
   *   The fields, in order.
   * @param array<string,mixed> $values
   *   The resolved values keyed by field id.
   * @param array<string,array<array-key,mixed>> $derive_rules
   *   Derive rules keyed by field id.
   * @param array<string,bool> $overridden
   *   Field ids the user has pinned (not re-derived).
   *
   * @return array{array<string,bool>,array<string,mixed>}
   *   The active map and the settled values.
   */
  protected function stabilize(array $fields, array $values, array $derive_rules, array $overridden): array {
    $active = [];
    foreach ($fields as $field) {
      $active[$field->id] = TRUE;
    }

    $limit = count($fields) + 2;
    for ($i = 0; $i <= $limit; $i++) {
      $derived = $this->deriver->derive($derive_rules, $values, $overridden);

      $next_active = [];
      $answers = $this->activeAnswers($fields, $derived, $active);
      foreach ($fields as $field) {
        $next_active[$field->id] = $field->when === NULL || $this->evaluator->matches($field->when, $answers);
      }

      $next_values = $this->applyFixups($derived, $this->activeAnswers($fields, $derived, $next_active));

      if ($next_active === $active && $next_values === $values) {
        return [$active, $values];
      }

      $active = $next_active;
      $values = $next_values;
    }

    return [$active, $values];
  }

  /**
   * Compute the provenance of every field.
   *
   * @param \DrevOps\Customizer\Config\Field[] $fields
   *   The fields, in order.
   * @param array<string,string> $sources
   *   The initial source per field id (input / detected / default).
   * @param array<string,bool> $overridden
   *   Field ids the user has pinned.
   * @param array<string,bool> $active
   *   The active map.
   *
   * @return array<string,string>
   *   The provenance of each active field.
   */
  protected function provenanceFor(array $fields, array $sources, array $overridden, array $active): array {
    $provenance = [];
    foreach ($fields as $field) {
      if (!($active[$field->id] ?? FALSE)) {
        continue;
      }

      $source = $sources[$field->id] ?? 'default';
      $provenance[$field->id] = match (TRUE) {
        $field->derive !== NULL => ($overridden[$field->id] ?? FALSE) ? 'override' : 'derived',
        $source === 'input' => 'edited',
        $source === 'detected' => 'detected',
        default => 'default',
      };
    }

    return $provenance;
  }

  /**
   * Restrict values to the active fields, in field order.
   *
   * @param \DrevOps\Customizer\Config\Field[] $fields
   *   The fields, in order.
   * @param array<string,mixed> $values
   *   The resolved values.
   * @param array<string,bool> $active
   *   The active map.
   *
   * @return array<string,mixed>
   *   The answers of the active fields.
   */
  protected function activeAnswers(array $fields, array $values, array $active): array {
    $answers = [];
    foreach ($fields as $field) {
      if ($active[$field->id] ?? FALSE) {
        $answers[$field->id] = $values[$field->id] ?? NULL;
      }
    }

    return $answers;
  }

  /**
   * Apply the config fix-up rules to the values.
   *
   * A rule sets a target field's value when its `when` guard matches (or when
   * it has no guard). The new value is a literal `to`, or a copy of another
   * field's value when `to` is `{field: other_id}`.
   *
   * @param array<string,mixed> $values
   *   The current values.
   * @param array<string,mixed> $answers
   *   The active answers the guards evaluate against.
   *
   * @return array<string,mixed>
   *   The values after fix-ups.
   */
  protected function applyFixups(array $values, array $answers): array {
    foreach ($this->config->fixups as $rule) {
      $when = isset($rule['when']) && is_array($rule['when']) ? $rule['when'] : [];
      if ($when !== [] && !$this->evaluator->matches($when, $answers)) {
        continue;
      }

      $target = isset($rule['set']) && is_scalar($rule['set']) ? (string) $rule['set'] : '';
      if ($target === '') {
        continue;
      }

      $values[$target] = $this->fixupValue($rule['to'] ?? NULL, $values);
    }

    return $values;
  }

  /**
   * Resolve a fix-up target value: a literal, or a copy of another field.
   *
   * @param mixed $to
   *   The raw `to` operand.
   * @param array<string,mixed> $values
   *   The current values (copy source).
   *
   * @return mixed
   *   The resolved value.
   */
  protected function fixupValue(mixed $to, array $values): mixed {
    if (is_array($to) && isset($to['field']) && is_scalar($to['field'])) {
      return $values[(string) $to['field']] ?? NULL;
    }

    return $to;
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
