@@ -9,8 +9,8 @@
 
 $settings['config_exclude_modules'][] = 'reroute_email';
 
-$config['reroute_email.settings']['address'] = getenv('DRUPAL_REROUTE_EMAIL_ADDRESS') ?: 'webmaster@star-wars.com';
-$config['reroute_email.settings']['allowed'] = getenv('DRUPAL_REROUTE_EMAIL_ALLOWED') ?: '*@star-wars.com';
+$config['reroute_email.settings']['address'] = getenv('DRUPAL_REROUTE_EMAIL_ADDRESS') ?: 'webmaster@death-star.com';
+$config['reroute_email.settings']['allowed'] = getenv('DRUPAL_REROUTE_EMAIL_ALLOWED') ?: '*@death-star.com';
 
 if (in_array($settings['environment'], [ENVIRONMENT_LOCAL, ENVIRONMENT_CI, ENVIRONMENT_PROD], TRUE)) {
   // Deliver every message to its intended recipient. Local and CI capture
