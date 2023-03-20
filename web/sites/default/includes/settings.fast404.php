<?php

/**
 * @file
 * Fast404 settings.
 */

if (file_exists($contrib_path . '/fast404/fast404.inc')) {
  $settings['fast404_exts'] = '/^(?!robots).*\.(txt|png|gif|jpe?g|css|js|ico|swf|flv|cgi|bat|pl|dll|exe|asp)$/i';
  $settings['fast404_allow_anon_imagecache'] = TRUE;
  $settings['fast404_whitelist'] = [
    'index.php',
    'rss.xml',
    'install.php',
    'cron.php',
    'update.php',
    'xmlrpc.php',
  ];
  $settings['fast404_string_whitelisting'] = ['/advagg_'];
  $settings['fast404_html'] = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL "@path" was not found on this server.</p></body></html>';
  include_once $contrib_path . '/fast404/fast404.inc';
  fast404_preboot($settings);
}
