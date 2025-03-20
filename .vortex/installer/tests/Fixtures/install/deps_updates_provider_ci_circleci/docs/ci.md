@@ -5,12 +5,12 @@
 Before feature changes can be merged into a shared mainline, a complete build
 must run and pass all tests on CI server.
 
-## GitHub Actions
+## Circle CI
 
-This project uses [GitHub Actions](https://github.com/features/actions) as a
-CI server: it imports production backups into fully built codebase and runs
-code linting and tests. When tests pass, a deployment process is triggered for
-nominated branches (usually, `main` and `develop`).
+This project uses [Circle CI](https://circleci.com/) as a CI server: it imports
+production backups into fully built codebase and runs code linting and tests.
+When tests pass, a deployment process is triggered for nominated branches
+(usually, `main` and `develop`).
 
 Refer to https://vortex.drevops.com/latest/usage/ci for more information.
 
@@ -21,10 +21,7 @@
 
 ### SSH
 
-GitHub Actions does not supports shell access to the build, but there is an
-action provided withing the `build` job that allows you to run a build with SSH
-support.
-
-Use "Run workflow" button in GitHub Actions UI to start build with SSH support
-that will be available for 120 minutes after the build is finished.
+Circle CI supports shell access to the build for 120 minutes after the build is
+finished when the build is started with SSH support. Use "Rerun job with SSH"
+button in Circle CI UI to start build with SSH support.
 
