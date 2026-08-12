<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\Converter;

class ProfileCustom extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'Custom profile machine name';
  }

  /**
   * {@inheritdoc}
   */
  public function placeholder(array $responses): ?string {
    return 'E.g. my_profile';
  }

  /**
   * {@inheritdoc}
   */
  public function isRequired(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function dependsOn(): ?array {
    return [Profile::id() => [Profile::CUSTOM]];
  }

  /**
   * {@inheritdoc}
   */
  public function shouldRun(array $responses): bool {
    return isset($responses[Profile::id()]) && $responses[Profile::id()] === Profile::CUSTOM;
  }

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    $profile_handler = new Profile($this->config);
    $profile_handler->setWebroot($this->webroot);
    $discovered = $profile_handler->discoverName();

    if (!empty($discovered) && !in_array($discovered, [Profile::STANDARD, Profile::MINIMAL, Profile::DEMO_UMAMI], TRUE)) {
      return $discovered;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(): ?callable {
    return fn(string $v): ?string => !empty($v) && Converter::machine($v) !== $v ?
      'Please enter a valid profile name: only lowercase letters, numbers, and underscores are allowed.' : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function transform(): ?callable {
    return trim(...);
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    // No processing needed: the Profile handler processes the final value.
  }

}
