<?php

declare(strict_types=1);

namespace Drupal\ys_deploy;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Site\Settings;

/**
 * Base class for deploy step plugins.
 *
 * Provides weight/label accessors from the plugin definition, a default "always
 * run" gate, and environment helpers most gates need. Subclasses implement
 * ::run() and, when conditional, override ::gate().
 */
abstract class DeployStepBase extends PluginBase implements DeployStepInterface {

  /**
   * {@inheritdoc}
   */
  public function gate(): ?string {
    // Run by default. Override to skip under specific conditions.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return (int) ($this->pluginDefinition['weight'] ?? 0);
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return (string) ($this->pluginDefinition['label'] ?? $this->getPluginId());
  }

  /**
   * Returns the current environment machine name.
   *
   * @return string
   *   One of the ENVIRONMENT_* values (local, ci, dev, stage, prod) or an empty
   *   string when not set.
   *
   * @SuppressWarnings("PHPMD.StaticAccess")
   */
  protected function environment(): string {
    return (string) Settings::get('environment', '');
  }

  /**
   * Whether the current environment is production.
   *
   * @return bool
   *   TRUE when running in the production environment.
   */
  protected function isProduction(): bool {
    // 'prod' is the value of the ENVIRONMENT_PROD constant defined in
    // settings.php.
    return $this->environment() === 'prod';
  }

}
