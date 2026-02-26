<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\File;

class AiCodeInstructions extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'Provide AI agent instructions?';
  }

  /**
   * {@inheritdoc}
   */
  public function hint(array $responses): ?string {
    return 'Provides AI coding agents with better context about the project.';
  }

  /**
   * {@inheritdoc}
   */
  public function default(array $responses): null|string|bool|array {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    if (!$this->isInstalled()) {
      return NULL;
    }

    return File::exists($this->dstDir . '/AGENTS.md') || File::exists($this->dstDir . '/CLAUDE.md');
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $v = $this->getResponseAsBool();
    $t = $this->tmpDir;

    if (!$v) {
      File::remove($t . '/AGENTS.md');
      File::remove($t . '/CLAUDE.md');
    }
  }

}
