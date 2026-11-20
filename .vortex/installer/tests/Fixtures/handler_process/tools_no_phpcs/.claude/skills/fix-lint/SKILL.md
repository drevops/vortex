@@ -21,8 +21,6 @@
 
 `ahoy lint` runs the check commands below in order, and `ahoy lint-fix` runs the fix commands.
 
-- PHPCS runs first in `ahoy lint-be`. Its fixer, PHPCBF, runs last in `ahoy lint-be-fix`.
-
 - PHPStan runs in `ahoy lint-be`. It has no fixer.
 
 - Rector runs last in `ahoy lint-be`, as a dry run. `ahoy lint-be-fix` runs it first and applies its changes.
