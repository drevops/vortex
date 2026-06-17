@@ -83,8 +83,6 @@
     $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
     $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
     $config['system.performance']['cache']['page']['max_age'] = 900;
-    $config['search_api.server.solr']['backend_config']['connector_config']['host'] = 'search';
-    $config['search_api.server.solr']['backend_config']['connector_config']['port'] = 8983;
     $this->assertConfig($config);
 
     $settings['auto_create_htaccess'] = FALSE;
@@ -162,8 +160,6 @@
     $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
     $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
     $config['system.performance']['cache']['page']['max_age'] = 1800;
-    $config['search_api.server.solr']['backend_config']['connector_config']['host'] = 'search';
-    $config['search_api.server.solr']['backend_config']['connector_config']['port'] = 8983;
     $this->assertConfig($config);
 
     // Verify settings overrides.
@@ -217,8 +213,6 @@
     $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
     $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
     $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
-    $config['search_api.server.solr']['backend_config']['connector_config']['host'] = 'search';
-    $config['search_api.server.solr']['backend_config']['connector_config']['port'] = 8983;
     $this->assertConfig($config);
 
     $settings['auto_create_htaccess'] = FALSE;
@@ -271,8 +265,6 @@
     $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
     $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
     $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
-    $config['search_api.server.solr']['backend_config']['connector_config']['host'] = 'search';
-    $config['search_api.server.solr']['backend_config']['connector_config']['port'] = 8983;
     $this->assertConfig($config);
 
     $settings['auto_create_htaccess'] = FALSE;
@@ -327,8 +319,6 @@
     $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
     $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
     $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
-    $config['search_api.server.solr']['backend_config']['connector_config']['host'] = 'search';
-    $config['search_api.server.solr']['backend_config']['connector_config']['port'] = 8983;
     $this->assertConfig($config);
 
     $settings['auto_create_htaccess'] = FALSE;
