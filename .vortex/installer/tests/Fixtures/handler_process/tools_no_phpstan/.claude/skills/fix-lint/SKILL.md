@@ -23,8 +23,6 @@
 
 - PHPCS runs first in `ahoy lint-be`. Its fixer, PHPCBF, runs last in `ahoy lint-be-fix`.
 
-- PHPStan runs in `ahoy lint-be`. It has no fixer.
-
 - Rector runs last in `ahoy lint-be`, as a dry run. `ahoy lint-be-fix` runs it first and applies its changes.
 
 - Twig CS Fixer runs first in `ahoy lint-fe`, and with `--fix` in `ahoy lint-fe-fix`.
