@@ -236,8 +236,6 @@
     $config['reroute_email.settings']['enable'] = FALSE;
     $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
     $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
-    $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
-    $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
     $config['search_api.server.solr']['backend_config']['connector_config']['host'] = 'search';
     $config['search_api.server.solr']['backend_config']['connector_config']['port'] = 8983;
     $this->assertConfig($config);
@@ -296,8 +294,6 @@
     $config['reroute_email.settings']['enable'] = FALSE;
     $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
     $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
-    $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
-    $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
     $config['search_api.server.solr']['backend_config']['connector_config']['host'] = 'search';
     $config['search_api.server.solr']['backend_config']['connector_config']['port'] = 8983;
     $this->assertConfig($config);
@@ -357,8 +353,6 @@
     $config['reroute_email.settings']['enable'] = FALSE;
     $config['reroute_email.settings']['address'] = 'webmaster@star-wars.com';
     $config['reroute_email.settings']['allowed'] = '*@star-wars.com';
-    $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
-    $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
     $config['search_api.server.solr']['backend_config']['connector_config']['host'] = 'search';
     $config['search_api.server.solr']['backend_config']['connector_config']['port'] = 8983;
     $this->assertConfig($config);
