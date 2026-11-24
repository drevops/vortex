@@ -117,6 +117,10 @@
 
 When `ahoy provision` fails, use the `debug-errors` skill to read its output. See [Provision](https://www.vortextemplate.com/docs/development/provision) for every variable that steers the flow.
 
+### Database in a container image
+
+With `VORTEX_DB_IMAGE` set, the database ships inside the `database` image, and there is no dump file. `ahoy pull-db` downloads the latest image, and `ahoy reload-db` recreates the `database` container from the local image, which replaces the local database. `ahoy provision` reloads the container before it provisions. Put the registry credentials in `.env.local` as `VORTEX_CONTAINER_REGISTRY_USER` and `VORTEX_CONTAINER_REGISTRY_PASS`.
+
 ## After a pull or a branch switch
 
 Skip the full rebuild. Read what changed with `git diff --stat ORIG_HEAD HEAD` or the branch diff, and run only the matching commands:
