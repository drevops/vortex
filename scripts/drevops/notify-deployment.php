#!/usr/bin/env php
<?php
/**
 * Send deployment notifications.
 *
 * Usage:
 * php notify-deployment.php "Site Name", "from@example.com", "to1@example.com|Jane Doe, to2@example.com|John Doe", "git-branch", "https://environment-url-example.com"
 */

if (getenv('SKIP_NOTIFY_DEPLOYMENT')) {
  print "Skipping notify deployment.";
  exit;
}

[, $site_name, $from_email, $to_emails, $branch, $url] = $argv;

if (empty($site_name) || empty($from_email) || empty($to_emails) || empty($branch) || empty($url)) {
  print 'ERROR: One of the required parameters is empty: ' . print_r($argv, TRUE);
  exit;
}

date_default_timezone_set('Australia/Melbourne');
$timestamp = date('d/m/Y H:i:s T');

$subject = sprintf('%s deployment notification of "%s"', $site_name, $branch);
$content = <<<EOF
## This is an automated message ##

Site $site_name "$branch" branch has been deployed at $timestamp and is available at $url.

Login at: $url/user/login
EOF;


$sent = [];
$to_emails = explode(', ', $to_emails);
foreach ($to_emails as $email_with_name) {
  [$email, $name] = explode('|', $email_with_name);
  if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $to = !empty($name) ? '"' . $name . '" <' . $email . '>' : $email;
    mail($to, $subject, $content, 'From: ' . $from_email);
    $sent[] = $email;
  }
}

if (count($sent) > 0) {
  print 'Notification email(s) sent to: ' . implode(', ', $sent) . "\n";
}
else {
  print 'No notification emails were sent.' . "\n";
}
