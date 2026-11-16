@@ -66,6 +66,10 @@
 # Behat testing
 ahoy test-bdd # Run Behat tests
 ahoy test-bdd -- --tags=@tagname  # Run Behat tests with specific tag
+
+# Lighthouse audits
+ahoy test-lighthouse          # Audit the configured pages, reports in .logs/lighthouse
+ahoy test-lighthouse /about   # Audit specific paths
 ```
 
 ## Before Starting Any Task
