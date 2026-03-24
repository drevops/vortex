@@ -52,19 +52,6 @@
 ahoy lint     # Check code style
 ahoy lint-fix # Auto-fix code style
 
-# PHPUnit testing
-ahoy test            # Run PHPUnit tests
-ahoy test-unit       # Run PHPUnit Unit tests
-ahoy test-kernel     # Run PHPUnit Kernel tests
-ahoy test-functional # Run PHPUnit Functional tests
-ahoy test -- --filter=TestClassName  # Run specific PHPUnit test class
-
-# Jest testing
-ahoy test-js  # Run Jest JavaScript unit tests
-
-# Behat testing
-ahoy test-bdd # Run Behat tests
-ahoy test-bdd -- --tags=@tagname  # Run Behat tests with specific tag
 ```
 
 ## Before Starting Any Task
