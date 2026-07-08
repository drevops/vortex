<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Config\FieldType;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Utils\File;

/**
 * Handler for the "ai_code_instructions" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class AiCodeInstructions extends AbstractFieldHandler {

  /**
   * {@inheritdoc}
   */
  public function process(Field $field, mixed $value, Context $context): void {
    if ($value !== TRUE) {
      File::remove($context->directory . '/AGENTS.md');
      File::remove($context->directory . '/CLAUDE.md');
      File::remove($context->directory . '/.claude');
      File::removeTokenAsync('AI_CODE_INSTRUCTIONS');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function id(): string {
    return 'ai_code_instructions';
  }

  /**
   * {@inheritdoc}
   */
  public static function label(): string {
    return 'Provide AI agent instructions?';
  }

  /**
   * {@inheritdoc}
   */
  public static function type(): FieldType {
    return FieldType::Confirm;
  }

  /**
   * {@inheritdoc}
   */
  public static function description(): string {
    return 'Provides AI coding agents with better context about the project.';
  }

  /**
   * {@inheritdoc}
   */
  public static function default(): mixed {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function weight(): int {
    return 20;
  }

}
