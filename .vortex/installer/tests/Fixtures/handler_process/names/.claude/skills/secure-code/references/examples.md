@@ -1,23 +1,23 @@
 # Secure code examples
 
-Each example uses the `sw_base` custom module. Replace the names with the module the change belongs to. Match the surrounding module's style for hooks, dependency injection and file layout.
+Each example uses the `the_force_base` custom module. Replace the names with the module the change belongs to. Match the surrounding module's style for hooks, dependency injection and file layout.
 
 ## Route requirements
 
 ```yaml
-# sw_base.routing.yml
-sw_base.report:
-  path: '/admin/reports/sw-base'
+# the_force_base.routing.yml
+the_force_base.report:
+  path: '/admin/reports/the-force-base'
   defaults:
-    _controller: '\Drupal\sw_base\Controller\ReportController::view'
+    _controller: '\Drupal\the_force_base\Controller\ReportController::view'
     _title: 'Site report'
   requirements:
-    _permission: 'view sw_base reports'
+    _permission: 'view the_force_base reports'
 
-sw_base.article_review:
+the_force_base.article_review:
   path: '/node/{node}/review'
   defaults:
-    _form: '\Drupal\sw_base\Form\ReviewForm'
+    _form: '\Drupal\the_force_base\Form\ReviewForm'
   requirements:
     _entity_access: 'node.update'
     node: \d+
@@ -26,21 +26,21 @@
       node:
         type: entity:node
 
-sw_base.subscription_cancel:
+the_force_base.subscription_cancel:
   path: '/subscription/{subscription_id}/cancel'
   defaults:
-    _controller: '\Drupal\sw_base\Controller\SubscriptionController::cancel'
+    _controller: '\Drupal\the_force_base\Controller\SubscriptionController::cancel'
   requirements:
-    _custom_access: '\Drupal\sw_base\Access\SubscriptionAccess::access'
+    _custom_access: '\Drupal\the_force_base\Access\SubscriptionAccess::access'
     _csrf_token: 'TRUE'
     subscription_id: \d+
 ```
 
 ```yaml
-# sw_base.permissions.yml
-view sw_base reports:
+# the_force_base.permissions.yml
+view the_force_base reports:
   title: 'View site reports'
-administer sw_base:
+administer the_force_base:
   title: 'Administer site integrations'
   restrict access: true
 ```
@@ -48,7 +48,7 @@
 ## Custom access with cacheability
 
 ```php
-namespace Drupal\sw_base\Access;
+namespace Drupal\the_force_base\Access;
 
 use Drupal\Core\Access\AccessResult;
 use Drupal\Core\Access\AccessResultInterface;
@@ -160,7 +160,7 @@
 // Drupal.behaviors with once(), writing text, not HTML.
 Drupal.behaviors.ysBaseCounter = {
   attach(context, settings) {
-    once('sw-base-counter', '.js-sw-base-counter', context).forEach((element) => {
+    once('the-force-base-counter', '.js-the-force-base-counter', context).forEach((element) => {
       element.textContent = settings.ysBase.counterLabel;
     });
   },
@@ -171,12 +171,12 @@
 
 ```php
 // A link to a route with '_csrf_token: TRUE' gets its token added.
-$url = Url::fromRoute('sw_base.subscription_cancel', ['subscription_id' => $id]);
+$url = Url::fromRoute('the_force_base.subscription_cancel', ['subscription_id' => $id]);
 
 // A token for a JavaScript endpoint, validated in the controller.
-$build['#attached']['drupalSettings']['ysBase']['token'] = $this->csrfToken->get('sw_base/vote');
+$build['#attached']['drupalSettings']['ysBase']['token'] = $this->csrfToken->get('the_force_base/vote');
 
-if (!$this->csrfToken->validate((string) $request->headers->get('X-Ys-Base-Token'), 'sw_base/vote')) {
+if (!$this->csrfToken->validate((string) $request->headers->get('X-Ys-Base-Token'), 'the_force_base/vote')) {
   throw new AccessDeniedHttpException();
 }
 ```
@@ -187,7 +187,7 @@
 $form['document'] = [
   '#type' => 'managed_file',
   '#title' => $this->t('Document'),
-  '#upload_location' => 'private://sw_base/documents',
+  '#upload_location' => 'private://the_force_base/documents',
   '#upload_validators' => [
     'FileExtension' => ['extensions' => 'pdf docx'],
     'FileSizeLimit' => ['fileLimit' => 10 * 1024 * 1024],
@@ -200,12 +200,12 @@
 /**
  * Implements hook_file_download().
  */
-function sw_base_file_download(string $uri): array|int|null {
-  if (!str_starts_with($uri, 'private://sw_base/reports/')) {
+function the_force_base_file_download(string $uri): array|int|null {
+  if (!str_starts_with($uri, 'private://the_force_base/reports/')) {
     return NULL;
   }
 
-  if (!\Drupal::currentUser()->hasPermission('view sw_base reports')) {
+  if (!\Drupal::currentUser()->hasPermission('view the_force_base reports')) {
     return -1;
   }
 
@@ -216,18 +216,18 @@
 ## Secrets
 
 ```php
-// web/sites/default/includes/modules/settings.sw_base.php
+// web/sites/default/includes/modules/settings.the_force_base.php
 declare(strict_types=1);
 
-$sw_base_api_key = getenv('DRUPAL_YS_BASE_API_KEY');
-if (!empty($sw_base_api_key)) {
-  $settings['sw_base_api_key'] = $sw_base_api_key;
+$the_force_base_api_key = getenv('DRUPAL_YS_BASE_API_KEY');
+if (!empty($the_force_base_api_key)) {
+  $settings['the_force_base_api_key'] = $the_force_base_api_key;
 }
 ```
 
 ```php
 // A service that receives '@settings' and '@http_client'.
-$api_key = $this->settings->get('sw_base_api_key');
+$api_key = $this->settings->get('the_force_base_api_key');
 if (empty($api_key)) {
   $this->logger->warning('The partner API key is not configured.');
 
@@ -263,7 +263,7 @@
 ## Logging
 
 ```php
-$this->logger = $logger_factory->get('sw_base');
+$this->logger = $logger_factory->get('the_force_base');
 
 // Placeholders, and an ID instead of personal data.
 $this->logger->error('Order @order_id failed to sync: @reason', [
