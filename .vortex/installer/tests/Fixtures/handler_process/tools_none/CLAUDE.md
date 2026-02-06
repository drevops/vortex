@@ -37,16 +37,6 @@
 ahoy lint     # Check code style
 ahoy lint-fix # Auto-fix code style
 
-# PHPUnit testing
-ahoy test            # Run PHPUnit tests
-ahoy test-unit       # Run PHPUnit Unit tests
-ahoy test-kernel     # Run PHPUnit Kernel tests
-ahoy test-functional # Run PHPUnit Functional tests
-ahoy test -- --filter=TestClassName  # Run specific PHPUnit test class
-
-# Behat testing
-ahoy test-bdd # Run Behat tests
-ahoy test-bdd -- --tags=@tagname  # Run Behat tests with specific tag
 ```
 
 ## Critical Rules
