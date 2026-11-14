@@ -1,6 +1,6 @@
 # Performance fixes
 
-Patterns for the causes the skill finds. Each example uses the `sw_base` custom module; replace the names with the module that owns the code. Measure before and after each fix with the request probe in [probes.md](probes.md).
+Patterns for the causes the skill finds. Each example uses the `the_force_base` custom module; replace the names with the module that owns the code. Measure before and after each fix with the request probe in [probes.md](probes.md).
 
 ## How cacheability bubbles
 
@@ -19,7 +19,7 @@
 ```php
 // Output built from an entity and a configuration object.
 $build = [
-  '#theme' => 'sw_base_summary',
+  '#theme' => 'the_force_base_summary',
   '#title' => $node->label(),
   '#site_name' => $site_config->get('name'),
 ];
@@ -62,21 +62,21 @@
 ```php
 // Where the page is built.
 $build['greeting'] = [
-  '#lazy_builder' => ['sw_base.greeting_builder:build', [(int) $node->id()]],
+  '#lazy_builder' => ['the_force_base.greeting_builder:build', [(int) $node->id()]],
   '#create_placeholder' => TRUE,
 ];
 ```
 
 ```yaml
-# sw_base.services.yml
+# the_force_base.services.yml
 services:
-  sw_base.greeting_builder:
-    class: Drupal\sw_base\GreetingBuilder
+  the_force_base.greeting_builder:
+    class: Drupal\the_force_base\GreetingBuilder
     arguments: ['@current_user', '@entity_type.manager']
 ```
 
 ```php
-namespace Drupal\sw_base;
+namespace Drupal\the_force_base;
 
 use Drupal\Core\Entity\EntityTypeManagerInterface;
 use Drupal\Core\Security\TrustedCallbackInterface;
@@ -117,7 +117,7 @@
 
 ```php
 // After the code changes data that cached output depends on.
-$this->cacheTagsInvalidator->invalidateTags(['sw_base:partner_feed']);
+$this->cacheTagsInvalidator->invalidateTags(['the_force_base:partner_feed']);
 ```
 
 - Entities and configuration objects invalidate their own tags when saved, so output that declares them as dependencies needs no extra code.
@@ -128,13 +128,13 @@
 
 ```php
 // A service that receives '@cache.default'.
-$cid = 'sw_base:partner_feed:' . $langcode;
+$cid = 'the_force_base:partner_feed:' . $langcode;
 if ($cached = $this->cache->get($cid)) {
   return $cached->data;
 }
 
 $items = $this->fetchItems($langcode);
-$this->cache->set($cid, $items, Cache::PERMANENT, ['sw_base:partner_feed']);
+$this->cache->set($cid, $items, Cache::PERMANENT, ['the_force_base:partner_feed']);
 
 return $items;
 ```
@@ -187,15 +187,15 @@
 
 ```php
 /**
- * Adds an index on the 'created' column of the 'sw_base_log' table.
+ * Adds an index on the 'created' column of the 'the_force_base_log' table.
  */
-function sw_base_update_11001(): void {
+function the_force_base_update_11001(): void {
   $schema = \Drupal::database()->schema();
-  if ($schema->indexExists('sw_base_log', 'sw_base_log__created')) {
+  if ($schema->indexExists('the_force_base_log', 'the_force_base_log__created')) {
     return;
   }
 
-  $schema->addIndex('sw_base_log', 'sw_base_log__created', ['created'], [
+  $schema->addIndex('the_force_base_log', 'the_force_base_log__created', ['created'], [
     'fields' => ['created' => ['type' => 'int', 'not null' => TRUE]],
   ]);
 }
