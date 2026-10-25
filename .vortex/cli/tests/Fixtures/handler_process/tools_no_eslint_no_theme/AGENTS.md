@@ -53,6 +53,11 @@
 ahoy lint     # Check code style
 ahoy lint-fix # Auto-fix code style
 
+# Storybook
+ahoy storybook         # Develop components: live Storybook that regenerates stories as they change
+ahoy storybook-build   # Rebuild the component library served at /storybook
+ahoy storybook-stories # Generate stories only ("--watch" to keep regenerating)
+
 # PHPUnit testing
 ahoy test            # Run PHPUnit tests
 ahoy test-unit       # Run PHPUnit Unit tests
