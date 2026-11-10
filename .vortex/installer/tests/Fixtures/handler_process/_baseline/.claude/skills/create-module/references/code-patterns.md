# Code patterns for a custom module

Each example uses `sw_base` as the module name. Replace it, the `Drupal\sw_base` namespace and the `SwBase` class prefix with the module you're working on. Add the `@file` docblocks, the class and method docblocks, and `declare(strict_types=1);` that the shipped modules carry, so `ahoy lint` passes.

## Info file

`web/modules/custom/sw_base/sw_base.info.yml`:

```yaml
name: star wars Base
type: module
description: Base feature for star wars site.
core_version_requirement: ^11
package: star_wars
dependencies:
  - drupal:node
```

## Service with autowiring

`sw_base.services.yml`:

```yaml
services:
  logger.channel.sw_base:
    parent: logger.channel_base
    arguments: ['sw_base']

  sw_base.recent_content:
    class: Drupal\sw_base\RecentContent
    autowire: true
  Drupal\sw_base\RecentContent: '@sw_base.recent_content'
```

`src/RecentContent.php`:

```php
<?php

declare(strict_types=1);

namespace Drupal\sw_base;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Loads recently published content.
 */
class RecentContent {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    #[Autowire(service: 'logger.channel.sw_base')]
    protected LoggerInterface $logger,
  ) {}

  /**
   * Returns the most recently created published nodes.
   *
   * @return \Drupal\node\NodeInterface[]
   *   The nodes, newest first.
   */
  public function latest(int $limit = 5): array {
    $storage = $this->entityTypeManager->getStorage('node');

    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('status', 1)
      ->sort('created', 'DESC')
      ->range(0, $limit)
      ->execute();

    return $storage->loadMultiple($ids);
  }

}
```

Other classes now type-hint `RecentContent` in their constructors and receive the service.

## Hook class

`src/Hook/NodeHooks.php`. Core registers the class as an autowired service, so it needs no entry in `sw_base.services.yml`. Rebuild caches with `ahoy drush cr` after adding it.

```php
<?php

declare(strict_types=1);

namespace Drupal\sw_base\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\NodeInterface;

/**
 * Node hook implementations for the sw_base module.
 */
class NodeHooks {

  public function __construct(
    protected AccountProxyInterface $currentUser,
  ) {}

  /**
   * Implements hook_node_presave().
   */
  #[Hook('node_presave')]
  public function nodePresave(NodeInterface $node): void {
    if ($node->isNew() && $node->getOwnerId() === 0) {
      $node->setOwnerId((int) $this->currentUser->id());
    }
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(): array {
    return [
      'sw_base_recent_content' => [
        'variables' => ['items' => []],
      ],
    ];
  }

}
```

A theme hook named `sw_base_recent_content` renders `templates/sw-base-recent-content.html.twig` from the module.

## Block plugin with an attribute

`src/Plugin/Block/RecentContentBlock.php`:

```php
<?php

declare(strict_types=1);

namespace Drupal\sw_base\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\sw_base\RecentContent;

/**
 * Lists the most recent content.
 */
#[Block(
  id: 'sw_base_recent_content',
  admin_label: new TranslatableMarkup('Recent content'),
  category: new TranslatableMarkup('star wars'),
)]
class RecentContentBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected RecentContent $recentContent,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $build = ['#theme' => 'sw_base_recent_content', '#items' => []];
    $cache = new CacheableMetadata();
    $cache->addCacheTags(['node_list']);

    foreach ($this->recentContent->latest() as $node) {
      $build['#items'][] = $node->toLink()->toRenderable();
      $cache->addCacheableDependency($node);
    }

    $cache->applyTo($build);

    return $build;
  }

}
```

`PluginBase` passes the first 3 constructor arguments and autowires the rest from their type hints, because the class implements `ContainerFactoryPluginInterface`.

## Route, permission and controller

`sw_base.routing.yml`:

```yaml
sw_base.recent_content:
  path: '/admin/reports/recent-content'
  defaults:
    _controller: '\Drupal\sw_base\Controller\RecentContentController::build'
    _title: 'Recent content'
  requirements:
    _permission: 'view sw_base recent content'
```

`sw_base.permissions.yml`:

```yaml
'view sw_base recent content':
  title: 'View the recent content report'
```

`src/Controller/RecentContentController.php`:

```php
<?php

declare(strict_types=1);

namespace Drupal\sw_base\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\sw_base\RecentContent;

/**
 * Builds the recent content report.
 */
class RecentContentController extends ControllerBase {

  public function __construct(
    protected RecentContent $recentContent,
  ) {}

  /**
   * Builds the report page.
   */
  public function build(): array {
    $items = [];

    foreach ($this->recentContent->latest(20) as $node) {
      $items[] = $node->toLink()->toRenderable();
    }

    return [
      '#theme' => 'item_list',
      '#items' => $items,
      '#empty' => $this->t('No content yet.'),
      '#cache' => ['tags' => ['node_list']],
    ];
  }

}
```

`ControllerBase` already defines `$entityTypeManager`, `$configFactory`, `$currentUser` and other properties. Name a promoted constructor property differently, or call the accessor such as `$this->entityTypeManager()`.

## Settings form with configuration

`config/install/sw_base.settings.yml`:

```yaml
items_per_page: 10
```

`config/schema/sw_base.schema.yml`:

```yaml
sw_base.settings:
  type: config_object
  label: 'star wars Base settings'
  mapping:
    items_per_page:
      type: integer
      label: 'Items per page'
```

`src/Form/SettingsForm.php`:

```php
<?php

declare(strict_types=1);

namespace Drupal\sw_base\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configures the sw_base module.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'sw_base_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['sw_base.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['items_per_page'] = [
      '#type' => 'number',
      '#title' => $this->t('Items per page'),
      '#min' => 1,
      '#config_target' => 'sw_base.settings:items_per_page',
    ];

    return parent::buildForm($form, $form_state);
  }

}
```

`ConfigFormBase` loads and saves every element that has a `#config_target`, so the form needs no `submitForm()`. Add the route with `_form: '\Drupal\sw_base\Form\SettingsForm'` and `_permission: 'administer site configuration'`. A menu link in `sw_base.links.menu.yml` with `parent: system.admin_config_system` lists it under Configuration > System.

After the form saves a value on the local site, export `sw_base.settings` with the `manage-config` skill.

## Install, update and post-update hooks

`sw_base.install`:

```php
<?php

/**
 * @file
 * Install, update and uninstall functions for the sw_base module.
 */

declare(strict_types=1);

/**
 * Implements hook_install().
 */
function sw_base_install(): void {
  // Work that a fresh install needs, because update hooks never run on it.
}

/**
 * Adds the 'sw_base_log' table.
 */
function sw_base_update_11001(): string {
  $schema = \Drupal::database()->schema();

  if (!$schema->tableExists('sw_base_log')) {
    $schema->createTable('sw_base_log', [
      'fields' => [
        'id' => ['type' => 'serial', 'unsigned' => TRUE, 'not null' => TRUE],
        'message' => ['type' => 'varchar', 'length' => 255, 'not null' => TRUE, 'default' => ''],
      ],
      'primary key' => ['id'],
    ]);
  }

  return 'Added the sw_base_log table.';
}
```

The same table also goes in `hook_schema()`, so a fresh install creates it.

`sw_base.post_update.php`:

```php
<?php

/**
 * @file
 * Post update functions for the sw_base module.
 */

declare(strict_types=1);

/**
 * Sets the default number of items per page.
 */
function sw_base_post_update_items_per_page(): string {
  \Drupal::configFactory()->getEditable('sw_base.settings')->set('items_per_page', 10)->save();

  return 'Set the default number of items per page.';
}
```

Export `sw_base.settings` after running it, or the configuration import that follows reverts the value.

## Deploy hooks

`sw_base.deploy.php`, run by `drush deploy:hook`:

```php
<?php

/**
 * @file
 * Deploy functions called from drush deploy:hook.
 *
 * @see https://www.drush.org/latest/deploycommand/
 */

declare(strict_types=1);

use Drupal\Core\Site\Settings;

/**
 * Creates the About us page outside production.
 */
function sw_base_deploy_create_about_page(): string {
  if (Settings::get('environment') === ENVIRONMENT_PROD) {
    return 'Skipped in production.';
  }

  $storage = \Drupal::entityTypeManager()->getStorage('node');

  if ($storage->loadByProperties(['type' => 'page', 'title' => 'About us'])) {
    return 'The About us page already exists.';
  }

  $storage->create(['type' => 'page', 'title' => 'About us', 'status' => 1])->save();

  return 'Created the About us page.';
}

/**
 * Publishes unpublished pages in passes of 50.
 */
function sw_base_deploy_publish_pages(array &$sandbox): string {
  $storage = \Drupal::entityTypeManager()->getStorage('node');

  if (!isset($sandbox['ids'])) {
    $sandbox['ids'] = array_values($storage->getQuery()->accessCheck(FALSE)->condition('type', 'page')->condition('status', 0)->execute());
    $sandbox['total'] = count($sandbox['ids']);
  }

  foreach ($storage->loadMultiple(array_splice($sandbox['ids'], 0, 50)) as $node) {
    $node->setPublished()->save();
  }

  $sandbox['#finished'] = $sandbox['total'] > 0 ? 1 - count($sandbox['ids']) / $sandbox['total'] : 1;

  return sprintf('Published %d pages.', $sandbox['total']);
}
```

Check the result with `ahoy drush deploy:hook-status` before and after `ahoy drush deploy:hook`.
