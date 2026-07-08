<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Form;

use DrevOps\Tui\Builder\FieldBuilder;
use DrevOps\Tui\Builder\PanelBuilder;
use DrevOps\Tui\Condition\ConditionInterface;
use DrevOps\Tui\Derive\Derive;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Handler\HandlerInterface;
use DrevOps\VortexCli\Prompts\PromptType;

/**
 * The adapter between the handlers and the TUI form.
 *
 * Handlers declare their question through the handler contract and know
 * nothing about the form; this is the single place converting that contract
 * into TUI form elements. The form itself keeps what its runner owns: panel
 * structure, question order, conditional gating, derivation and weights.
 *
 * @package DrevOps\VortexCli\Form
 */
class TuiAdapter {

  /**
   * Declare a handler's question on a panel.
   *
   * @param \DrevOps\Tui\Builder\PanelBuilder $p
   *   The panel builder.
   * @param \DrevOps\VortexCli\Handler\HandlerInterface $handler
   *   The handler declaring the question.
   * @param int $weight
   *   The processing weight; lower runs earlier.
   * @param \DrevOps\Tui\Condition\ConditionInterface|null $when
   *   The conditional-visibility rule, or NULL when always visible.
   * @param \DrevOps\Tui\Derive\Derive|null $derive
   *   The derive rule, or NULL when not derived.
   *
   * @return \DrevOps\Tui\Builder\FieldBuilder
   *   The declared field.
   */
  public static function field(PanelBuilder $p, HandlerInterface $handler, int $weight = 0, ?ConditionInterface $when = NULL, ?Derive $derive = NULL): FieldBuilder {
    $id = $handler::id();
    $label = $handler->label();

    $field = match ($handler->type()) {
      PromptType::Select => $p->select($id, $label),
      PromptType::MultiSelect => $p->multiselect($id, $label),
      PromptType::Confirm => $p->confirm($id, $label),
      PromptType::Suggest => $p->suggest($id, $label),
      default => $p->text($id, $label),
    };

    $field->weight($weight);

    $description = $handler::description([]) ?? $handler->hint([]);
    if (is_string($description) && $description !== '') {
      $field->description($description);
    }

    if ($handler->isRequired()) {
      $field->required();
    }

    $options = $handler->options([]);
    if (is_array($options)) {
      $field->options(array_is_list($options) ? array_combine($options, $options) : $options);
    }

    // Defaults resolve against the responses collected so far, exactly like
    // the handler contract defines them: a pre-determined resolved value wins,
    // then the handler default, then the kind's own default.
    $type = $handler->type();
    $field->default(function (Context $c) use ($handler, $type): mixed {
      $handler->setResponses($c->answers);

      $resolved = $handler->resolvedValue($c->answers);
      if ($resolved !== NULL && $resolved !== '') {
        return $resolved;
      }

      return $handler->default($c->answers) ?? self::typeDefault($type);
    });

    $validate = $handler->validate();
    $field->validate(function (mixed $value) use ($handler, $validate, $label): ?string {
      if ($handler->isRequired() && in_array($value, ['', [], NULL], TRUE)) {
        return sprintf('The %s is required.', mb_strtolower($label));
      }

      return $validate === NULL ? NULL : $validate($value);
    });

    $transform = $handler->transform();
    if ($transform !== NULL) {
      $field->transform(\Closure::fromCallable($transform));
    }

    // Project-content discovery runs in update mode only, driven by the
    // engine; the handler inspects the destination it was constructed with.
    $field->discover(function (Context $c) use ($handler): mixed {
      $handler->setResponses($c->answers);

      return $handler->discover();
    });

    if ($when instanceof ConditionInterface) {
      $field->when($when);
    }

    if ($derive instanceof Derive) {
      $field->derive($derive);
    }

    return $field;
  }

  /**
   * The neutral default for a question kind when the handler declares none.
   *
   * @param \DrevOps\VortexCli\Prompts\PromptType $type
   *   The question kind.
   *
   * @return mixed
   *   The kind default.
   */
  protected static function typeDefault(PromptType $type): mixed {
    return match ($type) {
      PromptType::MultiSelect => [],
      PromptType::Confirm => FALSE,
      default => '',
    };
  }

}
