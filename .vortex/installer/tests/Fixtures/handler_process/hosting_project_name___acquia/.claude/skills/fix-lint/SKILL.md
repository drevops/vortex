@@ -11,7 +11,7 @@
 
 ## Rules
 
-- Never edit files under `vendor/`, `web/core/` or the `contrib` directories. The linters don't check them, and Composer replaces them.
+- Never edit files under `vendor/`, `docroot/core/` or the `contrib` directories. The linters don't check them, and Composer replaces them.
 - Run `git status --short` before running a fixer, so the fixer's changes can be told apart from the developer's own.
 - Review every change a fixer makes with `git diff` before reporting.
 - Never set a `VORTEX_CI_*_IGNORE_FAILURE` CI variable to get a passing build. Those variables are a one-off bypass that the developer decides on.
