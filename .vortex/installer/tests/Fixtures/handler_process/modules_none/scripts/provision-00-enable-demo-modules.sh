@@ -61,10 +61,6 @@
 fi
 pass "Set up the administration navigation."
 
-task "Installing contrib modules."
-drush pm:install coffee config_split config_update media environment_indicator navigation_extra_tools pathauto redirect reroute_email robotstxt shield stage_file_proxy xmlsitemap
-pass "Installed contrib modules."
-
 task "Installing Redis module."
 drush pm:install redis || true
 pass "Installed Redis module."
