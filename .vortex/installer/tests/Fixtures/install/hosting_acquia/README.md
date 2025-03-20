@@ -27,6 +27,13 @@
 - Make sure that all local web development services are shut down (Apache/Nginx, Mysql, MAMP etc).
 - Checkout project repository (in one of the [supported Docker directories](https://docs.docker.com/docker-for-mac/osxfs/#access-control)).
 
+- Authenticate with Acquia Cloud API
+  1. Create your Acquia Cloud API token:<br/>
+     Acquia Cloud UI -> Account -> API tokens -> Create Token
+  2. Copy `.env.local.example` to `.env.local`.
+  3. Populate `$VORTEX_ACQUIA_KEY` and `$VORTEX_ACQUIA_SECRET` environment
+     variables in `.env.local` file with values generated in the step above.
+
 - `ahoy download-db`
 
 - `pygmy up`
