@@ -80,6 +80,22 @@
 
 ## 4. Setting up integrations
 
+- [ ] Configure Acquia integration:
+  - [ ] Create a `deployer` user in Acquia.
+  - [ ] Add this user to the Acquia project. Normally, this user would be
+    added to your project in GitHub as well.
+  - [ ] Login as this user to Acquia, go to
+    Acquia Cloud UI->Account->Credentials->Cloud API->Private key and
+    copy the token.
+  - [ ] Add token key to every non-developer's environment that must have
+    read access (only read access!). For example, add it to CI if
+    it has to get database dump.
+  - [ ] Create an SSH key pair with email `deployer+star_wars@yourcompany.com`
+    and add to this user in Acquia.
+  - [ ] Add private key to every non-developer's environment that must have
+    write access (only write access!). For example, add it to CI if
+    it has to push code to Acquia.
+
 - [ ] Configure Renovate by [logging in](https://developer.mend.io/) with your GitHub account and
   adding a project through UI.
 
