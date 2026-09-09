<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts;

/**
 * Sections of the prompt chain.
 *
 * The case order is the order in which the sections are introduced.
 */
enum PromptSection: string {

  case General = 'General information';

  case Drupal = 'Drupal';

  case CodeRepository = 'Code repository';

  case Environment = 'Environment';

  case Hosting = 'Hosting';

  case Deployment = 'Deployment';

  case Workflow = 'Workflow';

  case Notifications = 'Notifications';

  case ContinuousIntegration = 'Continuous Integration';

  case Automations = 'Automations';

  case Documentation = 'Documentation';

  case Ai = 'AI';

}
