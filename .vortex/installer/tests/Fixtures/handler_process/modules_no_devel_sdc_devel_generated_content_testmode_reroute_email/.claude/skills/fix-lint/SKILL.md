@@ -43,8 +43,6 @@
 
 - Hadolint runs only in the CI `lint` job. The project doesn't install it.
 
-`ahoy lint-sdc` validates the custom theme's components against a provisioned site. `ahoy lint` doesn't run it.
-
 Every Ahoy command runs its steps under `bash -e`, so it stops at the first tool that fails, and the tools after it report nothing until it passes. A fixer that leaves problems it can't fix also exits with a non-zero code, which stops `ahoy lint-fix` before the remaining fixers run. Run `ahoy lint-fix` again after fixing those problems by hand.
 
 The CI `lint` job also runs `composer validate --strict` and `composer normalize --dry-run`, which `ahoy lint` doesn't. Run them as `ahoy composer validate --strict` and `ahoy composer normalize --dry-run`, and fix the formatting of `composer.json` with `ahoy composer normalize`.
