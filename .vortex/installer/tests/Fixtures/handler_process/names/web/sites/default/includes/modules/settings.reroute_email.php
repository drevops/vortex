@@ -7,8 +7,8 @@
 
 declare(strict_types=1);
 
-$config['reroute_email.settings']['address'] = getenv('DRUPAL_REROUTE_EMAIL_ADDRESS') ?: 'webmaster@star-wars.com';
-$config['reroute_email.settings']['allowed'] = getenv('DRUPAL_REROUTE_EMAIL_ALLOWED') ?: '*@star-wars.com';
+$config['reroute_email.settings']['address'] = getenv('DRUPAL_REROUTE_EMAIL_ADDRESS') ?: 'webmaster@death-star.com';
+$config['reroute_email.settings']['allowed'] = getenv('DRUPAL_REROUTE_EMAIL_ALLOWED') ?: '*@death-star.com';
 
 if (!in_array($settings['environment'], [ENVIRONMENT_LOCAL, ENVIRONMENT_CI, ENVIRONMENT_STAGE, ENVIRONMENT_PROD], TRUE)) {
   // Send every outgoing message to the address above instead of to its
