@@ -1,7 +1,7 @@
 <div align="center">
   <a href="" rel="noopener">
   <img width=200px height=100px
-    src="web/themes/custom/star_wars/logo.svg"
+    src="docroot/themes/custom/star_wars/logo.svg"
     alt="star wars Logo"></a>
 </div>
 
@@ -42,6 +42,14 @@
   MySQL, or MAMP.
 - Check out the project repository into one of the [supported Docker
   directories](https://docs.docker.com/desktop/settings-and-maintenance/settings/#virtual-file-shares).
+
+- Authenticate with Acquia Cloud API
+  1. Create your Acquia Cloud API token:<br/>
+     Acquia Cloud UI -> Account -> API tokens -> Create Token
+  2. Copy `.env.local.example` to `.env.local`.
+  3. Populate the `$VORTEX_ACQUIA_KEY` and `$VORTEX_ACQUIA_SECRET`
+     environment variables in the `.env.local` file with the values
+     generated in step 1.
 
 - `ahoy fetch-db`
 
