@@ -18,7 +18,7 @@
 
 Recognize it:
 
-- The item's name starts with a module listed in `$settings['config_exclude_modules']`. Check with `grep -rn config_exclude_modules web/sites/default/includes/modules`. The development modules are listed there, so provisioning installs them in some environments and the configuration never carries them.
+- The item's name starts with a module listed in `$settings['config_exclude_modules']`. Check with `grep -rn config_exclude_modules docroot/sites/default/includes/modules`. The development modules are listed there, so provisioning installs them in some environments and the configuration never carries them.
 - `find config -name "*<name>.yml"` finds the file in a directory other than `config/default`, such as `config/stage`, or as a patch file whose name ends in `.<name>.yml`.
 
 Recommend Skip. The difference exists on purpose, and a file in `config/default` would push it to every environment. When the developer still wants to change the item, handle it with the `manage-config` skill, outside this walk.
@@ -70,7 +70,7 @@
 
 Recognize it: the values that differ are hostnames, URLs of other services, API keys, email addresses or other settings that must differ per environment.
 
-Recommend Skip. Neither side should hold these values for every environment. Tell the developer to move them into a per-module override file, `web/sites/default/includes/modules/settings.<module>.php`, with a condition on the environment type. See [Per-module overrides](https://www.vortextemplate.com/docs/development/settings#per-module-overrides). Never copy a secret from the database into `config/`.
+Recommend Skip. Neither side should hold these values for every environment. Tell the developer to move them into a per-module override file, `docroot/sites/default/includes/modules/settings.<module>.php`, with a condition on the environment type. See [Per-module overrides](https://www.vortextemplate.com/docs/development/settings#per-module-overrides). Never copy a secret from the database into `config/`.
 
 ## Rule 8: edits made on the site
 
