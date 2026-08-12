<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Form;

use DrevOps\PhpTui\Builder\FieldBuilder;
use DrevOps\PhpTui\Builder\PanelBuilder;
use DrevOps\PhpTui\Condition\ConditionInterface;
use DrevOps\PhpTui\Derive\Derive;
use DrevOps\PhpTui\Handler\Context;
use DrevOps\VortexCli\Prompts\Handlers\HandlerInterface;
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
   * Questions whose options and default are resolved without the answers.
   *
   * The linear flow this replaces handed the answers collected so far to some
   * questions and an empty set to the rest, so a handler in this list has
   * never seen them - and branches that read them have never run. Keeping the
   * distinction keeps every answer, and so every processed file, identical;
   * moving a question out of this list is a behaviour change to argue on its
   * own merits.
   */
  protected const array RESOLVED_WITHOUT_ANSWERS = [
    'ai_code_instructions',
    'assign_author_pr',
    'code_coverage_provider',
    'code_provider',
    'dependency_updates_provider',
    'gitleaks',
    'hosting_provider',
    'label_merge_conflicts_pr',
    'migration',
    'name',
    'notification_channels',
    'preserve_docs_project',
    'profile',
    'profile_custom',
    'provision_type',
    'services',
    'theme',
    'timezone',
    'tools',
    'version_scheme',
    'visual_regression',
  ];

  /**
   * Questions that settle on a pre-determined value instead of being asked.
   *
   * Only these were resolved ahead of the prompt in the flow this replaces, so
   * only these consult the handler for one; for every other question the value
   * comes from the answer, the default, or the environment.
   */
  protected const array PRE_RESOLVED = ['profile', 'theme', 'webroot'];

  /**
   * Declare a handler's question on a panel.
   *
   * @param \DrevOps\PhpTui\Builder\PanelBuilder $p
   *   The panel builder.
   * @param \DrevOps\VortexCli\Prompts\Handlers\HandlerInterface $handler
   *   The handler declaring the question.
   * @param \DrevOps\PhpTui\Condition\ConditionInterface|null $when
   *   The conditional-visibility rule, or NULL when always visible.
   * @param \DrevOps\PhpTui\Derive\Derive|null $derive
   *   The derive rule, or NULL when not derived.
   *
   * @return \DrevOps\PhpTui\Builder\FieldBuilder
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
      $field->help($hint);
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
    $contextual = !in_array($id, self::RESOLVED_WITHOUT_ANSWERS, TRUE);
    $pre_resolved = in_array($id, self::PRE_RESOLVED, TRUE);

    if (self::hasOptions($handler->type())) {
      $field->options(function (Context $c) use ($handler, $contextual, $pre_resolved): array {
        $answers = $contextual ? $c->answers : [];
        $handler->setResponses($c->answers);
        $options = $handler->options($answers);

        if (!is_array($options)) {
          return [];
        }

        $options = self::normalize($options);

        // A handler narrows its options against earlier answers but resolves
        // its default and its pre-determined value independently, so either can
        // fall outside the narrowed list: a profile resolved to a recipe path
        // is never one of the profiles on offer. The answer a handler settles
        // on is authoritative, so it joins the list rather than being dropped
        // when the value is reconciled against it.
        $candidates = [$c->update ? $handler->discover() : NULL];

        if ($pre_resolved) {
          $candidates[] = $handler->resolvedValue($c->answers);
        }

        // The default joins the list even when the handler's own narrowing
        // excludes it: the flow this replaces handed the narrowed options and
        // the unnarrowed default to the prompt without reconciling them, and
        // the answer that reached processing was the default. Dropping it here
        // would change what a project is built with.
        $candidates[] = $handler->default($answers);

        foreach ($candidates as $value) {
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
    $field->default(function (Context $c) use ($handler, $type, $contextual, $pre_resolved): mixed {
      $handler->setResponses($c->answers);

      if ($pre_resolved) {
        $resolved = $handler->resolvedValue($c->answers);

        if ($resolved !== NULL && $resolved !== '') {
          return $resolved;
        }
      }

      // An existing project answers for itself. The engine detects this too,
      // but only accepts what the field's options already allow - and options
      // that follow the answers have not resolved by then - so the detected
      // value is taken here, where it outranks the default either way.
      if ($c->update) {
        $detected = $handler->discover();

        // What a project holds is not necessarily something the question
        // offers, and a value it does not offer is not an answer.
        if ($detected !== NULL && $detected !== '' && self::offers($handler, $detected, $contextual ? $c->answers : [])) {
          return $detected;
        }
      }

      $default = $handler->default($contextual ? $c->answers : []);

      // An unanswerable question is left unset rather than defaulted to an
      // empty value, which its own validator would then reject.
      return $default ?? ($handler->isRequired() ? self::typeDefault($type) : NULL);
    });

    // A closure default is opaque to the schema, so the answer-independent
    // default is published alongside it for agents driving the form.
    $schema_default = $handler->default([]);
    if ($schema_default !== NULL) {
      $field->schemaDefault($schema_default);
    }

    // Answers also arrive from the environment. The form's prefix produces the
    // documented name; the handler's own name is registered alongside it so the
    // contract stays the source of truth for what that name is.
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
   * Whether a question offers the given value.
   *
   * @param \DrevOps\VortexCli\Prompts\Handlers\HandlerInterface $handler
   *   The handler declaring the question.
   * @param mixed $value
   *   The value to look for.
   * @param array<string,mixed> $responses
   *   The answers the options are resolved against.
   *
   * @return bool
   *   TRUE when the question offers the value, or offers no list at all.
   */
  protected static function offers(HandlerInterface $handler, mixed $value, array $responses): bool {
    if (!self::hasOptions($handler->type())) {
      return TRUE;
    }

    $options = self::normalize($handler->options($responses));

    if ($options === []) {
      return TRUE;
    }

    foreach (is_array($value) ? $value : [$value] as $item) {
      if (!array_key_exists((string) $item, $options)) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * A handler's options as a value-to-label map.
   *
   * @param mixed $options
   *   The handler's options, a list or a map, or NULL when it declares none.
   *
   * @return array<string,string>
   *   The options keyed by value.
   */
  protected static function normalize(mixed $options): array {
    if (!is_array($options)) {
      return [];
    }

    return array_is_list($options) ? array_combine($options, $options) : $options;
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
