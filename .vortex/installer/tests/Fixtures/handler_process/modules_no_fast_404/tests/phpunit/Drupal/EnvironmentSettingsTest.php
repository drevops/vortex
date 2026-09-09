@@ -337,18 +337,6 @@
       'container_yamls' => [$this->app_root . '/' . $this->site_path . '/services.yml'],
       'entity_update_batch_size' => 50,
       'environment' => $environment,
-      'fast404_allow_anon_imagecache' => FALSE,
-      'fast404_exts' => '/^(?!\/robots)^(?!\/system\/files).*\.(txt|png|gif|jpe?g|css|js|ico|swf|flv|cgi|bat|pl|dll|exe|asp)$/i',
-      'fast404_html' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL "@path" was not found on this server.</p></body></html>',
-      'fast404_path_check' => FALSE,
-      'fast404_respect_redirect' => FALSE,
-      'fast404_url_whitelisting' => TRUE,
-      'fast404_whitelist' => [
-        'index.php',
-        'rss.xml',
-        'cron.php',
-        'xmlrpc.php',
-      ],
       'file_private_path' => 'sites/default/files/private',
       'file_public_path' => 'sites/default/files',
       'file_scan_ignore_directories' => [
