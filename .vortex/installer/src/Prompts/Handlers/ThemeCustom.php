<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\Converter;

class ThemeCustom extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'Custom theme machine name';
  }

  /**
   * {@inheritdoc}
   */
  public function hint(array $responses): ?string {
    return 'We will use this name as a custom theme name';
  }

  /**
   * {@inheritdoc}
   */
  public function placeholder(array $responses): ?string {
    return 'E.g. my_theme';
  }

  /**
   * {@inheritdoc}
   */
  public function isRequired(): bool {
    return TRUE;
  }

  public function default(array $responses): null|string|bool|array {
    if (isset($responses[MachineName::id()]) && !empty($responses[MachineName::id()])) {
      return Converter::machine($responses[MachineName::id()]);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function shouldRun(array $responses): bool {
    return isset($responses[Theme::id()]) && $responses[Theme::id()] === Theme::CUSTOM;
  }

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    // Get the discovered theme from the Theme handler.
    $theme_handler = new Theme($this->config);
    $theme_handler->setWebroot($this->webroot);
    $discovered = $theme_handler->discoverName();

    // Only return discovered value if it's a custom theme.
    if (!empty($discovered) && !in_array($discovered, [Theme::OLIVERO, Theme::CLARO, Theme::STARK])) {
      return $discovered;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(): ?callable {
    return fn(string $v): ?string => !empty($v) && Converter::machine($v) !== $v ?
      'Please enter a valid theme machine name: only lowercase letters, numbers, and underscores are allowed.' : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function transform(): ?callable {
    return fn(string $v): string => trim($v);
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    // This handler doesn't need processing - the Theme handler will handle
    // the final result.
  }

}
