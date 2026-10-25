@@ -88,9 +88,6 @@
     $config['shield.settings']['shield_enable'] = TRUE;
     $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
     $config['xmlsitemap_engines.settings']['submit'] = FALSE;
-    $config['reroute_email.settings']['enable'] = TRUE;
-    $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
-    $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
     $config['system.performance']['cache']['page']['max_age'] = 900;
     $config['search_api.server.solr']['backend_config']['connector_config']['host'] = 'search';
     $config['search_api.server.solr']['backend_config']['connector_config']['port'] = 8983;
@@ -100,7 +97,6 @@
     $settings['config_exclude_modules'] = [
       'devel',
       'generated_content',
-      'reroute_email',
       'sdc_devel',
       'testmode',
     ];
@@ -187,9 +183,6 @@
     $config['shield.settings']['shield_enable'] = TRUE;
     $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
     $config['xmlsitemap_engines.settings']['submit'] = FALSE;
-    $config['reroute_email.settings']['enable'] = TRUE;
-    $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
-    $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
     $config['system.performance']['cache']['page']['max_age'] = 1800;
     $config['search_api.server.solr']['backend_config']['connector_config']['host'] = 'search';
     $config['search_api.server.solr']['backend_config']['connector_config']['port'] = 8983;
@@ -200,7 +193,6 @@
     $settings['config_exclude_modules'] = [
       'devel',
       'generated_content',
-      'reroute_email',
       'sdc_devel',
       'testmode',
     ];
@@ -261,9 +253,6 @@
     $config['xmlsitemap_engines.settings']['submit'] = FALSE;
     $config['system.logging']['error_level'] = 'all';
     $config['system.performance']['cache']['page']['max_age'] = 900;
-    $config['reroute_email.settings']['enable'] = FALSE;
-    $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
-    $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
     $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
     $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
     $config['search_api.server.solr']['backend_config']['connector_config']['host'] = 'search';
@@ -274,7 +263,6 @@
     $settings['config_exclude_modules'] = [
       'devel',
       'generated_content',
-      'reroute_email',
       'sdc_devel',
       'testmode',
     ];
@@ -335,9 +323,6 @@
     $config['xmlsitemap_engines.settings']['submit'] = FALSE;
     $config['system.logging']['error_level'] = 'all';
     $config['system.performance']['cache']['page']['max_age'] = 900;
-    $config['reroute_email.settings']['enable'] = FALSE;
-    $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
-    $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
     $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
     $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
     $config['search_api.server.solr']['backend_config']['connector_config']['host'] = 'search';
@@ -348,7 +333,6 @@
     $settings['config_exclude_modules'] = [
       'devel',
       'generated_content',
-      'reroute_email',
       'sdc_devel',
       'testmode',
     ];
@@ -411,9 +395,6 @@
     $config['system.logging']['error_level'] = 'all';
     $config['system.mail']['interface']['default'] = 'test_mail_collector';
     $config['system.performance']['cache']['page']['max_age'] = 900;
-    $config['reroute_email.settings']['enable'] = FALSE;
-    $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
-    $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
     $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
     $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
     $config['search_api.server.solr']['backend_config']['connector_config']['host'] = 'search';
@@ -424,7 +405,6 @@
     $settings['config_exclude_modules'] = [
       'devel',
       'generated_content',
-      'reroute_email',
       'sdc_devel',
       'testmode',
     ];
