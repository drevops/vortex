<?php

declare(strict_types=1);

namespace Drupal\ys_base\Plugin\DeployStep;

use Drupal\Core\Extension\ModuleHandlerInterface;
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
  id: 'ys_base_development_setup',
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
   * The module handler.
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->moduleInstaller = $container->get('module_installer');
    $instance->moduleHandler = $container->get('module_handler');

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
    $this->configFactory->getEditable('system.site')->set('name', 'YOURSITE')->save();

    // Use the core Navigation module as the administration interface and remove
    // the classic Toolbar so the two admin systems never run at once. Uninstall
    // only when Toolbar is actually enabled - it is absent on a re-provision or
    // a navigation-based database - while a genuine uninstall failure still
    // aborts.
    $this->moduleInstaller->install(['navigation']);
    if ($this->moduleHandler->moduleExists('toolbar')) {
      $this->moduleInstaller->uninstall(['toolbar']);
    }

    // phpcs:ignore #;< MODULES
    $this->moduleInstaller->install([
      'coffee',
      'config_split',
      'config_update',
      'media',
      'environment_indicator',
      'navigation_extra_tools',
      'pathauto',
      'redirect',
      'reroute_email',
      'robotstxt',
      'shield',
      'stage_file_proxy',
      'xmlsitemap',
    ]);
    // phpcs:ignore #;> MODULES

    // phpcs:ignore #;< SERVICE_CACHE
    $this->moduleInstaller->install(['redis']);
    // phpcs:ignore #;> SERVICE_CACHE

    // phpcs:ignore #;< SERVICE_ANTIVIRUS
    $this->moduleInstaller->install(['clamav']);
    $this->configFactory->getEditable('clamav.settings')->set('mode_daemon_tcpip.hostname', 'clamav')->save();
    // phpcs:ignore #;> SERVICE_ANTIVIRUS

    // phpcs:ignore #;< SERVICE_SEARCH
    $this->moduleInstaller->install(['search_api', 'search_api_solr']);
    // phpcs:ignore #;> SERVICE_SEARCH

    // phpcs:ignore #;< MODULE_SDC_DEVEL
    $this->moduleInstaller->install(['sdc_devel']);
    // phpcs:ignore #;> MODULE_SDC_DEVEL

    // phpcs:ignore #;< MODULE_DEVEL
    $this->moduleInstaller->install(['devel']);
    // phpcs:ignore #;> MODULE_DEVEL

    // phpcs:ignore #;< CUSTOM_MODULE_SEARCH
    $this->moduleInstaller->install(['ys_search']);
    // phpcs:ignore #;> CUSTOM_MODULE_SEARCH

    // phpcs:ignore #;< CUSTOM_MODULE_DEMO
    $this->moduleInstaller->install(['ys_demo']);
    // phpcs:ignore #;> CUSTOM_MODULE_DEMO
  }

}
