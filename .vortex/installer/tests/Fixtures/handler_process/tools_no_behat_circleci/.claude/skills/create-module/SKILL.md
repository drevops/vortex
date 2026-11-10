@@ -148,8 +148,6 @@
 
 - Put JavaScript tests in the module's `js/` directory as `<name>.test.js`. `jest.config.js` collects the `js/` directory of every custom module. Run them with `ahoy test-js`.
 
-- Put end-to-end scenarios in `tests/behat/features/` at the project root, not in the module.
-
 ## Before you finish
 
 - Caches are rebuilt with `ahoy drush cr` after the last change to services, hooks, routes or plugins.
