@@ -139,26 +139,3 @@
 ```shell
 ahoy cli vendor/bin/gherkinlint lint tests/behat/features/<name>.feature
 ```
-
-## DCLint
-
-The CI `lint` job runs the `zavoloklom/dclint` image on the project root, with the configuration in `.dclintrc`. Errors fail the job. Warnings, such as service keys out of order or unquoted ports, appear in the log without failing it. `.dclintrc` turns off 4 rules and sets `service-keys-order` to a warning with 8 groups of keys. Before linting, CI deletes the lines of `docker-compose.yml` that contain `###` and removes the `##` comment markers, so it checks the CI variant of the file.
-
-The tool isn't in the `cli` container. Run it with Docker from the project root, using the image tag that the DCLint step in the CI configuration pins. `--fix` fixes the service key order and the port quotes:
-
-```shell
-docker run --rm -v "${PWD}:/app" -w /app zavoloklom/dclint:<tag> .
-docker run --rm -v "${PWD}:/app" -w /app zavoloklom/dclint:<tag> --fix .
-```
-
-## Hadolint
-
-The CI `lint` job pipes every file under `.docker/` that is named `Dockerfile` or ends in `.dockerfile` into the `hadolint/hadolint` image, with the default rules. Findings at the `info` severity or higher fail the job, and `style` findings appear in the log without failing it. The project has no Hadolint configuration file.
-
-Hadolint has no fixer. Fix each finding by hand, such as pinning package versions or splitting a `RUN` instruction that ShellCheck flags. The template ignores the version pinning rules where the package set tracks the pinned base image.
-
-The tool isn't in the `cli` container. Lint 1 Dockerfile per call with Docker, using the image tag that the Hadolint step in the CI configuration pins:
-
-```shell
-docker run --rm -i hadolint/hadolint:<tag> < .docker/<name>.dockerfile
-```
