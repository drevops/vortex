@@ -49,7 +49,7 @@
 
 Read the output:
 
-- The first frame of the trace that points into `web/modules/custom/` or the custom theme is usually where the fix goes.
+- The first frame of the trace that points into `docroot/modules/custom/` or the custom theme is usually where the fix goes.
 - A `NotFoundHttpException` or `AccessDeniedHttpException` is a 404 or a 403, not a crash. Check the path and the user.
 - Drupal bypasses Page Cache and Dynamic Page Cache on the command line, so the request runs the full render path every time.
 - When the probe fails with an error about the session or the request stack, the route depends on a real browser request. Reproduce it with `curl` and read the watchdog log instead.
