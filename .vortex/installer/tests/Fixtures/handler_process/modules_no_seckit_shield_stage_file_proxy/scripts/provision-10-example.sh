@@ -59,7 +59,7 @@
   pass "Set up the administration navigation."
 
   task "Installing contrib modules."
-  drush pm:install coffee config_split config_update media environment_indicator navigation_extra_tools pathauto redirect reroute_email robotstxt shield stage_file_proxy xmlsitemap
+  drush pm:install coffee config_split config_update media environment_indicator navigation_extra_tools pathauto redirect reroute_email robotstxt xmlsitemap
   pass "Installed contrib modules."
 
   task "Installing Redis module."
