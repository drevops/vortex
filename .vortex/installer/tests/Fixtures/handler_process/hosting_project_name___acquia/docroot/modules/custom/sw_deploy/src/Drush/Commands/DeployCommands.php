<?php

declare(strict_types=1);

namespace Drupal\sw_deploy\Drush\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\Hooks\HookManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Site\Settings;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Repeatable deploy-time tasks for this project.
 *
 * Drupal/Drush run-once hooks (hook_update_N(), hook_post_update_NAME() and
 * hook_deploy_NAME()) are recorded as completed and never run again. This class
 * is the home for logic that must run on EVERY deploy - a search reindex, a
 * re-runnable migration, cache warmups - expressed as Drush command hooks that
 * are NOT tracked as run-once. It replaces the legacy "persistent update" hack.
 *
 * A Vortex project has two deploy-time layers:
 * - Drupal-level "every deploy" -> these command hooks. They run wherever
 *   `drush deploy:hook` runs (CI, local, and production hosting post-rollout).
 * - Vortex tooling-level -> the pre/post provision event scripts, for
 *   orchestration that happens outside `drush deploy` (e.g. work before the
 *   database is imported).
 *
 * The hooks target `deploy:hook` because that is the Drush command the Vortex
 * provision flow runs in every environment (see the shipped
 * `vortex-tooling/src/provision` script). Targeting the higher-level `deploy`
 * command would never fire, because the provision flow runs the underlying
 * steps (updatedb, config import, cache rebuild, deploy:hook) individually.
 *
 * To add your own repeatable logic, add steps to ::preDeploySteps() or
 * ::postDeploySteps(). Steps run in array order. Keep every step idempotent -
 * it runs on every single deploy.
 */
final class DeployCommands extends DrushCommands {

  /**
   * Constructs a DeployCommands object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $moduleInstaller
   *   The module installer.
   */
  public function __construct(
    protected readonly ModuleHandlerInterface $moduleHandler,
    protected readonly ModuleInstallerInterface $moduleInstaller,
  ) {
    parent::__construct();
  }

  /**
   * Creates an instance of the command handler.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   *
   * @return self
   *   The command handler instance.
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('module_handler'),
      $container->get('module_installer'),
    );
  }

  /**
   * Runs before EVERY `drush deploy:hook`, in every environment.
   *
   * Use this for preparation that must happen before the run-once deploy hooks
   * execute (for example, enabling a module so its own deploy hooks can run).
   *
   * @param \Consolidation\AnnotatedCommand\CommandData $command_data
   *   The command data.
   *
   * @SuppressWarnings("PHPMD.UnusedFormalParameter")
   */
  #[CLI\Hook(type: HookManager::PRE_COMMAND_HOOK, target: 'deploy:hook')]
  public function preDeploy(CommandData $command_data): void {
    $this->runSteps('pre-deploy', $this->preDeploySteps());
  }

  /**
   * Runs after EVERY `drush deploy:hook`, in every environment.
   *
   * The correct home for idempotent, must-run-on-every-deploy work that is not
   * recorded as done (so it is never skipped): search reindex, a re-runnable
   * migration, cache warmups. Contrast hook_deploy_NAME(), which is run-once.
   *
   * @param mixed $result
   *   The result returned by the `deploy:hook` command.
   * @param \Consolidation\AnnotatedCommand\CommandData $command_data
   *   The command data.
   *
   * @SuppressWarnings("PHPMD.UnusedFormalParameter")
   */
  #[CLI\Hook(type: HookManager::POST_COMMAND_HOOK, target: 'deploy:hook')]
  public function postDeploy(mixed $result, CommandData $command_data): void {
    $this->runSteps('post-deploy', $this->postDeploySteps());
  }

  /**
   * Returns the ordered pre-deploy steps for this project.
   *
   * Add project-specific, idempotent steps here. The array key is a
   * human-readable label and the value is a callable run with no arguments.
   * Steps run in array order. For example:
   * @code
   * return [
   *   'Enable a module' => fn() => $this->installModules(['my_module']),
   * ];
   * @endcode
   *
   * @return array<string, callable>
   *   Ordered map of label => callable.
   */
  protected function preDeploySteps(): array {
    return [];
  }

  /**
   * Returns the ordered post-deploy steps for this project.
   *
   * Add project-specific, idempotent steps here. The array key is a
   * human-readable label and the value is a callable run with no arguments.
   * Steps run in array order. Replace or extend the default step with real
   * repeatable work. For example:
   * @code
   * return [
   *   'Rebuild the search index' => fn() => $this->reindexSearch(),
   *   'Re-run a migration' => fn() => $this->runMigration('my_migration'),
   * ];
   * @endcode
   *
   * @return array<string, callable>
   *   Ordered map of label => callable.
   */
  protected function postDeploySteps(): array {
    return [
      'Log deployment environment' => function (): void {
        $message = sprintf('Deployed to "%s".', $this->environment());
        $this->logger()?->notice($message);
      },
    ];
  }

  /**
   * Runs an ordered list of named steps, logging each one.
   *
   * Ordering is expressed by the array order. A step that throws aborts the
   * sequence so the deploy fails loudly rather than continuing silently.
   *
   * @param string $phase
   *   A short phase label used in log lines (e.g. "pre-deploy").
   * @param array<string, callable> $steps
   *   Ordered map of human-readable label => callable.
   */
  protected function runSteps(string $phase, array $steps): void {
    foreach ($steps as $label => $step) {
      $message = sprintf('[%s] %s', $phase, $label);
      $this->logger()?->notice($message);
      $step();
    }
  }

  /**
   * Returns the current environment machine name.
   *
   * @return string
   *   One of the ENVIRONMENT_* values (local, ci, dev, stage, prod) or an empty
   *   string when not set.
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

  /**
   * Idempotently installs (enables) the given modules.
   *
   * Modules that are already enabled are skipped, so this is safe to call on
   * every deploy.
   *
   * @param string[] $modules
   *   Module machine names.
   */
  protected function installModules(array $modules): void {
    $missing = array_values(array_filter(
      $modules,
      fn(string $module): bool => !$this->moduleHandler->moduleExists($module),
    ));

    if ($missing) {
      $this->moduleInstaller->install($missing);
    }
  }

}
