<?php

declare(strict_types=1);

namespace Drupal\ys_deploy\Drush\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\Hooks\HookManager;
use Drupal\ys_deploy\DeployStepManager;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Runs repeatable deploy step plugins on every `drush deploy:hook`.
 *
 * Drupal/Drush run-once hooks (hook_update_N(), hook_post_update_NAME() and
 * hook_deploy_NAME()) are recorded as completed and never run again, so they
 * cannot express "run on every deploy". This command provides that missing
 * layer: it discovers every DeployStep plugin from every enabled module, orders
 * them by weight, asks each plugin's gate whether to run, and runs the rest -
 * on every single deploy.
 *
 * The design deliberately inverts the naive "one Drush command hook per module"
 * approach, which does not scale: Drush discovers command hooks at bootstrap, so
 * a module could only contribute deploy logic by shipping its own DrushCommands
 * class AND being enabled before bootstrap. Here, ys_deploy owns the single
 * command hook and DISCOVERS plugins; any enabled module contributes steps just
 * by declaring a DeployStep plugin - no Drush wiring of its own. That makes the
 * mechanism reusable (and extractable to a standalone contrib module).
 *
 * The hook targets `deploy:hook` because that is the Drush command the Vortex
 * provision flow runs in every environment; the higher-level `deploy` command is
 * never invoked directly.
 */
final class DeployCommands extends DrushCommands {

  /**
   * Constructs a DeployCommands object.
   *
   * @param \Drupal\ys_deploy\DeployStepManager $deployStepManager
   *   The deploy step plugin manager.
   */
  public function __construct(
    protected readonly DeployStepManager $deployStepManager,
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
    return new self($container->get('plugin.manager.ys_deploy.deploy_step'));
  }

  /**
   * Runs all deploy step plugins after EVERY `drush deploy:hook`.
   *
   * @param mixed $result
   *   The result returned by the `deploy:hook` command.
   * @param \Consolidation\AnnotatedCommand\CommandData $command_data
   *   The command data.
   *
   * @SuppressWarnings("PHPMD.UnusedFormalParameter")
   */
  #[CLI\Hook(type: HookManager::POST_COMMAND_HOOK, target: 'deploy:hook')]
  public function runDeploySteps(mixed $result, CommandData $command_data): void {
    foreach ($this->deployStepManager->getSortedSteps() as $step) {
      $skip_reason = $step->gate();

      if ($skip_reason !== NULL) {
        $this->logger()?->notice(sprintf('Skipped deploy step "%s": %s', $step->label(), $skip_reason));
        continue;
      }

      $this->logger()?->notice(sprintf('Running deploy step "%s".', $step->label()));
      $step->run();
    }
  }

}
