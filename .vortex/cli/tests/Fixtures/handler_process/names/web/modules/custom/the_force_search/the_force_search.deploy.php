<?php

/**
 * @file
 * Deploy hooks for the the_force_search module.
 */

declare(strict_types=1);

use Drupal\content_moderation\Plugin\WorkflowType\ContentModerationInterface;
use Drupal\workflows\Entity\Workflow;

/**
 * Attach editorial workflow to the page content type.
 *
 * Computed base fields like 'moderation_state' are only available when a
 * workflow is attached to the content type. This deploy hook creates the
 * editorial workflow if it does not exist and attaches the page content type.
 */
function the_force_search_deploy_add_editorial_workflow(): string {
  /** @var \Drupal\Core\Extension\ModuleInstallerInterface $installer */
  $installer = \Drupal::service('module_installer');
  $installer->install(['content_moderation']);

  $workflow = Workflow::load('editorial');

  if (!$workflow) {
    $workflow = Workflow::create([
      'id' => 'editorial',
      'label' => 'Editorial',
      'type' => 'content_moderation',
      'type_settings' => [
        'states' => [
          'draft' => [
            'label' => 'Draft',
            'published' => FALSE,
            'default_revision' => FALSE,
            'weight' => -5,
          ],
          'published' => [
            'label' => 'Published',
            'published' => TRUE,
            'default_revision' => TRUE,
            'weight' => 0,
          ],
        ],
        'transitions' => [
          'create_new_draft' => [
            'label' => 'Create New Draft',
            'from' => ['draft', 'published'],
            'to' => 'draft',
            'weight' => 0,
          ],
          'publish' => [
            'label' => 'Publish',
            'from' => ['draft', 'published'],
            'to' => 'published',
            'weight' => 1,
          ],
        ],
        'default_moderation_state' => 'published',
      ],
    ]);
  }

  $type_plugin = $workflow->getTypePlugin();
  if ($type_plugin instanceof ContentModerationInterface) {
    $type_plugin->addEntityTypeAndBundle('node', 'page');
  }

  $workflow->save();

  return 'Attached editorial workflow to page content type.';
}
