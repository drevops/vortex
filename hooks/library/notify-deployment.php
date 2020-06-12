#!/usr/bin/env php
<?php
/**
 * Send deployment notifications.
 */

$site_name = 'YOURSITE';
$email_addresses = [
  'change.me@your-site-url' => 'CHANGE ME',
];

$from = 'noreply-acquia-deploy@your-site-url';

$domain = 'your-site-url';
$default_domain = 'prod.acquia-sites.com';

//------------------------------------------------------------------------------

if(getenv('SKIP_NOTIFY_DEPLOYMENT')) {
  print "Skipping notify deployment.";
  exit;
}

list(, $site, $target_env, $branch) = $argv;

if (empty($site) || empty($target_env) || empty($branch)) {
  print 'ERROR: One of the required parameters is empty';
  exit;
}

if (strpos($target_env, 'ode') === 0) {
  $site_url = "https://{$site}{$target_env}.$default_domain";
}
else {
  $subdomain = $target_env;

  if ($subdomain == 'test') {
    $subdomain = 'stage';
  }

  $site_url = "https://$subdomain.$domain";
}

date_default_timezone_set('Australia/Melbourne');
$timestamp = date('d/m/Y H:i:s T');

$subject = sprintf('%s deployment notification of "%s" to "%s" environment', $site_name, $branch, $target_env);

$content = <<<EOF
## This is an automated message ##

Site $site_name "$branch" branch has been deployed to the "$target_env" environment at $timestamp and is available at $site_url.

Login at: $site_url/user/login
EOF;

foreach ($email_addresses as $email => $name) {
  $to = '"' . $name . '" <' . $email . '>';
  mail($to, $subject, $content, 'From: ' . $from);
}

print 'Notification email sent to: ' . implode(', ', array_keys($email_addresses)) . "\n";
