@@ -89,8 +89,6 @@
 
 When the root `package.json` or `package-lock.json` changed, run `ahoy fei`. It runs `npm ci`, which installs exactly what the lock file lists and fails when the lock file and `package.json` disagree. A failure there means the lock file in the commit is out of date, so tell the developer instead of regenerating it.
 
-When files in `web/themes/custom/<theme>` changed, run `ahoy fei` for its npm packages and then `ahoy fe` to rebuild its assets. Unstyled pages after a pull usually mean the assets weren't rebuilt.
-
 ## A variable doesn't apply
 
 - A variable passed in `docker-compose.yml` needs `ahoy up`, which recreates the container. `ahoy restart` keeps the old value.
