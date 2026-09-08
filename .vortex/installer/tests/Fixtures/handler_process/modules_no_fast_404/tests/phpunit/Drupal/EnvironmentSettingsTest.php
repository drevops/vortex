@@ -96,18 +96,6 @@
     $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
     $settings['entity_update_batch_size'] = 50;
     $settings['environment'] = self::ENVIRONMENT_SUT;
-    $settings['fast404_allow_anon_imagecache'] = FALSE;
-    $settings['fast404_exts'] = '/^(?!\/robots)^(?!\/system\/files).*\.(txt|png|gif|jpe?g|css|js|ico|swf|flv|cgi|bat|pl|dll|exe|asp)$/i';
-    $settings['fast404_html'] = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL "@path" was not found on this server.</p></body></html>';
-    $settings['fast404_path_check'] = FALSE;
-    $settings['fast404_respect_redirect'] = FALSE;
-    $settings['fast404_url_whitelisting'] = TRUE;
-    $settings['fast404_whitelist'] = [
-      'index.php',
-      'rss.xml',
-      'cron.php',
-      'xmlrpc.php',
-    ];
     $settings['file_public_path'] = 'sites/default/files';
     $settings['file_private_path'] = 'sites/default/files/private';
     $settings['file_temp_path'] = '/tmp';
@@ -194,18 +182,6 @@
     $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
     $settings['entity_update_batch_size'] = 50;
     $settings['environment'] = self::ENVIRONMENT_SUT;
-    $settings['fast404_allow_anon_imagecache'] = FALSE;
-    $settings['fast404_exts'] = '/^(?!\/robots)^(?!\/system\/files).*\.(txt|png|gif|jpe?g|css|js|ico|swf|flv|cgi|bat|pl|dll|exe|asp)$/i';
-    $settings['fast404_html'] = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL "@path" was not found on this server.</p></body></html>';
-    $settings['fast404_path_check'] = FALSE;
-    $settings['fast404_respect_redirect'] = FALSE;
-    $settings['fast404_url_whitelisting'] = TRUE;
-    $settings['fast404_whitelist'] = [
-      'index.php',
-      'rss.xml',
-      'cron.php',
-      'xmlrpc.php',
-    ];
     $settings['file_public_path'] = 'custom_public';
     $settings['file_private_path'] = 'custom_private';
     $settings['file_temp_path'] = 'custom_temp';
@@ -263,18 +239,6 @@
     $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
     $settings['entity_update_batch_size'] = 50;
     $settings['environment'] = self::ENVIRONMENT_LOCAL;
-    $settings['fast404_allow_anon_imagecache'] = FALSE;
-    $settings['fast404_exts'] = '/^(?!\/robots)^(?!\/system\/files).*\.(txt|png|gif|jpe?g|css|js|ico|swf|flv|cgi|bat|pl|dll|exe|asp)$/i';
-    $settings['fast404_html'] = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL "@path" was not found on this server.</p></body></html>';
-    $settings['fast404_path_check'] = FALSE;
-    $settings['fast404_respect_redirect'] = FALSE;
-    $settings['fast404_url_whitelisting'] = TRUE;
-    $settings['fast404_whitelist'] = [
-      'index.php',
-      'rss.xml',
-      'cron.php',
-      'xmlrpc.php',
-    ];
     $settings['file_public_path'] = 'sites/default/files';
     $settings['file_private_path'] = 'sites/default/files/private';
     $settings['file_temp_path'] = '/tmp';
@@ -334,18 +298,6 @@
     $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
     $settings['entity_update_batch_size'] = 50;
     $settings['environment'] = self::ENVIRONMENT_LOCAL;
-    $settings['fast404_allow_anon_imagecache'] = FALSE;
-    $settings['fast404_exts'] = '/^(?!\/robots)^(?!\/system\/files).*\.(txt|png|gif|jpe?g|css|js|ico|swf|flv|cgi|bat|pl|dll|exe|asp)$/i';
-    $settings['fast404_html'] = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL "@path" was not found on this server.</p></body></html>';
-    $settings['fast404_path_check'] = FALSE;
-    $settings['fast404_respect_redirect'] = FALSE;
-    $settings['fast404_url_whitelisting'] = TRUE;
-    $settings['fast404_whitelist'] = [
-      'index.php',
-      'rss.xml',
-      'cron.php',
-      'xmlrpc.php',
-    ];
     $settings['file_public_path'] = 'sites/default/files';
     $settings['file_private_path'] = 'sites/default/files/private';
     $settings['file_temp_path'] = '/tmp';
@@ -448,18 +400,6 @@
     $settings['container_yamls'][0] = $this->app_root . '/' . $this->site_path . '/services.yml';
     $settings['entity_update_batch_size'] = 50;
     $settings['environment'] = self::ENVIRONMENT_CI;
-    $settings['fast404_allow_anon_imagecache'] = FALSE;
-    $settings['fast404_exts'] = '/^(?!\/robots)^(?!\/system\/files).*\.(txt|png|gif|jpe?g|css|js|ico|swf|flv|cgi|bat|pl|dll|exe|asp)$/i';
-    $settings['fast404_html'] = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL "@path" was not found on this server.</p></body></html>';
-    $settings['fast404_path_check'] = FALSE;
-    $settings['fast404_respect_redirect'] = FALSE;
-    $settings['fast404_url_whitelisting'] = TRUE;
-    $settings['fast404_whitelist'] = [
-      'index.php',
-      'rss.xml',
-      'cron.php',
-      'xmlrpc.php',
-    ];
     $settings['file_public_path'] = 'sites/default/files';
     $settings['file_private_path'] = 'sites/default/files/private';
     $settings['file_temp_path'] = '/tmp';
