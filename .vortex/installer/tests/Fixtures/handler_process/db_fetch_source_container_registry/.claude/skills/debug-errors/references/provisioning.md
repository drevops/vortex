@@ -38,6 +38,10 @@
 | `Drupal status report contains errors.`                                  | `VORTEX_PROVISION_VERIFY_STATUS_REPORT=1` and a requirement has the error severity      | Read them with `ahoy drush core:requirements --severity=2`. List accepted keys in `VORTEX_PROVISION_VERIFY_STATUS_REPORT_EXCLUDE` |
 | `Unable to read the Drupal status report.`                               | `drush core:requirements` itself failed                                                 | Run `ahoy drush core:requirements --debug` and read its error                                               |
 
+| Message                                                    | Cause                                                       | Fix                                                                                                |
+|------------------------------------------------------------|-------------------------------------------------------------|----------------------------------------------------------------------------------------------------|
+| `Looks like the database in the container image is corrupted.` | `VORTEX_DB_IMAGE` is set and Drupal doesn't bootstrap from the database in the image | Pull the image again with `ahoy pull-db`, then reload it with `ahoy reload-db`, after asking |
+
 ## Results that look like failures
 
 - `Site content will be preserved.` with `Overwrite existing DB : No`: a site was installed and `VORTEX_PROVISION_OVERRIDE_DB` isn't `1`, so the dump wasn't imported. To import it, provision with `VORTEX_PROVISION_OVERRIDE_DB=1` in front of the command, after asking.
