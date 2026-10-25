<?php

/**
 * @file
 * Fast404 settings.
 */

declare(strict_types=1);

if (file_exists($contrib_path . '/fast_404/fast404.inc')) {
  // Disallowed extensions. Any extension set here will not be served by Drupal
  // and will get a Fast 404. This does not affect actual files on the
  // filesystem, as requests hit them before defaulting to a Drupal request.
  $settings['fast404_exts'] = '/^(?!\/robots)^(?!\/system\/files).*\.(txt|png|gif|jpe?g|css|js|ico|swf|flv|cgi|bat|pl|dll|exe|asp)$/i';

  // Image derivative URLs fall under the same rules as any other static file.
  // Anonymous requests no longer create derivatives, so the site cannot be
  // taken down by hammering it with image style paths.
  $settings['fast404_allow_anon_imagecache'] = FALSE;

  // Check requests against a whitelist before the extension list. Modules that
  // serve their own PHP files need to be whitelisted below if they bootstrap
  // Drupal.
  $settings['fast404_url_whitelisting'] = TRUE;

  // Files and URLs allowed while URL whitelisting is enabled.
  $settings['fast404_whitelist'] = [
    'index.php',
    'rss.xml',
    'cron.php',
    'xmlrpc.php',
  ];

  // Check whether the requested path corresponds to a real page by consulting
  // the router and the URL aliases. This finds 404s earlier at the cost of
  // adding the lookup to regular page loads. The lookup queries the database
  // before Drupal has connected to it, so every path other than the front page
  // answers with a redirect to the installer. Enable it once the module is
  // patched with
  // https://www.drupal.org/files/issues/2022-10-05/fast_404-db_preboot_errors_d9.4-2961512-23.patch
  // @see https://www.drupal.org/project/fast_404/issues/2961512
  $settings['fast404_path_check'] = FALSE;

  // The path check runs before the Redirect module, so it consults the
  // redirect table to avoid answering a configured redirect with a 404. Enable
  // it alongside the path check above.
  $settings['fast404_respect_redirect'] = FALSE;

  // Body of the 404 response. The '@path' token is replaced with the path
  // being requested relative to the executed script.
  $settings['fast404_html'] = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL "@path" was not found on this server.</p></body></html>';

  include_once $contrib_path . '/fast_404/fast404.inc';
  // Each answered request logs a TypeError after the response is sent, because
  // the module passes an array where an exception is expected. Patch it with
  // https://www.drupal.org/files/issues/2023-08-24/fast_404_3x-3194034-10.patch
  // @see https://www.drupal.org/project/fast_404/issues/3194034
  // @phpstan-ignore-next-line
  fast404_preboot($settings);
}
