@@ -8,32 +8,32 @@
   @api @javascript
   Scenario: Counter block is visible on homepage
     Given I go to the homepage
-    Then I should see a ".sw-base-counter-block" element
-    And the ".sw-base-counter-label" element should contain "Counter:"
-    And the ".sw-base-counter-value" element should contain "0"
+    Then I should see a ".the-force-base-counter-block" element
+    And the ".the-force-base-counter-label" element should contain "Counter:"
+    And the ".the-force-base-counter-value" element should contain "0"
 
-    When I click on the element ".sw-base-counter-btn--increment"
-    Then the ".sw-base-counter-value" element should contain "1"
-    When I click on the element ".sw-base-counter-btn--increment"
-    Then the ".sw-base-counter-value" element should contain "2"
+    When I click on the element ".the-force-base-counter-btn--increment"
+    Then the ".the-force-base-counter-value" element should contain "1"
+    When I click on the element ".the-force-base-counter-btn--increment"
+    Then the ".the-force-base-counter-value" element should contain "2"
 
-    When I click on the element ".sw-base-counter-btn--decrement"
-    Then the ".sw-base-counter-value" element should contain "1"
-    When I click on the element ".sw-base-counter-btn--decrement"
-    Then the ".sw-base-counter-value" element should contain "0"
+    When I click on the element ".the-force-base-counter-btn--decrement"
+    Then the ".the-force-base-counter-value" element should contain "1"
+    When I click on the element ".the-force-base-counter-btn--decrement"
+    Then the ".the-force-base-counter-value" element should contain "0"
 
-    When I click on the element ".sw-base-counter-btn--decrement"
-    Then the ".sw-base-counter-value" element should contain "-1"
+    When I click on the element ".the-force-base-counter-btn--decrement"
+    Then the ".the-force-base-counter-value" element should contain "-1"
 
-    When I click on the element ".sw-base-counter-btn--reset"
-    Then the ".sw-base-counter-value" element should contain "0"
+    When I click on the element ".the-force-base-counter-btn--reset"
+    Then the ".the-force-base-counter-value" element should contain "0"
 
   @api @javascript
   Scenario: Counter persistence across page reloads
     Given I go to the homepage
-    When I click on the element ".sw-base-counter-btn--increment"
-    And I click on the element ".sw-base-counter-btn--increment"
-    And I click on the element ".sw-base-counter-btn--increment"
-    Then the ".sw-base-counter-value" element should contain "3"
+    When I click on the element ".the-force-base-counter-btn--increment"
+    And I click on the element ".the-force-base-counter-btn--increment"
+    And I click on the element ".the-force-base-counter-btn--increment"
+    Then the ".the-force-base-counter-value" element should contain "3"
     When I reload the page
-    Then the ".sw-base-counter-value" element should contain "3"
+    Then the ".the-force-base-counter-value" element should contain "3"
