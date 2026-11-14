@@ -39,8 +39,6 @@
 
 - Gherkin Lint runs in `ahoy lint-tests`. It has no fixer, so `ahoy lint-fix` leaves feature files alone.
 
-- DCLint runs only in the CI `lint` job. The project doesn't install it.
-
 - Hadolint runs only in the CI `lint` job. The project doesn't install it.
 
 `ahoy lint-sdc` validates the custom theme's components against a provisioned site. `ahoy lint` doesn't run it.
@@ -57,8 +55,6 @@
 4. Fix each remaining finding by hand. Read the failing tool's section in [references/tools.md](references/tools.md): it covers the tool's configuration, what its fixer can't fix, how to fix that by hand and how to run the tool alone on 1 file. Re-run the tool on the changed file until it passes.
 5. Repeat steps 2 to 4 until `ahoy lint` exits with code `0`. The Bash tool reports the exit code.
 6. Review the result: `git diff`
-
-When the change touches `docker-compose.yml`, also run DCLint as its section in [references/tools.md](references/tools.md) describes.
 
 When the change touches a file in `.docker/`, also run Hadolint as its section in [references/tools.md](references/tools.md) describes.
 
