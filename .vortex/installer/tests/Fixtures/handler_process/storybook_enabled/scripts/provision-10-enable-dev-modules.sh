@@ -41,6 +41,21 @@
 drush pm:install sdc_devel
 pass "Installed Single Directory Component development tools."
 
+task "Installing Storybook module."
+drush pm:install storybook
+pass "Installed Storybook module."
+
+# Storybook renders every story through Drupal, so recompiling templates on
+# each request shows component edits without a cache rebuild. Only the
+# development settings change: 'drush theme:dev' also writes aggregation
+# settings that would appear in exported configuration.
+if [ "${environment}" = "local" ]; then
+  task "Enabling Twig development mode."
+  drush php:eval "\Drupal::keyValue('development_settings')->setMultiple(['twig_debug' => TRUE, 'twig_cache_disable' => TRUE, 'disable_rendered_output_cache_bins' => TRUE]);"
+  drush cache:rebuild
+  pass "Enabled Twig development mode."
+fi
+
 task "Installing Devel module."
 drush pm:install devel
 pass "Installed Devel module."
