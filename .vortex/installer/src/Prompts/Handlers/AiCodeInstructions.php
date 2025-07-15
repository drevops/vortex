<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

class AiCodeInstructions extends AbstractHandler {

  const NONE = 'none';

  const CLAUDE = 'claude';

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return '🤖 AI code assistant instructions';
  }

  /**
   * {@inheritdoc}
   */
  public function hint(array $responses): ?string {
    return 'Helps AI coding assistants to understand the project better.';
  }

  /**
   * {@inheritdoc}
   */
  public function options(array $responses): ?array {
    return [
      self::CLAUDE => 'Anthropic Claude',
      self::NONE => 'None',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function default(array $responses): null|string|bool|array {
    return self::NONE;
  }

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    if (!$this->isInstalled()) {
      return NULL;
    }

    if (is_readable($this->dstDir . '/CLAUDE.md')) {
      return self::CLAUDE;
    }

    return self::NONE;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $v = $this->getResponseAsString();
    $t = $this->tmpDir;

    if ($v !== self::CLAUDE) {
      @unlink($t . '/CLAUDE.md');
    }
  }

}
