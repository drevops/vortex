<?php

declare(strict_types=1);

namespace Drupal\the_force_base\Plugin\DeployStep;

use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\deploy_steps\Attribute\DeployStep;
use Drupal\deploy_steps\DeployStepBase;
use Drupal\deploy_steps\DeployStepInterface;
use Drupal\deploy_steps\EnvironmentTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sets up the development and demo environment on non-production deploys.
 *
 * Runs in the PRE phase so the modules it enables are picked up by the
 * `deploy:hook` body that follows: their run-once `hook_deploy_NAME()` fire in
 * the same deploy, with no second `deploy:hook` run. Idempotent - module
 * installs skip already-enabled modules - so it is safe on every deploy.
 *
 * @codeCoverageIgnore
 */
#[DeployStep(
  id: 'the_force_base_development_setup',
  label: new TranslatableMarkup('Development and demo environment setup'),
  weight: 0,
  phase: DeployStepInterface::PHASE_PRE,
)]
final class EnableDevelopmentModulesDeployStep extends DeployStepBase {

  use EnvironmentTrait;

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
    return $this->environment() === 'prod' ? 'production environment' : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function run(): void {
    $this->configFactory->getEditable('system.site')->set('name', 'New hope')->save();

    $this->moduleInstaller->install([
      'admin_toolbar',
      'coffee',
      'config_split',
      'config_update',
      'media',
      'environment_indicator',
      'pathauto',
      'redirect',
      'reroute_email',
      'robotstxt',
      'shield',
      'stage_file_proxy',
      'xmlsitemap',
    ]);

    $this->moduleInstaller->install(['redis']);

    $this->moduleInstaller->install(['clamav']);
    $this->configFactory->getEditable('clamav.settings')->set('mode_daemon_tcpip.hostname', 'clamav')->save();

    $this->moduleInstaller->install(['search_api', 'search_api_solr']);

    $this->moduleInstaller->install(['the_force_search']);

    $this->moduleInstaller->install(['the_force_demo']);
  }

}
