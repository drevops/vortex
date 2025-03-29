@@ -27,6 +27,12 @@
 - Make sure that all local web development services are shut down (Apache/Nginx, Mysql, MAMP etc).
 - Checkout project repository (in one of the [supported Docker directories](https://docs.docker.com/docker-for-mac/osxfs/#access-control)).
 
+- Authenticate with Lagoon
+  1. Create an SSH key and add it to your account in the [Lagoon Dashboard](https://ui-lagoon-master.ch.amazee.io/).
+  2. Copy `.env.local.example` to `.env.local`.
+  3. Update `$VORTEX_DB_DOWNLOAD_SSH_FILE` environment variable in `.env.local` file
+     with the path to the SSH key.
+
 - `ahoy download-db`
 
 - `pygmy up`
