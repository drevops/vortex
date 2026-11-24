@@ -62,28 +62,6 @@
 fi
 pass "Set up the administration navigation."
 
-task "Installing contrib modules."
-drush pm:install coffee config_split config_update media diff entity_usage environment_indicator field_group http_response_headers linkit menu_trail_by_path metatag navigation_extra_tools pathauto redirect rename_admin_paths robotstxt scheduled_transitions schema_metatag shield stage_file_proxy views_bulk_operations xmlsitemap
-pass "Installed contrib modules."
-
-# The module's samples include an empty 'Content-Security-Policy', which strips
-# the policy another module sends, and 'Access-Control-Allow-Origin: *'. A
-# sample is deleted only while it holds its shipped value, so edits are kept.
-task "Configuring HTTP response headers."
-headers_php="\$storage = \Drupal::entityTypeManager()->getStorage('response_header');"
-headers_php+=" \$samples = ['access_control_allow_origin' => '*', 'content_security_policy' => '', 'public_key_pins' => '', 'x_xss_protection' => '1; mode=block'];"
-headers_php+=" foreach (\$samples as \$id => \$sample) { \$header = \$storage->load(\$id); if (\$header && \$header->get('value') === \$sample) { \$header->delete(); } }"
-headers_php+=" \$headers = ['permissions_policy' => ['Permissions-Policy', 'camera=(), microphone=(), geolocation=()'], 'cross_origin_opener_policy' => ['Cross-Origin-Opener-Policy', 'same-origin'],"
-headers_php+=" 'cross_origin_resource_policy' => ['Cross-Origin-Resource-Policy', 'same-origin'], 'x_permitted_cross_domain_policies' => ['X-Permitted-Cross-Domain-Policies', 'none']];"
-headers_php+=" foreach (\$headers as \$id => [\$name, \$value]) { if (!\$storage->load(\$id)) { \$storage->create(['id' => \$id, 'label' => \$name, 'name' => \$name, 'value' => \$value])->save(); } }"
-drush php:eval "${headers_php}"
-pass "Configured HTTP response headers."
-
-task "Setting Gin as the administration theme."
-drush theme:install gin
-drush config:set system.theme admin gin
-pass "Set Gin as the administration theme."
-
 task "Installing Redis module."
 drush pm:install redis || true
 pass "Installed Redis module."
