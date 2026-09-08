@@ -109,7 +109,6 @@
     $settings['trusted_host_patterns'] = [
       '^localhost$',
     ];
-    $settings += static::expectedFast404Settings();
 
     $this->assertSettings($settings);
   }
@@ -196,8 +195,6 @@
       '^localhost$',
     ];
 
-    $settings += static::expectedFast404Settings();
-
     $this->assertSettings($settings);
   }
 
@@ -256,7 +253,6 @@
     $settings['trusted_host_patterns'] = [
       '^localhost$',
     ];
-    $settings += static::expectedFast404Settings();
 
     $this->assertSettings($settings);
   }
@@ -318,7 +314,6 @@
       '^example\-site\.docker\.amazee\.io$',
       '^nginx$',
     ];
-    $settings += static::expectedFast404Settings();
 
     $this->assertSettings($settings);
   }
@@ -419,22 +414,8 @@
     $settings['trusted_host_patterns'] = [
       '^localhost$',
     ];
-    $settings += static::expectedFast404Settings();
 
     $this->assertSettings($settings);
-  }
-
-  /**
-   * Settings applied by the Fast 404 override in every environment.
-   */
-  protected static function expectedFast404Settings(): array {
-    return [
-      'fast404_exts' => '/^(?!\/robots)^(?!\/system\/files).*\.(txt|png|gif|jpe?g|css|js|ico|swf|flv|cgi|bat|pl|dll|exe|asp)$/i',
-      'fast404_allow_anon_imagecache' => TRUE,
-      'fast404_whitelist' => ['index.php', 'rss.xml', 'install.php', 'cron.php', 'update.php', 'xmlrpc.php'],
-      'fast404_string_whitelisting' => ['/advagg_'],
-      'fast404_html' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL "@path" was not found on this server.</p></body></html>',
-    ];
   }
 
 }
