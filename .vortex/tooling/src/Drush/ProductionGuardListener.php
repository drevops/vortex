<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Drush;

use Consolidation\Config\ConfigAwareTrait;
use Consolidation\SiteAlias\SiteAlias;
use Consolidation\SiteAlias\SiteAliasManagerAwareInterface;
use Consolidation\SiteAlias\SiteAliasManagerAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Robo\Contract\ConfigAwareInterface;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Refuses destructive Drush commands that target a production site alias.
 *
 * The production aliases are read from the 'vortex.production_aliases' Drush
 * configuration. An entry matches the alias with that name and any alias
 * whose environment has that name: 'prod' matches '@prod' and '@site.prod'.
 *
 * Setting VORTEX_DRUSH_PRODUCTION_GUARD_SKIP to 1 bypasses the guard.
 *
 * Drush discovers listeners only in the project's 'drush/Listeners' directory
 * and reads '#[AsEventListener]' from the discovered class, because PHP does
 * not inherit attributes. Extend this class there and declare the attribute
 * on the subclass.
 *
 * Services are set through the Drush '*AwareInterface' inflection. A listener
 * with a static 'create()' is registered only after a full Drupal bootstrap,
 * and a command against a remote alias never bootstraps Drupal locally.
 */
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

    $aliases = array_values(array_filter($this->resolveTargetAliases($command, $event->getInput()), $this->isProductionAlias(...)));

    if ($aliases === []) {
      return;
    }

    $alias = $aliases[0];

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
   * Resolves the alias names that a guarded command writes to.
   *
   * @param string $command
   *   The command name.
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   The command input.
   *
   * @return string[]
   *   The alias names, or an empty array if the command is not guarded or its
   *   target is not an alias.
   */
  protected function resolveTargetAliases(string $command, InputInterface $input): array {
    $target = $input->hasArgument('target') ? $input->getArgument('target') : NULL;
    $target = is_string($target) ? $target : '';

    return match ($command) {
      'sql:sync' => $this->resolveAliases($target),
      // The target is '@alias:path' or a plain path.
      'core:rsync' => $this->resolveAliases(explode(':', $target, 2)[0]),
      'sql:drop', 'site:install' => [$this->siteAliasManager()->getSelf()->name()],
      default => [],
    };
  }

  /**
   * Resolves an alias argument to the requested and the resolved alias names.
   *
   * '@self' resolves to the alias selected on the command line, and a site
   * name resolves to its default environment, such as '@prod' to '@prod.live'.
   *
   * @param string $name
   *   The argument value.
   *
   * @return string[]
   *   The requested name, followed by the resolved name if it differs, or an
   *   empty array if the value is not an alias.
   */
  protected function resolveAliases(string $name): array {
    if (!str_starts_with($name, '@')) {
      return [];
    }

    $record = $this->siteAliasManager()->get($name);

    return $record instanceof SiteAlias && $record->name() !== $name ? [$name, $record->name()] : [$name];
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

    return array_filter($this->productionAliases(), fn(string $production_alias): bool => $name === $production_alias || str_ends_with($name, '.' . $production_alias)) !== [];
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
