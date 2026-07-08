<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Builder\FieldBuilder;
use DrevOps\Tui\Builder\PanelBuilder;
use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Utils\File;

/**
 * Handler for the "ai_code_instructions" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class AiCodeInstructions extends AbstractHandler implements FieldInterface {

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
  public static function field(PanelBuilder $p): FieldBuilder {
    return $p->confirm('ai_code_instructions', 'Provide AI agent instructions?')->description('Provides AI coding agents with better context about the project.')->default(TRUE)->weight(20);
  }

}
