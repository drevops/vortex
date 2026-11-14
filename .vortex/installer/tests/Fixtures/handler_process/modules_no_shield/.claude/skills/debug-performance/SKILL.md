@@ -57,8 +57,6 @@
 
 Hosted environments send no debug headers, so read `X-Drupal-Cache`, `X-Drupal-Dynamic-Cache` and `Cache-Control` there.
 
-On `dev` and `stage`, Shield asks for credentials first. Add `-u "USER:PASS"` with the values of `DRUPAL_SHIELD_USER` and `DRUPAL_SHIELD_PASS` from that environment.
-
 ## 3. Decide which layer is slow
 
 - **Slow first byte, high query count**: the render path. Go to step 4 for caching and step 5 for queries.
