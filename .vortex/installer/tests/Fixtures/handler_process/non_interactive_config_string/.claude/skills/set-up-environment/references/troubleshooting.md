@@ -44,8 +44,6 @@
 
 Docker Compose starts `cli` only after the `wait-for-dependencies` container exits with code `0`. That container polls the service ports, such as `database:3306`, and gives up when a service isn't ready in time. On the first start of a stopped stack, a slow service can outlast it, and Docker Compose then reports that `wait-for-dependencies` didn't complete successfully.
 
-The `clamav` container is the usual cause on its first start, because it can take several minutes to accept connections on port `3310`.
-
 1. Read which service it waited for: `ahoy logs wait-for-dependencies`.
 2. Run `ahoy up` once more, in the background. The slow service is usually ready by then.
 3. When the second run fails the same way, read the log of the service it waited for, as the previous section describes.
