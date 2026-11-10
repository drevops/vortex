@@ -1,17 +1,17 @@
 # Code patterns for a custom module
 
-Each example uses `sw_base` as the module name. Replace it, the `Drupal\sw_base` namespace and the `SwBase` class prefix with the module you're working on. Add the `@file` docblocks, the class and method docblocks, and `declare(strict_types=1);` that the shipped modules carry, so `ahoy lint` passes.
+Each example uses `the_force_base` as the module name. Replace it, the `Drupal\the_force_base` namespace and the `TheForceBase` class prefix with the module you're working on. Add the `@file` docblocks, the class and method docblocks, and `declare(strict_types=1);` that the shipped modules carry, so `ahoy lint` passes.
 
 ## Info file
 
-`web/modules/custom/sw_base/sw_base.info.yml`:
+`web/modules/custom/the_force_base/the_force_base.info.yml`:
 
 ```yaml
-name: star wars Base
+name: New hope Base
 type: module
-description: Base feature for star wars site.
+description: Base feature for New hope site.
 core_version_requirement: ^11
-package: star_wars
+package: the_new_hope
 dependencies:
   - drupal:node
 ```
@@ -18,18 +18,18 @@
 
 ## Service with autowiring
 
-`sw_base.services.yml`:
+`the_force_base.services.yml`:
 
 ```yaml
 services:
-  logger.channel.sw_base:
+  logger.channel.the_force_base:
     parent: logger.channel_base
-    arguments: ['sw_base']
+    arguments: ['the_force_base']
 
-  sw_base.recent_content:
-    class: Drupal\sw_base\RecentContent
+  the_force_base.recent_content:
+    class: Drupal\the_force_base\RecentContent
     autowire: true
-  Drupal\sw_base\RecentContent: '@sw_base.recent_content'
+  Drupal\the_force_base\RecentContent: '@the_force_base.recent_content'
 ```
 
 `src/RecentContent.php`:
@@ -39,7 +39,7 @@
 
 declare(strict_types=1);
 
-namespace Drupal\sw_base;
+namespace Drupal\the_force_base;
 
 use Drupal\Core\Entity\EntityTypeManagerInterface;
 use Psr\Log\LoggerInterface;
@@ -52,7 +52,7 @@
 
   public function __construct(
     protected EntityTypeManagerInterface $entityTypeManager,
-    #[Autowire(service: 'logger.channel.sw_base')]
+    #[Autowire(service: 'logger.channel.the_force_base')]
     protected LoggerInterface $logger,
   ) {}
 
@@ -82,7 +82,7 @@
 
 ## Hook class
 
-`src/Hook/NodeHooks.php`. Core registers the class as an autowired service, so it needs no entry in `sw_base.services.yml`. Rebuild caches with `ahoy drush cr` after adding it.
+`src/Hook/NodeHooks.php`. Core registers the class as an autowired service, so it needs no entry in `the_force_base.services.yml`. Rebuild caches with `ahoy drush cr` after adding it.
 
 ```php
 <?php
@@ -89,7 +89,7 @@
 
 declare(strict_types=1);
 
-namespace Drupal\sw_base\Hook;
+namespace Drupal\the_force_base\Hook;
 
 use Drupal\Core\Hook\Attribute\Hook;
 use Drupal\Core\Session\AccountProxyInterface;
@@ -96,7 +96,7 @@
 use Drupal\node\NodeInterface;
 
 /**
- * Node hook implementations for the sw_base module.
+ * Node hook implementations for the the_force_base module.
  */
 class NodeHooks {
 
@@ -120,7 +120,7 @@
   #[Hook('theme')]
   public function theme(): array {
     return [
-      'sw_base_recent_content' => [
+      'the_force_base_recent_content' => [
         'variables' => ['items' => []],
       ],
     ];
@@ -129,7 +129,7 @@
 }
 ```
 
-A theme hook named `sw_base_recent_content` renders `templates/sw-base-recent-content.html.twig` from the module.
+A theme hook named `the_force_base_recent_content` renders `templates/the-force-base-recent-content.html.twig` from the module.
 
 ## Block plugin with an attribute
 
@@ -140,7 +140,7 @@
 
 declare(strict_types=1);
 
-namespace Drupal\sw_base\Plugin\Block;
+namespace Drupal\the_force_base\Plugin\Block;
 
 use Drupal\Core\Block\Attribute\Block;
 use Drupal\Core\Block\BlockBase;
@@ -147,15 +147,15 @@
 use Drupal\Core\Cache\CacheableMetadata;
 use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
 use Drupal\Core\StringTranslation\TranslatableMarkup;
-use Drupal\sw_base\RecentContent;
+use Drupal\the_force_base\RecentContent;
 
 /**
  * Lists the most recent content.
  */
 #[Block(
-  id: 'sw_base_recent_content',
+  id: 'the_force_base_recent_content',
   admin_label: new TranslatableMarkup('Recent content'),
-  category: new TranslatableMarkup('star wars'),
+  category: new TranslatableMarkup('New hope'),
 )]
 class RecentContentBlock extends BlockBase implements ContainerFactoryPluginInterface {
 
@@ -172,7 +172,7 @@
    * {@inheritdoc}
    */
   public function build(): array {
-    $build = ['#theme' => 'sw_base_recent_content', '#items' => []];
+    $build = ['#theme' => 'the_force_base_recent_content', '#items' => []];
     $cache = new CacheableMetadata();
     $cache->addCacheTags(['node_list']);
 
@@ -193,22 +193,22 @@
 
 ## Route, permission and controller
 
-`sw_base.routing.yml`:
+`the_force_base.routing.yml`:
 
 ```yaml
-sw_base.recent_content:
+the_force_base.recent_content:
   path: '/admin/reports/recent-content'
   defaults:
-    _controller: '\Drupal\sw_base\Controller\RecentContentController::build'
+    _controller: '\Drupal\the_force_base\Controller\RecentContentController::build'
     _title: 'Recent content'
   requirements:
-    _permission: 'view sw_base recent content'
+    _permission: 'view the_force_base recent content'
 ```
 
-`sw_base.permissions.yml`:
+`the_force_base.permissions.yml`:
 
 ```yaml
-'view sw_base recent content':
+'view the_force_base recent content':
   title: 'View the recent content report'
 ```
 
@@ -219,10 +219,10 @@
 
 declare(strict_types=1);
 
-namespace Drupal\sw_base\Controller;
+namespace Drupal\the_force_base\Controller;
 
 use Drupal\Core\Controller\ControllerBase;
-use Drupal\sw_base\RecentContent;
+use Drupal\the_force_base\RecentContent;
 
 /**
  * Builds the recent content report.
@@ -258,18 +258,18 @@
 
 ## Settings form with configuration
 
-`config/install/sw_base.settings.yml`:
+`config/install/the_force_base.settings.yml`:
 
 ```yaml
 items_per_page: 10
 ```
 
-`config/schema/sw_base.schema.yml`:
+`config/schema/the_force_base.schema.yml`:
 
 ```yaml
-sw_base.settings:
+the_force_base.settings:
   type: config_object
-  label: 'star wars Base settings'
+  label: 'New hope Base settings'
   mapping:
     items_per_page:
       type: integer
@@ -283,13 +283,13 @@
 
 declare(strict_types=1);
 
-namespace Drupal\sw_base\Form;
+namespace Drupal\the_force_base\Form;
 
 use Drupal\Core\Form\ConfigFormBase;
 use Drupal\Core\Form\FormStateInterface;
 
 /**
- * Configures the sw_base module.
+ * Configures the the_force_base module.
  */
 class SettingsForm extends ConfigFormBase {
 
@@ -297,7 +297,7 @@
    * {@inheritdoc}
    */
   public function getFormId(): string {
-    return 'sw_base_settings';
+    return 'the_force_base_settings';
   }
 
   /**
@@ -304,7 +304,7 @@
    * {@inheritdoc}
    */
   protected function getEditableConfigNames(): array {
-    return ['sw_base.settings'];
+    return ['the_force_base.settings'];
   }
 
   /**
@@ -315,7 +315,7 @@
       '#type' => 'number',
       '#title' => $this->t('Items per page'),
       '#min' => 1,
-      '#config_target' => 'sw_base.settings:items_per_page',
+      '#config_target' => 'the_force_base.settings:items_per_page',
     ];
 
     return parent::buildForm($form, $form_state);
@@ -324,13 +324,13 @@
 }
 ```
 
-`ConfigFormBase` loads and saves every element that has a `#config_target`, so the form needs no `submitForm()`. Add the route with `_form: '\Drupal\sw_base\Form\SettingsForm'` and `_permission: 'administer site configuration'`. A menu link in `sw_base.links.menu.yml` with `parent: system.admin_config_system` lists it under Configuration > System.
+`ConfigFormBase` loads and saves every element that has a `#config_target`, so the form needs no `submitForm()`. Add the route with `_form: '\Drupal\the_force_base\Form\SettingsForm'` and `_permission: 'administer site configuration'`. A menu link in `the_force_base.links.menu.yml` with `parent: system.admin_config_system` lists it under Configuration > System.
 
-After the form saves a value on the local site, export `sw_base.settings` with the `manage-config` skill.
+After the form saves a value on the local site, export `the_force_base.settings` with the `manage-config` skill.
 
 ## Install, update and post-update hooks
 
-`sw_base.install`:
+`the_force_base.install`:
 
 ```php
 <?php
@@ -337,7 +337,7 @@
 
 /**
  * @file
- * Install, update and uninstall functions for the sw_base module.
+ * Install, update and uninstall functions for the the_force_base module.
  */
 
 declare(strict_types=1);
@@ -345,18 +345,18 @@
 /**
  * Implements hook_install().
  */
-function sw_base_install(): void {
+function the_force_base_install(): void {
   // Work that a fresh install needs, because update hooks never run on it.
 }
 
 /**
- * Adds the 'sw_base_log' table.
+ * Adds the 'the_force_base_log' table.
  */
-function sw_base_update_11001(): string {
+function the_force_base_update_11001(): string {
   $schema = \Drupal::database()->schema();
 
-  if (!$schema->tableExists('sw_base_log')) {
-    $schema->createTable('sw_base_log', [
+  if (!$schema->tableExists('the_force_base_log')) {
+    $schema->createTable('the_force_base_log', [
       'fields' => [
         'id' => ['type' => 'serial', 'unsigned' => TRUE, 'not null' => TRUE],
         'message' => ['type' => 'varchar', 'length' => 255, 'not null' => TRUE, 'default' => ''],
@@ -365,13 +365,13 @@
     ]);
   }
 
-  return 'Added the sw_base_log table.';
+  return 'Added the the_force_base_log table.';
 }
 ```
 
 The same table also goes in `hook_schema()`, so a fresh install creates it.
 
-`sw_base.post_update.php`:
+`the_force_base.post_update.php`:
 
 ```php
 <?php
@@ -378,7 +378,7 @@
 
 /**
  * @file
- * Post update functions for the sw_base module.
+ * Post update functions for the the_force_base module.
  */
 
 declare(strict_types=1);
@@ -386,18 +386,18 @@
 /**
  * Sets the default number of items per page.
  */
-function sw_base_post_update_items_per_page(): string {
-  \Drupal::configFactory()->getEditable('sw_base.settings')->set('items_per_page', 10)->save();
+function the_force_base_post_update_items_per_page(): string {
+  \Drupal::configFactory()->getEditable('the_force_base.settings')->set('items_per_page', 10)->save();
 
   return 'Set the default number of items per page.';
 }
 ```
 
-Export `sw_base.settings` after running it, or the configuration import that follows reverts the value.
+Export `the_force_base.settings` after running it, or the configuration import that follows reverts the value.
 
 ## Deploy hooks
 
-`sw_base.deploy.php`, run by `drush deploy:hook`:
+`the_force_base.deploy.php`, run by `drush deploy:hook`:
 
 ```php
 <?php
@@ -416,7 +416,7 @@
 /**
  * Creates the About us page outside production.
  */
-function sw_base_deploy_create_about_page(): string {
+function the_force_base_deploy_create_about_page(): string {
   if (Settings::get('environment') === ENVIRONMENT_PROD) {
     return 'Skipped in production.';
   }
@@ -435,7 +435,7 @@
 /**
  * Publishes unpublished pages in passes of 50.
  */
-function sw_base_deploy_publish_pages(array &$sandbox): string {
+function the_force_base_deploy_publish_pages(array &$sandbox): string {
   $storage = \Drupal::entityTypeManager()->getStorage('node');
 
   if (!isset($sandbox['ids'])) {
