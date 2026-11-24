@@ -20,37 +20,3 @@
   \Drupal::service('theme_installer')->install(['star_wars']);
   \Drupal::service('config.factory')->getEditable('system.theme')->set('default', 'star_wars')->save();
 }
-
-/**
- * Replaces the sample response headers with security headers.
- *
- * The module installs its sample headers on enable. The empty
- * 'Content-Security-Policy' sample strips the policy another module sends, and
- * 'Access-Control-Allow-Origin: *' lets any origin read every response.
- *
- * @codeCoverageIgnore
- */
-function sw_base_deploy_configure_response_headers(): string {
-  if (!\Drupal::moduleHandler()->moduleExists('http_response_headers')) {
-    return 'Skipped configuring response headers: the module is not installed.';
-  }
-
-  $storage = \Drupal::entityTypeManager()->getStorage('response_header');
-
-  $storage->delete($storage->loadMultiple(['access_control_allow_origin', 'content_security_policy', 'public_key_pins', 'x_xss_protection']));
-
-  $headers = [
-    'permissions_policy' => ['name' => 'Permissions-Policy', 'value' => 'camera=(), microphone=(), geolocation=()'],
-    'cross_origin_opener_policy' => ['name' => 'Cross-Origin-Opener-Policy', 'value' => 'same-origin'],
-    'cross_origin_resource_policy' => ['name' => 'Cross-Origin-Resource-Policy', 'value' => 'same-origin'],
-    'x_permitted_cross_domain_policies' => ['name' => 'X-Permitted-Cross-Domain-Policies', 'value' => 'none'],
-  ];
-
-  foreach ($headers as $id => $header) {
-    if ($storage->load($id) === NULL) {
-      $storage->create(['id' => $id, 'label' => $header['name']] + $header)->save();
-    }
-  }
-
-  return 'Configured response headers.';
-}
