<?php

declare(strict_types=1);

namespace DrevOps\Tui\Engine;

use DrevOps\Tui\Answers\Answers;
use DrevOps\Tui\Condition\ConditionInterface;
use DrevOps\Tui\Config\Config;
use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Derive\Deriver;
use DrevOps\Tui\Discovery\DiscoverInterface;
use DrevOps\Tui\Handler\Context;
use DrevOps\Tui\Handler\HandlerRegistry;

/**
 * Orchestrates the question lifecycle generically over a configuration.
 *
 * For each configured field the engine resolves a value (supplied input, else
 * a value detected in update mode, else the field default), runs its
 * validator and transformer, then settles derived values, conditional
 * activation and fix-ups to a fixpoint. Precedence per field is
 * input > detected > derived > default. It never knows what any field means:
 * all behaviour comes from the form declaration, with the reusable static
 * validate()/transform() of a consumer class (resolved by field id) as the
 * fallback.
 *
 * @package DrevOps\Tui\Engine
 */
class Engine {

  /**
   * The deriver for computed field values.
   */
  protected Deriver $deriver;

  /**
   * The provenance of each active field from the most recent collect().
   *
   * @var array<string,string>
   */
  protected array $lastProvenance = [];

  /**
   * The active answers from the most recent collect().
   *
   * @var array<string,mixed>
   */
  protected array $lastAnswers = [];

  /**
   * Construct an engine.
   *
   * @param \DrevOps\Tui\Config\Config $config
   *   The configuration to run.
   * @param \DrevOps\Tui\Handler\HandlerRegistry $handlers
   *   The registry resolving a field id to its handler.
   */
  public function __construct(
    protected Config $config,
    protected HandlerRegistry $handlers,
  ) {
    $this->deriver = new Deriver();
  }

  /**
   * Collect the answers of the active fields.
   *
   * @param array<string,mixed> $inputs
   *   Pre-supplied values keyed by field id (from flags, env, prompts, ...).
   * @param \DrevOps\Tui\Handler\Context $context
   *   The run context (destination directory, update flag).
   *
   * @return array<string,mixed>
   *   The collected answers of the active fields, keyed by field id.
   */
  public function collect(array $inputs, Context $context): array {
    $fields = $this->config->fields();

    $values = [];
    $sources = [];
    foreach ($fields as $field) {
      $resolved = new Context($context->directory, $values, $context->update, $context->version, $context->destination);
      [$value, $source] = $this->resolveInitial($field, $inputs, $resolved);
      $sources[$field->id] = $source;
      $values[$field->id] = $value;
    }

    $derive_rules = [];
    $pinned = [];
    foreach ($fields as $field) {
      if ($field->derive !== NULL) {
        $derive_rules[$field->id] = $field->derive;
        $pinned[$field->id] = in_array($sources[$field->id] ?? '', ['input', 'detected'], TRUE);
      }
    }

    [$active, $values] = $this->stabilize($fields, $values, $derive_rules, $pinned);

    foreach ($fields as $field) {
      if (!($active[$field->id] ?? FALSE)) {
        continue;
      }

      // Only supplied inputs pass through the guards: defaults, discovered
      // and derived values are the configuration's own. Transform first so
      // validation sees the normalized value.
      if (($sources[$field->id] ?? '') !== 'input') {
        continue;
      }

      $values[$field->id] = $this->transformValue($field, $values[$field->id]);

      $error = $this->validateValue($field, $values[$field->id]);
      if ($error !== NULL) {
        throw new EngineException(sprintf('Invalid value for field "%s": %s', $field->id, $error));
      }
    }

    $this->lastProvenance = $this->provenanceFor($fields, $sources, $active);
    $this->lastAnswers = $this->activeAnswers($fields, $values, $active);

    return $this->lastAnswers;
  }

  /**
   * The provenance of each active field from the most recent collect().
   *
   * @return array<string,string>
   *   One of default / detected / edited / derived / override, keyed by id.
   */
  public function provenance(): array {
    return $this->lastProvenance;
  }

  /**
   * The collected answers of the most recent collect() as an Answers model.
   *
   * @return \DrevOps\Tui\Answers\Answers
   *   The self-describing answer set with values and provenance.
   */
  public function answers(): Answers {
    return Answers::forConfig($this->config, $this->lastAnswers, $this->lastProvenance);
  }

  /**
   * Resolve the initial value and its source for a field.
   *
   * @param \DrevOps\Tui\Config\Field $field
   *   The field.
   * @param array<string,mixed> $inputs
   *   Pre-supplied values keyed by field id.
   * @param \DrevOps\Tui\Handler\Context $context
   *   The run context.
   *
   * @return array{mixed,string}
   *   The resolved value and its source (input / detected / default).
   */
  protected function resolveInitial(Field $field, array $inputs, Context $context): array {
    if (array_key_exists($field->id, $inputs)) {
      return [$inputs[$field->id], 'input'];
    }

    if ($context->update) {
      $detected = $this->discoverValue($field, $context);
      if ($detected !== NULL) {
        return [$detected, 'detected'];
      }
    }

    if ($field->default instanceof \Closure) {
      return [($field->default)($context), 'default'];
    }

    return [$field->default, 'default'];
  }

  /**
   * Validate a value: the declared validator, else a reusable static one.
   *
   * @param \DrevOps\Tui\Config\Field $field
   *   The field.
   * @param mixed $value
   *   The value to validate.
   *
   * @return string|null
   *   An error message, or NULL when the value is valid.
   */
  protected function validateValue(Field $field, mixed $value): ?string {
    $validator = $field->validate ?? $this->handlers->validator($field->id);
    if (!$validator instanceof \Closure) {
      return NULL;
    }

    $error = $validator($value);

    return is_string($error) && $error !== '' ? $error : NULL;
  }

  /**
   * Transform a value: the declared transformer, else a reusable static one.
   *
   * @param \DrevOps\Tui\Config\Field $field
   *   The field.
   * @param mixed $value
   *   The accepted value.
   *
   * @return mixed
   *   The transformed value.
   */
  protected function transformValue(Field $field, mixed $value): mixed {
    $transformer = $field->transform ?? $this->handlers->transformer($field->id);

    return $transformer instanceof \Closure ? $transformer($value) : $value;
  }

  /**
   * Detect a value from the declared discovery rule.
   *
   * @param \DrevOps\Tui\Config\Field $field
   *   The field.
   * @param \DrevOps\Tui\Handler\Context $context
   *   The run context.
   *
   * @return mixed
   *   The detected value, or NULL.
   */
  protected function discoverValue(Field $field, Context $context): mixed {
    if ($field->discover instanceof DiscoverInterface) {
      return $field->discover->discover($context->directory);
    }

    if ($field->discover instanceof \Closure) {
      return ($field->discover)($context);
    }

    return NULL;
  }

  /**
   * Settle derived values, conditional activation and fix-ups to a fixpoint.
   *
   * @param \DrevOps\Tui\Config\Field[] $fields
   *   The fields, in order.
   * @param array<string,mixed> $values
   *   The resolved values keyed by field id.
   * @param array<string,array<array-key,mixed>> $derive_rules
   *   Derive rules keyed by field id.
   * @param array<string,bool> $pinned
   *   Field ids that must not be re-derived (input or detected).
   *
   * @return array{array<string,bool>,array<string,mixed>}
   *   The active map and the settled values.
   */
  protected function stabilize(array $fields, array $values, array $derive_rules, array $pinned): array {
    $active = [];
    foreach ($fields as $field) {
      $active[$field->id] = TRUE;
    }

    $limit = count($fields) + 2;
    for ($i = 0; $i <= $limit; $i++) {
      $derived = $this->deriver->derive($derive_rules, $values, $pinned);

      $next_active = [];
      $answers = $this->activeAnswers($fields, $derived, $active);
      foreach ($fields as $field) {
        $next_active[$field->id] = $field->when === NULL || $field->when->matches($answers);
      }

      $next_values = $this->applyFixups($derived, $this->activeAnswers($fields, $derived, $next_active));

      if ($next_active === $active && $next_values === $values) {
        return [$active, $values];
      }

      $active = $next_active;
      $values = $next_values;
    }

    // @codeCoverageIgnoreStart
    return [$active, $values];
    // @codeCoverageIgnoreEnd
  }

  /**
   * Compute the provenance of every active field.
   *
   * @param \DrevOps\Tui\Config\Field[] $fields
   *   The fields, in order.
   * @param array<string,string> $sources
   *   The initial source per field id (input / detected / default).
   * @param array<string,bool> $active
   *   The active map.
   *
   * @return array<string,string>
   *   The provenance of each active field.
   */
  protected function provenanceFor(array $fields, array $sources, array $active): array {
    $provenance = [];
    foreach ($fields as $field) {
      if (!($active[$field->id] ?? FALSE)) {
        continue;
      }

      $source = $sources[$field->id] ?? 'default';
      $provenance[$field->id] = match (TRUE) {
        $source === 'detected' => 'detected',
        $field->derive !== NULL && $source === 'input' => 'override',
        $field->derive !== NULL => 'derived',
        $source === 'input' => 'edited',
        default => 'default',
      };
    }

    return $provenance;
  }

  /**
   * Restrict values to the active fields, in field order.
   *
   * @param \DrevOps\Tui\Config\Field[] $fields
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
   * A fix-up sets its target field's value when its guard matches (or when it
   * has no guard): a literal `to`, or a copy of the `from` field's value.
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
    foreach ($this->config->fixups as $fixup) {
      if ($fixup->when instanceof ConditionInterface && !$fixup->when->matches($answers)) {
        continue;
      }

      $values[$fixup->set] = $fixup->from !== '' ? ($values[$fixup->from] ?? NULL) : $fixup->to;
    }

    return $values;
  }

}
