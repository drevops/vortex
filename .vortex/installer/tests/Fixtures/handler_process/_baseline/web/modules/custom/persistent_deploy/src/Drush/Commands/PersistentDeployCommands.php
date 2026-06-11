<?php

declare(strict_types=1);

namespace Drupal\persistent_deploy\Drush\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\Hooks\HookManager;
use Drupal\persistent_deploy\PersistentDeployInterface;
use Drupal\persistent_deploy\PersistentDeployRunner;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Runs persistent deploy plugins around every `drush deploy:hook`.
 *
 * Drupal/Drush run-once hooks (hook_update_N(), hook_post_update_NAME() and
 * hook_deploy_NAME()) are recorded as completed and never run again, so they
 * cannot express "run on every deploy". This command provides that missing
 * layer: it discovers every PersistentDeploy plugin from every enabled module,
 * groups them by phase, orders each phase by weight, asks each plugin's gate
 * whether to run, and runs the rest - on every single deploy. Pre-phase plugins
 * run before the `deploy:hook` body, post-phase plugins after it.
 *
 * The design inverts the naive "one Drush command hook per module" approach,
 * which does not scale: Drush discovers command hooks at bootstrap, so a
 * module could only contribute deploy logic by shipping its own DrushCommands
 * class AND being enabled before bootstrap. Here, persistent_deploy owns the
 * single command hook and DISCOVERS plugins; any enabled module contributes
 * steps by declaring a PersistentDeploy plugin - no Drush wiring of its own.
 * That makes the mechanism reusable (and extractable to a contrib module).
 *
 * The hooks target `deploy:hook` because that is the Drush command the Vortex
 * provision flow runs in every environment; the higher-level `deploy` command
 * is never invoked directly.
 */
final class PersistentDeployCommands extends DrushCommands {

  /**
   * Constructs a PersistentDeployCommands object.
   *
   * @param \Drupal\persistent_deploy\PersistentDeployRunner $runner
   *   The persistent deploy runner.
   */
  public function __construct(
    protected readonly PersistentDeployRunner $runner,
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
    return new self($container->get(PersistentDeployRunner::class));
  }

  /**
   * Runs PRE-phase plugins before EVERY `drush deploy:hook`.
   *
   * @param \Consolidation\AnnotatedCommand\CommandData $command_data
   *   The command data.
   *
   * @SuppressWarnings("PHPMD.UnusedFormalParameter")
   */
  #[CLI\Hook(type: HookManager::PRE_COMMAND_HOOK, target: 'deploy:hook')]
  public function runPreDeploySteps(CommandData $command_data): void {
    $this->runner->run(PersistentDeployInterface::PHASE_PRE);
  }

  /**
   * Runs POST-phase plugins after EVERY `drush deploy:hook`.
   *
   * @param mixed $result
   *   The result returned by the `deploy:hook` command.
   * @param \Consolidation\AnnotatedCommand\CommandData $command_data
   *   The command data.
   *
   * @SuppressWarnings("PHPMD.UnusedFormalParameter")
   */
  #[CLI\Hook(type: HookManager::POST_COMMAND_HOOK, target: 'deploy:hook')]
  public function runPostDeploySteps(mixed $result, CommandData $command_data): void {
    $this->runner->run(PersistentDeployInterface::PHASE_POST);
  }

}
