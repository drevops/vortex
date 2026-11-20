@@ -12,7 +12,7 @@
 ## Rules
 
 - Start from a clean tree: `git status --short`. Keep each update in its own commit, apart from feature work.
-- Never edit files under `vendor/`, `web/core/`, `web/libraries/` or the `contrib` directories. Composer replaces them on the next install. Change them with a patch instead.
+- Never edit files under `vendor/`, `docroot/core/`, `docroot/libraries/` or the `contrib` directories. Composer replaces them on the next install. Change them with a patch instead.
 - Update to new minor and patch releases by default. Move a package to a new major version only when the developer asks for it.
 - Leave the `php` constraint and `config.platform.php` in `composer.json` alone. They follow the PHP version of the container images.
 - Drush answers its own confirmation prompts with "yes" when no terminal is attached. Run `updatedb` and the configuration commands against the local site only.
@@ -49,7 +49,7 @@
 
 `pyrech/composer-changelogs` prints a summary of the changed packages after each update. Use it in your report.
 
-After a core update, review the scaffold files that it changed, such as `web/index.php` and `web/sites/default/default.settings.php`, with `git diff`. The project keeps `web/.htaccess` out of scaffolding, so port changes to `.htaccess` from the core release notes by hand.
+After a core update, review the scaffold files that it changed, such as `docroot/index.php` and `docroot/sites/default/default.settings.php`, with `git diff`. The project keeps `docroot/.htaccess` out of scaffolding, so port changes to `.htaccess` from the core release notes by hand.
 
 After editing `composer.json` by hand, run `ahoy composer validate --strict` and `ahoy composer normalize`. The CI `lint` job checks both.
 
