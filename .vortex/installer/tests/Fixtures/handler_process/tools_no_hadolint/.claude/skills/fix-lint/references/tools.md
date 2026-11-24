@@ -150,15 +150,3 @@
 docker run --rm -v "${PWD}:/app" -w /app zavoloklom/dclint:<tag> .
 docker run --rm -v "${PWD}:/app" -w /app zavoloklom/dclint:<tag> --fix .
 ```
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
