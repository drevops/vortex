@@ -30,6 +30,8 @@
 - Use lowercase letters, digits and underscores, at most 50 characters. Drupal refuses to install a longer name.
 - Every custom module shares 1 prefix, `ys` in the template, which the installer replaced with the project's own. Read it from the start of an existing module directory name in `web/modules/custom/`. When none exists, ask the developer.
 
+- `ys_migrate` keeps the `ys` prefix on every project, so never take the prefix from it.
+
 ## Write the info file
 
 Copy the keys from a shipped `.info.yml` file. `core_version_requirement: ^11` matches the Drupal 11 core in `composer.json`, and `package: star_wars` groups the custom modules on the Extend page.
