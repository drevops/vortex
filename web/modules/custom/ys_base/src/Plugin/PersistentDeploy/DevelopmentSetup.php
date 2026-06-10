<?php

declare(strict_types=1);

namespace Drupal\ys_base\Plugin\PersistentDeploy;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\persistent_deploy\Attribute\PersistentDeploy;
use Drupal\persistent_deploy\PersistentDeployBase;
use Drupal\persistent_deploy\PersistentDeployInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sets up the development and demo environment on non-production deploys.
 *
 * Runs in the PRE phase so the modules it enables are picked up by the
 * `deploy:hook` body that follows: their run-once `hook_deploy_NAME()` fire in
 * the same deploy, with no second `deploy:hook` run. Idempotent - module
 * installs skip already-enabled modules - so it is safe on every deploy.
 */
#[PersistentDeploy(
  id: 'ys_base_development_setup',
  label: new TranslatableMarkup('Development and demo environment setup'),
  weight: 0,
  phase: PersistentDeployInterface::PHASE_PRE,
)]
final class DevelopmentSetup extends PersistentDeployBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a DevelopmentSetup object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $moduleInstaller
   *   The module installer.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
    protected readonly ModuleInstallerInterface $moduleInstaller,
    protected readonly ConfigFactoryInterface $configFactory,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self($configuration, $plugin_id, $plugin_definition, $container->get('module_installer'), $container->get('config.factory'));
  }

  /**
   * {@inheritdoc}
   */
  public function gate(): ?string {
    return $this->isProduction() ? 'production environment' : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function run(): void {
    $this->configFactory->getEditable('system.site')->set('name', 'YOURSITE')->save();

    // phpcs:ignore #;< MODULES
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
    // phpcs:ignore #;> MODULES

    // phpcs:ignore #;< SERVICE_REDIS
    $this->moduleInstaller->install(['redis']);
    // phpcs:ignore #;> SERVICE_REDIS

    // phpcs:ignore #;< SERVICE_CLAMAV
    $this->moduleInstaller->install(['clamav']);
    $this->configFactory->getEditable('clamav.settings')->set('mode_daemon_tcpip.hostname', 'clamav')->save();
    // phpcs:ignore #;> SERVICE_CLAMAV

    // phpcs:ignore #;< SERVICE_SOLR
    $this->moduleInstaller->install(['search_api', 'search_api_solr']);
    // phpcs:ignore #;> SERVICE_SOLR

    // phpcs:ignore #;< CUSTOM_MODULE_SEARCH
    $this->moduleInstaller->install(['ys_search']);
    // phpcs:ignore #;> CUSTOM_MODULE_SEARCH

    // phpcs:ignore #;< CUSTOM_MODULE_DEMO
    $this->moduleInstaller->install(['ys_demo']);
    // phpcs:ignore #;> CUSTOM_MODULE_DEMO
  }

}
