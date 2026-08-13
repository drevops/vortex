@@ -81,10 +81,6 @@
 drush pm:install sdc_devel || true
 pass "Installed Single Directory Component development tools."
 
-task "Installing Devel module."
-drush pm:install devel || true
-pass "Installed Devel module."
-
 generate_content
 
 info "Finished development modules operations."
