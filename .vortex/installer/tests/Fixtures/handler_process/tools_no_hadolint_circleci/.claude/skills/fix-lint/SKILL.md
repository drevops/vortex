@@ -41,8 +41,6 @@
 
 - DCLint runs only in the CI `lint` job. The project doesn't install it.
 
-- Hadolint runs only in the CI `lint` job. The project doesn't install it.
-
 `ahoy lint-sdc` validates the custom theme's components against a provisioned site. `ahoy lint` doesn't run it.
 
 Every Ahoy command runs its steps under `bash -e`, so it stops at the first tool that fails, and the tools after it report nothing until it passes. A fixer that leaves problems it can't fix also exits with a non-zero code, which stops `ahoy lint-fix` before the remaining fixers run. Run `ahoy lint-fix` again after fixing those problems by hand.
@@ -59,8 +57,6 @@
 6. Review the result: `git diff`
 
 When the change touches `docker-compose.yml`, also run DCLint as its section in [references/tools.md](references/tools.md) describes.
-
-When the change touches a file in `.docker/`, also run Hadolint as its section in [references/tools.md](references/tools.md) describes.
 
 Report to the developer what the fixers changed, what you fixed by hand and every ignore you added, with its reason.
 
