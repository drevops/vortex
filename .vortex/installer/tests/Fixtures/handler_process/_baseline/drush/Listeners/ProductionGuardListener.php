<?php

declare(strict_types=1);

namespace Drush\Listeners;

use DrevOps\VortexTooling\Drush\ProductionGuardListener as VortexProductionGuardListener;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

// Releases of 'drevops/vortex-tooling' before the guard do not ship the
// parent class; declaring the subclass then would break every Drush command.
if (!class_exists(VortexProductionGuardListener::class)) {
  return;
}

/**
 * Refuses destructive Drush commands that target a production site alias.
 *
 * Override a method of the parent class to change the guard for this project.
 *
 * The priority runs the guard before the 'core:rsync' command event hook,
 * which connects to the target to evaluate its path.
 */
#[AsEventListener(priority: 100)]
class ProductionGuardListener extends VortexProductionGuardListener {

}
