<?php

declare(strict_types=1);

namespace Drupal\my_module\Plugin\DeployStep;

use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\deploy_steps\Attribute\DeployStep;
use Drupal\deploy_steps\DeployStepBase;
use Drupal\deploy_steps\DeployStepInterface;
use Drupal\deploy_steps\EnvTrait;
use Drupal\deploy_steps\EnvironmentTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Example deploy step: prepares a non-production environment on every deploy.
 *
 * The deploy_steps equivalent of the legacy `provision-10-example.sh` custom
 * provision script. Copy it into any enabled module's `src/Plugin/DeployStep/`
 * namespace and adapt it - or remove it. The deploy_steps runner discovers it
 * and runs it around every `drush deploy:hook`.
 *
 * It runs in the PRE phase so the modules it enables have their run-once
 * `hook_deploy_NAME()` fired by the `deploy:hook` body that follows.
 */
#[DeployStep(
  id: 'example',
  label: new TranslatableMarkup('Example environment setup'),
  weight: 0,
  phase: DeployStepInterface::PHASE_PRE,
)]
final class ExampleDeployStep extends DeployStepBase {

  use EnvironmentTrait;
  use EnvTrait;

  /**
   * The module installer.
   */
  protected ModuleInstallerInterface $moduleInstaller;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->moduleInstaller = $container->get('module_installer');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function skip(): ?string {
    // Run only in non-production environments. The value of
    // $settings['environment'] is set in the Drupal settings file.
    return $this->environment() === 'prod' ? 'production environment' : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function run(): void {
    // Enable custom site modules. Idempotent: already-enabled modules are
    // skipped. Their run-once deploy hooks fire in the deploy:hook body because
    // this step runs in the PRE phase.
    $this->moduleInstaller->install(['my_module_search', 'my_module_demo']);

    // Conditionally act on a freshly imported database. The provision script
    // exports VORTEX_PROVISION_OVERRIDE_DB before `drush deploy:hook`, so the
    // step reads it here via getenv().
    if ($this->env('VORTEX_PROVISION_OVERRIDE_DB', '0') === '1') {
      // Fresh database detected - place one-off setup here.
    }
  }

}
