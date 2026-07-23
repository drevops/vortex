@@ -8,32 +8,32 @@
   @api @javascript
   Scenario: Counter block is visible on homepage
     Given I go to the homepage
-    Then I should see a ".sw-demo-counter-block" element
-    And the ".sw-demo-counter-label" element should contain "Counter:"
-    And the ".sw-demo-counter-value" element should contain "0"
+    Then I should see a ".the-force-demo-counter-block" element
+    And the ".the-force-demo-counter-label" element should contain "Counter:"
+    And the ".the-force-demo-counter-value" element should contain "0"
 
-    When I click on the element ".sw-demo-counter-btn--increment"
-    Then the ".sw-demo-counter-value" element should contain "1"
-    When I click on the element ".sw-demo-counter-btn--increment"
-    Then the ".sw-demo-counter-value" element should contain "2"
+    When I click on the element ".the-force-demo-counter-btn--increment"
+    Then the ".the-force-demo-counter-value" element should contain "1"
+    When I click on the element ".the-force-demo-counter-btn--increment"
+    Then the ".the-force-demo-counter-value" element should contain "2"
 
-    When I click on the element ".sw-demo-counter-btn--decrement"
-    Then the ".sw-demo-counter-value" element should contain "1"
-    When I click on the element ".sw-demo-counter-btn--decrement"
-    Then the ".sw-demo-counter-value" element should contain "0"
+    When I click on the element ".the-force-demo-counter-btn--decrement"
+    Then the ".the-force-demo-counter-value" element should contain "1"
+    When I click on the element ".the-force-demo-counter-btn--decrement"
+    Then the ".the-force-demo-counter-value" element should contain "0"
 
-    When I click on the element ".sw-demo-counter-btn--decrement"
-    Then the ".sw-demo-counter-value" element should contain "-1"
+    When I click on the element ".the-force-demo-counter-btn--decrement"
+    Then the ".the-force-demo-counter-value" element should contain "-1"
 
-    When I click on the element ".sw-demo-counter-btn--reset"
-    Then the ".sw-demo-counter-value" element should contain "0"
+    When I click on the element ".the-force-demo-counter-btn--reset"
+    Then the ".the-force-demo-counter-value" element should contain "0"
 
   @api @javascript
   Scenario: Counter persistence across page reloads
     Given I go to the homepage
-    When I click on the element ".sw-demo-counter-btn--increment"
-    And I click on the element ".sw-demo-counter-btn--increment"
-    And I click on the element ".sw-demo-counter-btn--increment"
-    Then the ".sw-demo-counter-value" element should contain "3"
+    When I click on the element ".the-force-demo-counter-btn--increment"
+    And I click on the element ".the-force-demo-counter-btn--increment"
+    And I click on the element ".the-force-demo-counter-btn--increment"
+    Then the ".the-force-demo-counter-value" element should contain "3"
     When I reload the page
-    Then the ".sw-demo-counter-value" element should contain "3"
+    Then the ".the-force-demo-counter-value" element should contain "3"
