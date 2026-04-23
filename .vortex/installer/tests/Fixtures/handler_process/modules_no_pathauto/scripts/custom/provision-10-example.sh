@@ -40,7 +40,7 @@
   pass "Set site name."
 
   task "Installing contrib modules."
-  drush pm:install admin_toolbar coffee config_split config_update media environment_indicator pathauto redirect reroute_email robotstxt shield stage_file_proxy xmlsitemap
+  drush pm:install admin_toolbar coffee config_split config_update media environment_indicator redirect reroute_email robotstxt shield stage_file_proxy xmlsitemap
   pass "Installed contrib modules."
 
   task "Installing Redis module."
