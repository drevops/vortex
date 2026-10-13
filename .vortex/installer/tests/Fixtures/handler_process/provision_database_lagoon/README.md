@@ -43,6 +43,13 @@
 - Check out the project repository into one of the [supported Docker
   directories](https://docs.docker.com/desktop/settings-and-maintenance/settings/#virtual-file-shares).
 
+- Authenticate with Lagoon
+  1. Create an SSH key and add it to your account in the [Lagoon
+     Dashboard](https://dashboard.amazeeio.cloud/).
+  2. Copy `.env.local.example` to `.env.local`.
+  3. Update the `$VORTEX_FETCH_DB_SSH_FILE` environment variable in the
+     `.env.local` file with the path to the SSH key.
+
 - `ahoy fetch-db`
 
 - `pygmy up`
