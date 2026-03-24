@@ -59,9 +59,6 @@
 ahoy test-functional # Run PHPUnit Functional tests
 ahoy test -- --filter=TestClassName  # Run specific PHPUnit test class
 
-# Jest testing
-ahoy test-js  # Run Jest JavaScript unit tests
-
 # Behat testing
 ahoy test-bdd # Run Behat tests
 ahoy test-bdd -- --tags=@tagname  # Run Behat tests with specific tag
