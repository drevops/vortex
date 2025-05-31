@@ -16,6 +16,6 @@
  */
 function sw_base_deploy_install_theme(): void {
   \Drupal::service('theme_installer')->install(['olivero']);
-  \Drupal::service('theme_installer')->install(['star_wars']);
-  \Drupal::service('config.factory')->getEditable('system.theme')->set('default', 'star_wars')->save();
+  \Drupal::service('theme_installer')->install(['light_saber']);
+  \Drupal::service('config.factory')->getEditable('system.theme')->set('default', 'light_saber')->save();
 }
