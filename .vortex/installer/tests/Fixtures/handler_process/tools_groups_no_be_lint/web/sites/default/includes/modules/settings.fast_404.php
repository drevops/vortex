@@ -20,6 +20,5 @@
   ];
   $settings['fast404_html'] = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL "@path" was not found on this server.</p></body></html>';
   include_once $contrib_path . '/fast_404/fast404.inc';
-  // @phpstan-ignore-next-line
   fast404_preboot($settings);
 }
