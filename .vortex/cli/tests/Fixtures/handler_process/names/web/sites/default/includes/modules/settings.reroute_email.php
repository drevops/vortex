@@ -10,8 +10,8 @@
 use DrevOps\EnvironmentDetector\Environment;
 
 // Default reroute email address and allowed list.
-$config['reroute_email.settings']['address'] = getenv('DRUPAL_REROUTE_EMAIL_ADDRESS') ?: 'webmaster@star-wars.com';
-$config['reroute_email.settings']['allowed'] = getenv('DRUPAL_REROUTE_EMAIL_ALLOWED') ?: '*@star-wars.com';
+$config['reroute_email.settings']['address'] = getenv('DRUPAL_REROUTE_EMAIL_ADDRESS') ?: 'webmaster@death-star.com';
+$config['reroute_email.settings']['allowed'] = getenv('DRUPAL_REROUTE_EMAIL_ALLOWED') ?: '*@death-star.com';
 
 // Enable rerouting in all environments except local, ci, stage and prod.
 // This covers dev and any custom environments (e.g., PR environments).
