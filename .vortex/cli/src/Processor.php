<?php

declare(strict_types=1);

namespace DrevOps\VortexCli;

use DrevOps\Customizer\Config\Config;
use DrevOps\Customizer\Config\Field;
use DrevOps\Customizer\Config\FieldType;
use DrevOps\Customizer\Handler\Context;
use DrevOps\Customizer\Handler\HandlerInterface;
use DrevOps\Customizer\Handler\HandlerRegistry;

/**
 * Applies collected answers to a project directory via the handler processors.
 *
 * @package DrevOps\VortexCli
 */
class Processor {

  /**
   * The order in which processors apply, mirroring the installer.
   *
   * Reverse of the collection order so specific string replacements run before
   * generic ones, with the '.env' carry first and internal cleanup last. Only
   * active fields (present in the answers) process; the internal processors
   * ('dotenv' and 'internal') always run.
   */
  public const ORDER = [
    'dotenv',
    'webroot',
    'ai_code_instructions',
    'preserve_docs_project',
    'label_merge_conflicts_pr',
    'assign_author_pr',
    'code_coverage_provider',
    'dependency_updates_provider',
    'visual_regression',
    'ci_provider',
    'migration_image',
    'migration_fetch_source',
    'migration',
    'database_image',
    'database_fetch_source',
    'provision_type',
    'notification_channels',
    'deploy_types',
    'hosting_provider',
    'tools',
    'services',
    'timezone',
    'version_scheme',
    'code_provider',
    'modules',
    'starter',
    'profile_custom',
    'profile',
    'domain',
    'hosting_project_name',
    'custom_modules',
    'module_prefix',
    'frontend_build',
    'theme_custom',
    'theme',
    'org_machine_name',
    'machine_name',
    'org',
    'name',
    'internal',
  ];

  /**
   * Apply the collected answers to the project directory.
   *
   * @param \DrevOps\Customizer\Config\Config $config
   *   The configuration.
   * @param \DrevOps\Customizer\Handler\HandlerRegistry $handlers
   *   The handler registry.
   * @param array<string,mixed> $answers
   *   The collected answers.
   * @param \DrevOps\Customizer\Handler\Context $context
   *   The run context (carrying the answers and version).
   */
  public function apply(Config $config, HandlerRegistry $handlers, array $answers, Context $context): void {
    $fields = [];
    foreach ($config->fields() as $field) {
      $fields[$field->id] = $field;
    }

    $placeholder = new Field('', '', '', FieldType::Text, NULL);

    foreach (static::ORDER as $id) {
      $handler = $handlers->get($id);

      if (!$handler instanceof HandlerInterface) {
        continue;
      }

      $is_internal = !isset($fields[$id]);

      if (!$is_internal && !array_key_exists($id, $answers)) {
        continue;
      }

      $handler->process($fields[$id] ?? $placeholder, $answers[$id] ?? NULL, $context);
    }
  }

}
