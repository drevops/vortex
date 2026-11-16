<?php

declare(strict_types=1);

namespace Drush\Listeners;

use Consolidation\Config\ConfigAwareTrait;
use Consolidation\SiteAlias\SiteAlias;
use Consolidation\SiteAlias\SiteAliasManagerAwareInterface;
use Consolidation\SiteAlias\SiteAliasManagerAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Robo\Contract\ConfigAwareInterface;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Refuses destructive Drush commands that target a production site alias.
 *
 * The production aliases are read from the 'vortex.production_aliases' Drush
 * configuration. An entry matches the alias with that name and any alias
 * whose environment has that name: 'prod' matches '@prod' and '@site.prod'.
 *
 * Setting VORTEX_DRUSH_PRODUCTION_GUARD_SKIP to 1 bypasses the guard.
 *
 * Services are set through the Drush '*AwareInterface' inflection. A listener
 * with a static 'create()' is registered only after a full Drupal bootstrap,
 * and a command against a remote alias never bootstraps Drupal locally.
 *
 * The priority runs the guard before the 'core:rsync' command event hook,
 * which connects to the target to evaluate its path.
 */
#[AsEventListener(priority: 100)]
class ProductionGuardListener implements ConfigAwareInterface, LoggerAwareInterface, SiteAliasManagerAwareInterface {

  use ConfigAwareTrait;
  use LoggerAwareTrait;
  use SiteAliasManagerAwareTrait;

  /**
   * Drush configuration key with the production aliases.
   */
  const CONFIG_KEY = 'vortex.production_aliases';

  /**
   * Environment variable that bypasses the guard when set to 1.
   */
  const SKIP_VARIABLE = 'VORTEX_DRUSH_PRODUCTION_GUARD_SKIP';

  /**
   * Disables a guarded command that targets a production alias.
   *
   * @param \Symfony\Component\Console\Event\ConsoleCommandEvent $event
   *   The event fired before the command runs.
   */
  public function __invoke(ConsoleCommandEvent $event): void {
    $command = $event->getCommand()?->getName();

    if ($command === NULL) {
      return;
    }

    $alias = $this->resolveTargetAlias($command, $event->getInput());

    if ($alias === NULL || !$this->isProductionAlias($alias)) {
      return;
    }

    $context = ['command' => $command, 'alias' => $alias, 'variable' => self::SKIP_VARIABLE];

    if (getenv(self::SKIP_VARIABLE) === '1') {
      $this->logger?->warning('Running {command} against the production site alias {alias} because {variable} is set to 1.', $context);

      return;
    }

    $this->logger?->error('Refusing to run {command} against the production site alias {alias}. Set {variable}=1 to bypass this guard.', $context);
    $event->disableCommand();
    $event->stopPropagation();
  }

  /**
   * Resolves the alias that a guarded command writes to.
   *
   * @param string $command
   *   The command name.
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   The command input.
   *
   * @return string|null
   *   The alias name, or NULL if the command is not guarded or its target is
   *   not an alias.
   */
  protected function resolveTargetAlias(string $command, InputInterface $input): ?string {
    return match ($command) {
      'sql:sync' => $this->resolveAlias((string) $input->getArgument('target')),
      // The target is '@alias:path' or a plain path.
      'core:rsync' => $this->resolveAlias(explode(':', (string) $input->getArgument('target'), 2)[0]),
      'sql:drop', 'site:install' => $this->siteAliasManager()->getSelf()->name(),
      default => NULL,
    };
  }

  /**
   * Resolves an alias argument to the name of its alias record.
   *
   * @param string $name
   *   The argument value.
   *
   * @return string|null
   *   The alias name, or NULL if the value is not an alias.
   */
  protected function resolveAlias(string $name): ?string {
    if (!str_starts_with($name, '@')) {
      return NULL;
    }

    // '@self' resolves to the alias selected on the command line.
    $record = $this->siteAliasManager()->get($name);

    return $record instanceof SiteAlias ? $record->name() : $name;
  }

  /**
   * Checks whether an alias matches a configured production alias.
   *
   * @param string $alias
   *   The alias name.
   *
   * @return bool
   *   TRUE if the alias is a production alias, FALSE otherwise.
   */
  protected function isProductionAlias(string $alias): bool {
    $name = ltrim($alias, '@');

    return array_any($this->productionAliases(), fn(string $production_alias): bool => $name === $production_alias || str_ends_with($name, '.' . $production_alias));
  }

  /**
   * Gets the configured production aliases.
   *
   * @return string[]
   *   The production alias names without the leading '@'.
   */
  protected function productionAliases(): array {
    $value = $this->getConfig()->get(self::CONFIG_KEY) ?? [];
    $values = is_array($value) ? $value : [$value];

    $aliases = [];

    foreach ($values as $item) {
      $alias = is_string($item) ? ltrim(trim($item), '@') : '';

      if ($alias !== '') {
        $aliases[] = $alias;
      }
    }

    return $aliases;
  }

}
