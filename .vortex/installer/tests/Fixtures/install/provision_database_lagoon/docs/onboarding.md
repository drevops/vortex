@@ -80,6 +80,17 @@
 
 ## 4. Setting up integrations
 
+- [ ] Configure Lagoon integration:
+  - [ ] Submit a request to AmazeeIO to create a project.
+  - [ ] Add your public key to the project.
+  - [ ] Ensure that you have access to Lagoon: run `ahoy cli` and `drush sa` -
+    a list of available environments should be shown (at least one
+    environment).
+  - [ ] Ensure that you have access to Lagoon UI.
+  - [ ]
+    Setup [Slack notifications](https://docs.lagoon.sh/administering-lagoon/graphql-queries/#adding-notifications-to-the-project)
+  - [ ] Push to remote and ensure that Lagoon was successfully deployed.
+
 - [ ] Configure Renovate by [logging in](https://developer.mend.io/) with your GitHub account and
   adding a project through UI.
 
