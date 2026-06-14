<?php

declare(strict_types=1);

namespace Drupal\ys_migrate\Plugin\DeployStep;

use Drupal\Core\Database\Database;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\deploy_steps\Attribute\DeployStep;
use Drupal\deploy_steps\DeployStepBase;
use Drupal\deploy_steps\DeployStepInterface;
use Drupal\deploy_steps\DrushTrait;
use Drupal\deploy_steps\EnvTrait;
use Drupal\deploy_steps\EnvironmentTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Imports migration content on non-production deploys.
 *
 * Models the deploy_steps `ImportMigrationsDeployStep` example - a thin
 * `migrate:import` shaped by DRUPAL_MIGRATION_* variables - plus a guard that
 * loads the migration source database (db2.sql) into the `migrate` connection
 * when it is not already populated, so the import has a source on a fresh
 * environment. Runs in the POST phase, after the deploy hook body.
 *
 * `migrate:import` builds a Drupal batch that Drush processes across restarting
 * subprocesses (via the DrushTrait::drush() helper), so a large import stays
 * within memory bounds; it is idempotent on re-run.
 *
 * @codeCoverageIgnore
 */
#[DeployStep(
  id: 'ys_migrate_content',
  label: new TranslatableMarkup('Import migration content'),
  weight: 0,
  phase: DeployStepInterface::PHASE_POST,
)]
final class MigrateContentDeployStep extends DeployStepBase {

  use DrushTrait;
  use EnvTrait;
  use EnvironmentTrait;

  /**
   * Table probed in the source database to verify it is populated.
   */
  protected const string PROBE_TABLE = 'categories';

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
    if ($this->environment() === 'prod') {
      return 'production environment';
    }

    if ($this->env('DRUPAL_MIGRATION_SKIP', '0') === '1') {
      return 'DRUPAL_MIGRATION_SKIP is set';
    }

    return $this->moduleHandler->moduleExists('migrate_tools') ? NULL : 'migrate_tools module is not enabled';
  }

  /**
   * {@inheritdoc}
   */
  public function run(): void {
    $this->importSourceDatabase();

    $options = ['all' => TRUE];

    // A limit of 0 imports everything; any positive value caps the batch.
    $limit = (int) $this->env('DRUPAL_MIGRATION_IMPORT_LIMIT', '50');

    if ($limit > 0) {
      $options['limit'] = $limit;
    }

    if ($this->env('DRUPAL_MIGRATION_UPDATE', '0') === '1') {
      $options['update'] = TRUE;
    }

    $this->drush('migrate:import', [], $options);
  }

  /**
   * Loads the source database dump when the migrate database is not populated.
   */
  protected function importSourceDatabase(): void {
    $import = $this->env('DRUPAL_MIGRATION_SOURCE_DB_IMPORT') ?: $this->env('VORTEX_PROVISION_OVERRIDE_DB', '0');

    if ($import !== '1' && !$this->sourceDatabaseIntact()) {
      $import = '1';
    }

    if ($import === '1') {
      $dump = $this->dumpPath();

      if (!file_exists($dump)) {
        throw new \RuntimeException(sprintf('Migration source database file not found: %s. Run "ahoy download-db2".', $dump));
      }

      $this->drush('sql:drop', [], ['database' => 'migrate']);
      $this->drush('sql:query', [], ['database' => 'migrate', 'file' => $dump]);
    }

    if (!$this->sourceDatabaseIntact()) {
      throw new \RuntimeException('Migration source database is corrupted or empty.');
    }
  }

  /**
   * Resolves the absolute path to the source database dump file.
   *
   * The configured directory is relative to the project root, but deploy steps
   * run with the Drupal root as the working directory, so a relative path is
   * resolved against the project root (the parent of the Drupal root).
   *
   * @return string
   *   The absolute path to the dump file.
   */
  protected function dumpPath(): string {
    $dir = $this->env('VORTEX_DB_DIR') ?: './.data';

    if (!str_starts_with($dir, '/')) {
      if (str_starts_with($dir, './')) {
        $dir = substr($dir, 2);
      }

      $dir = dirname($this->appRoot) . '/' . $dir;
    }

    return $dir . '/' . ($this->env('VORTEX_DOWNLOAD_DB2_FILE') ?: 'db2.sql');
  }

  /**
   * Checks whether the source database contains the probe table.
   *
   * @return bool
   *   TRUE when the probe table is queryable.
   *
   * @SuppressWarnings("PHPMD.StaticAccess")
   */
  protected function sourceDatabaseIntact(): bool {
    try {
      Database::getConnection('default', 'migrate')->select(self::PROBE_TABLE)->countQuery()->execute()->fetchField();

      return TRUE;
    }
    catch (\Exception) {
      return FALSE;
    }
  }

}
