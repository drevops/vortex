<?php

declare(strict_types=1);

namespace Drupal\ys_migrate\Plugin\PersistentDeploy;

use Drupal\Core\Database\Database;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\persistent_deploy\Attribute\PersistentDeploy;
use Drupal\persistent_deploy\PersistentDeployBase;
use Drupal\persistent_deploy\PersistentDeployInterface;

/**
 * Imports content from the migration source database on non-production deploys.
 *
 * Runs in the POST phase, after the deploy hook body. The long-running
 * `migrate:import` runs as a subprocess via the inherited drush() helper, so it
 * gets its own memory ceiling and is resumable from the migrate map tables on
 * re-run. Idempotent - re-importing only processes unprocessed rows.
 */
#[PersistentDeploy(
  id: 'ys_migrate_content',
  label: new TranslatableMarkup('Import migration content'),
  weight: 0,
  phase: PersistentDeployInterface::PHASE_POST,
)]
final class MigrateContent extends PersistentDeployBase {

  /**
   * Migrations to run, in order.
   */
  protected const MIGRATIONS = ['ys_migrate_categories'];

  /**
   * Table probed in the source database to verify it is not corrupted.
   */
  protected const PROBE_TABLE = 'categories';

  /**
   * {@inheritdoc}
   */
  public function gate(): ?string {
    if ($this->isProduction()) {
      return 'production environment';
    }

    if (getenv('DRUPAL_MIGRATION_SKIP') === '1') {
      return 'migrations skipped (DRUPAL_MIGRATION_SKIP)';
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function run(): void {
    $this->importSourceDatabase();

    if (getenv('DRUPAL_MIGRATION_ROLLBACK_SKIP') !== '1') {
      $this->drush('migrate:rollback', [], ['all' => TRUE]);
    }

    $limit = (int) (getenv('DRUPAL_MIGRATION_IMPORT_LIMIT') ?: 50);
    $feedback = (int) (getenv('DRUPAL_MIGRATION_FEEDBACK') ?: 50);

    foreach (self::MIGRATIONS as $migration) {
      $this->drush('migrate:reset-status', [$migration]);

      $options = ['feedback' => $feedback];

      if ($limit > 0) {
        $options['limit'] = $limit;
      }

      if (getenv('DRUPAL_MIGRATION_UPDATE') === '1') {
        $options['update'] = TRUE;
      }

      $this->drush('migrate:import', [$migration], $options);
    }
  }

  /**
   * Imports the source database dump when requested or when it is not intact.
   */
  protected function importSourceDatabase(): void {
    $import = getenv('DRUPAL_MIGRATION_SOURCE_DB_IMPORT') ?: (getenv('VORTEX_PROVISION_OVERRIDE_DB') ?: '0');

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
   *
   * @SuppressWarnings("PHPMD.StaticAccess")
   */
  protected function dumpPath(): string {
    $dir = getenv('VORTEX_DB_DIR') ?: './.data';

    if (!str_starts_with($dir, '/')) {
      if (str_starts_with($dir, './')) {
        $dir = substr($dir, 2);
      }

      $dir = dirname(\Drupal::root()) . '/' . $dir;
    }

    return $dir . '/' . (getenv('VORTEX_DOWNLOAD_DB2_FILE') ?: 'db2.sql');
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
