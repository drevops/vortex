@@ -7,8 +7,8 @@
 
 declare(strict_types=1);
 
-$config['reroute_email.settings']['address'] = getenv('DRUPAL_REROUTE_EMAIL_ADDRESS') ?: 'webmaster@star-wars.com';
-$config['reroute_email.settings']['allowed'] = getenv('DRUPAL_REROUTE_EMAIL_ALLOWED') ?: '*@star-wars.com';
+$config['reroute_email.settings']['address'] = getenv('DRUPAL_REROUTE_EMAIL_ADDRESS') ?: 'webmaster@death-star.com';
+$config['reroute_email.settings']['allowed'] = getenv('DRUPAL_REROUTE_EMAIL_ALLOWED') ?: '*@death-star.com';
 
 // Rerouting replaces the recipient of every outgoing message with the address
 // above. Disabling it delivers messages to their original recipients, so it is
