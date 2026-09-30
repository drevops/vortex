@@ -41,6 +41,10 @@
 drush pm:install sdc_devel
 pass "Installed Single Directory Component development tools."
 
+task "Installing Storybook module."
+drush pm:install storybook
+pass "Installed Storybook module."
+
 task "Installing Devel module."
 drush pm:install devel
 pass "Installed Devel module."
