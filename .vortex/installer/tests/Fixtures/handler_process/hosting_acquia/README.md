@@ -1,6 +1,6 @@
 <div align="center">
   <a href="" rel="noopener">
-  <img width=200px height=100px src="web/themes/custom/star_wars/logo.svg" alt="star wars Logo"></a>
+  <img width=200px height=100px src="docroot/themes/custom/star_wars/logo.svg" alt="star wars Logo"></a>
 </div>
 
 <h1 align="center">star wars</h1>
@@ -33,6 +33,13 @@
 - Make sure that you have latest versions of all required software installed: [Docker](https://www.docker.com/), [Pygmy](https://github.com/pygmystack/pygmy), [Ahoy](https://github.com/ahoy-cli/ahoy)
 - Make sure that all local web development services are shut down (Apache/Nginx, Mysql, MAMP etc).
 - Checkout project repository (in one of the [supported Docker directories](https://docs.docker.com/desktop/settings-and-maintenance/settings/#virtual-file-shares)).
+
+- Authenticate with Acquia Cloud API
+  1. Create your Acquia Cloud API token:<br/>
+     Acquia Cloud UI -> Account -> API tokens -> Create Token
+  2. Copy `.env.local.example` to `.env.local`.
+  3. Populate `$VORTEX_ACQUIA_KEY` and `$VORTEX_ACQUIA_SECRET` environment
+     variables in `.env.local` file with values generated in the step above.
 
 - `ahoy download-db`
 
