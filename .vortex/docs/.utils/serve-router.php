<?php

/**
 * @file
 * Router for the PHP built-in server serving a built Docusaurus site.
 *
 * Docusaurus emits a directory per route, so a request without a trailing
 * slash has to resolve to that directory's 'index.html'. The built-in server
 * only does so for a trailing slash, and returns its own 404 page rather than
 * the site's.
 *
 * @usage
 * php -S localhost:8000 -t <build-dir> serve-router.php
 */

declare(strict_types=1);

$doc_root = realpath($_SERVER['DOCUMENT_ROOT']);
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = is_string($path) ? rawurldecode($path) : '/';

$target = realpath($doc_root . $path);

// Reject a path that resolves outside the build directory.
if ($target !== FALSE && $target !== $doc_root && !str_starts_with($target, $doc_root . DIRECTORY_SEPARATOR)) {
  http_response_code(400);

  return TRUE;
}

// Serve an existing file as-is and let the server set the content type.
if ($target !== FALSE && is_file($target)) {
  return FALSE;
}

$index = $target === FALSE ? FALSE : realpath($target . '/index.html');

if ($index !== FALSE && is_file($index)) {
  header('Content-Type: text/html; charset=UTF-8');
  readfile($index);

  return TRUE;
}

// Fall back to the site's own 404 page so navigation errors look like the site.
$not_found = $doc_root . '/404.html';

http_response_code(404);

if (is_file($not_found)) {
  header('Content-Type: text/html; charset=UTF-8');
  readfile($not_found);
}

return TRUE;
