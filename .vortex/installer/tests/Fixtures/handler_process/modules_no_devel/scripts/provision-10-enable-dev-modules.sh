@@ -38,8 +38,4 @@
 drush pm:install sdc_devel || true
 pass "Installed Single Directory Component development tools."
 
-task "Installing Devel module."
-drush pm:install devel || true
-pass "Installed Devel module."
-
 info "Finished development modules operations."
