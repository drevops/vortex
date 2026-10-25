@@ -12,8 +12,8 @@
 $settings['config_exclude_modules'][] = 'reroute_email';
 
 // Default reroute email address and allowed list.
-$config['reroute_email.settings']['address'] = getenv('DRUPAL_REROUTE_EMAIL_ADDRESS') ?: 'webmaster@star-wars.com';
-$config['reroute_email.settings']['allowed'] = getenv('DRUPAL_REROUTE_EMAIL_ALLOWED') ?: '*@star-wars.com';
+$config['reroute_email.settings']['address'] = getenv('DRUPAL_REROUTE_EMAIL_ADDRESS') ?: 'webmaster@death-star.com';
+$config['reroute_email.settings']['allowed'] = getenv('DRUPAL_REROUTE_EMAIL_ALLOWED') ?: '*@death-star.com';
 
 if (in_array($settings['environment'], [
   Environment::LOCAL,
