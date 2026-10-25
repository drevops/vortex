<?php

declare(strict_types=1);

namespace Drupal\sw_base\Plugin\DeployStep;

use DrevOps\EnvironmentDetector\Environment;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\deploy_steps\Attribute\DeployStep;
use Drupal\deploy_steps\DeployStepBase;
use Drupal\deploy_steps\DeployStepInterface;
use Drupal\deploy_steps\EnvTrait;
use Drupal\deploy_steps\EnvironmentTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Process\Process;

/**
 * Builds the Storybook component library on development deploys.
 *
 * Generates the stories from the theme's Twig story templates and builds the
 * static Storybook application served at '/storybook'. Runs only in the
 * environments where 'settings.storybook.php' opens the story render route,
 * and in the POST phase, after the Storybook module is installed. Idempotent -
 * every run rebuilds the library in place.
 *
 * @codeCoverageIgnore
 */
#[DeployStep(
  id: 'sw_base_storybook',
  label: new TranslatableMarkup('Storybook component library build'),
  weight: 20,
  phase: DeployStepInterface::PHASE_POST,
)]
final class BuildStorybookDeployStep extends DeployStepBase {

  use EnvTrait;
  use EnvironmentTrait;

  /**
   * The Drupal application root.
   */
  protected string $appRoot;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $app_root = $container->getParameter('app.root');
    $instance->appRoot = is_string($app_root) ? $app_root : '';

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function skip(): ?string {
    if ($this->env('DRUPAL_STORYBOOK_SKIP', '0') === '1') {
      return 'Storybook build skipped via DRUPAL_STORYBOOK_SKIP';
    }

    if (!in_array($this->environment(), [Environment::LOCAL, Environment::CI, Environment::DEVELOPMENT], TRUE)) {
      return 'non-development environment';
    }

    return is_executable($this->storybookDir() . '/node_modules/.bin/storybook') ? NULL : 'theme dependencies are not installed';
  }

  /**
   * {@inheritdoc}
   */
  public function run(): void {
    // The tooling resolves its paths against the project root, while deploy
    // steps run with the Drupal root as the working directory.
    $project_root = dirname($this->appRoot);

    $process = new Process([$project_root . '/vendor/bin/vortex-storybook', 'build'], $project_root, NULL, NULL, NULL);
    $process->mustRun(static function (string $type, string $buffer): void {
      echo $buffer;
    });
  }

  /**
   * Resolves the absolute path to the directory with the Storybook scripts.
   *
   * @return string
   *   The absolute path to the directory.
   */
  protected function storybookDir(): string {
    $project_root = dirname($this->appRoot);
    $dir = $this->env('VORTEX_STORYBOOK_DIR') ?: './' . basename($this->appRoot) . '/themes/custom/' . $this->env('DRUPAL_THEME');

    if (!str_starts_with($dir, '/')) {
      if (str_starts_with($dir, './')) {
        $dir = substr($dir, 2);
      }

      $dir = $project_root . '/' . $dir;
    }

    return $dir;
  }

}
