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
   * @param \DrevOps\Tui\Condition\ConditionInterface|null $when
   *   The conditional-visibility rule, or NULL when always visible.
   * @param \DrevOps\Tui\Derive\Derive|null $derive
   *   The derive rule, or NULL when not derived.
   *
   * @return \DrevOps\Tui\Builder\FieldBuilder
   *   The declared field.
   */
  public static function field(PanelBuilder $p, HandlerInterface $handler, ?ConditionInterface $when = NULL, ?Derive $derive = NULL): FieldBuilder {
    $id = $handler::id();
    $label = $handler->label();

    $field = match ($handler->type()) {
      PromptType::Select => $p->select($id, $label),
      PromptType::MultiSelect => $p->select($id, $label)->multiple(),
      PromptType::Confirm => $p->confirm($id, $label),
      PromptType::Suggest => $p->suggest($id, $label),
      PromptType::Number => $p->number($id, $label),
      PromptType::Textarea => $p->textarea($id, $label),
      PromptType::Password => $p->password($id, $label),
      PromptType::Search => $p->search($id, $label),
      PromptType::MultiSearch => $p->search($id, $label)->multiple(),
      PromptType::Pause => $p->pause($id, $label),
      default => $p->text($id, $label),
    };

    // The handler's three guidance texts map to the three field slots.
    $description = $handler::description([]);
    if (is_string($description) && $description !== '') {
      $field->description($description);
    }

    $hint = $handler->hint([]);
    if (is_string($hint) && $hint !== '') {
      $field->hint($hint);
    }

    $placeholder = $handler->placeholder([]);
    if (is_string($placeholder) && $placeholder !== '') {
      $field->placeholder($placeholder);
    }

    if ($handler->isRequired()) {
      $field->required(TRUE, sprintf('The %s is required.', mb_strtolower($label)));
    }

    // Options follow the answers: a handler narrows its own set as earlier
    // questions are answered, so they are resolved rather than fixed. Only the
    // kinds that show a list accept them.
    if (self::hasOptions($handler->type())) {
      $field->options(function (Context $c) use ($handler): array {
        $handler->setResponses($c->answers);
        $options = $handler->options($c->answers);

        if (!is_array($options)) {
          return [];
        }

        $options = array_is_list($options) ? array_combine($options, $options) : $options;

        // A handler narrows its options against earlier answers but resolves
        // its default and its pre-determined value independently, so either can
        // fall outside the narrowed list: a profile resolved to a recipe path
        // is never one of the profiles on offer. The answer a handler settles
        // on is authoritative, so it joins the list rather than being dropped
        // when the value is reconciled against it.
        foreach ([$handler->resolvedValue($c->answers), $handler->default($c->answers)] as $value) {
          if (is_string($value) && $value !== '' && !array_key_exists($value, $options)) {
            $options[$value] = $value;
          }
        }

        return $options;
      });
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

    // A closure default is opaque to the schema, so the answer-independent
    // default is published alongside it for agents driving the form.
    $schema_default = $handler->default([]);
    if ($schema_default !== NULL) {
      $field->schemaDefault($schema_default);
    }

    // Answers also arrive from the environment. The prefixed name is the
    // engine's own; the handler's name is honoured as an alias so variables
    // published before this CLI existed keep working.
    $field->envAliases([$handler::envName()]);

    $validate = $handler->validate();
    if ($validate !== NULL) {
      $field->validate(\Closure::fromCallable($validate));
    }

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
   * Whether a question kind shows a list of options.
   *
   * @param \DrevOps\VortexCli\Prompts\PromptType $type
   *   The question kind.
   *
   * @return bool
   *   TRUE when the kind accepts options.
   */
  protected static function hasOptions(PromptType $type): bool {
    return in_array($type, [
      PromptType::Select,
      PromptType::MultiSelect,
      PromptType::Suggest,
      PromptType::Search,
      PromptType::MultiSearch,
    ], TRUE);
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
