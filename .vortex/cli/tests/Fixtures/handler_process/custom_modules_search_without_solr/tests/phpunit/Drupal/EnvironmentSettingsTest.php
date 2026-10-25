@@ -92,8 +92,6 @@
     $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
     $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
     $config['system.performance']['cache']['page']['max_age'] = 900;
-    $config['search_api.server.solr']['backend_config']['connector_config']['host'] = 'search';
-    $config['search_api.server.solr']['backend_config']['connector_config']['port'] = 8983;
     $this->assertConfig($config);
 
     $settings['auto_create_htaccess'] = FALSE;
@@ -191,8 +189,6 @@
     $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
     $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
     $config['system.performance']['cache']['page']['max_age'] = 1800;
-    $config['search_api.server.solr']['backend_config']['connector_config']['host'] = 'search';
-    $config['search_api.server.solr']['backend_config']['connector_config']['port'] = 8983;
     $this->assertConfig($config);
 
     // Verify settings overrides.
@@ -266,8 +262,6 @@
     $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
     $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
     $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
-    $config['search_api.server.solr']['backend_config']['connector_config']['host'] = 'search';
-    $config['search_api.server.solr']['backend_config']['connector_config']['port'] = 8983;
     $this->assertConfig($config);
 
     $settings['auto_create_htaccess'] = FALSE;
@@ -340,8 +334,6 @@
     $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
     $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
     $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
-    $config['search_api.server.solr']['backend_config']['connector_config']['host'] = 'search';
-    $config['search_api.server.solr']['backend_config']['connector_config']['port'] = 8983;
     $this->assertConfig($config);
 
     $settings['auto_create_htaccess'] = FALSE;
@@ -416,8 +408,6 @@
     $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
     $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
     $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
-    $config['search_api.server.solr']['backend_config']['connector_config']['host'] = 'search';
-    $config['search_api.server.solr']['backend_config']['connector_config']['port'] = 8983;
     $this->assertConfig($config);
 
     $settings['auto_create_htaccess'] = FALSE;
