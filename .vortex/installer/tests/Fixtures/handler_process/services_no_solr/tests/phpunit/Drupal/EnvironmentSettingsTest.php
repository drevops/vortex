@@ -83,8 +83,6 @@
     $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
     $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
     $config['system.performance']['cache']['page']['max_age'] = 900;
-    $config['search_api.server.solr']['backend_config']['connector_config']['host'] = 'search';
-    $config['search_api.server.solr']['backend_config']['connector_config']['port'] = 8983;
     $this->assertConfig($config);
 
     $settings['auto_create_htaccess'] = FALSE;
@@ -166,8 +164,6 @@
     $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
     $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
     $config['system.performance']['cache']['page']['max_age'] = 1800;
-    $config['search_api.server.solr']['backend_config']['connector_config']['host'] = 'search';
-    $config['search_api.server.solr']['backend_config']['connector_config']['port'] = 8983;
     $this->assertConfig($config);
 
     // Verify settings overrides.
@@ -225,8 +221,6 @@
     $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
     $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
     $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
-    $config['search_api.server.solr']['backend_config']['connector_config']['host'] = 'search';
-    $config['search_api.server.solr']['backend_config']['connector_config']['port'] = 8983;
     $this->assertConfig($config);
 
     $settings['auto_create_htaccess'] = FALSE;
@@ -283,8 +277,6 @@
     $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
     $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
     $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
-    $config['search_api.server.solr']['backend_config']['connector_config']['host'] = 'search';
-    $config['search_api.server.solr']['backend_config']['connector_config']['port'] = 8983;
     $this->assertConfig($config);
 
     $settings['auto_create_htaccess'] = FALSE;
@@ -343,8 +335,6 @@
     $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
     $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
     $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
-    $config['search_api.server.solr']['backend_config']['connector_config']['host'] = 'search';
-    $config['search_api.server.solr']['backend_config']['connector_config']['port'] = 8983;
     $this->assertConfig($config);
 
     $settings['auto_create_htaccess'] = FALSE;
