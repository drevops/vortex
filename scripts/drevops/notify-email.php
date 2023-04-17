#!/usr/bin/env php
<?php
/**
 * Notification dispatch to email recipients.
 *
 * Usage:
 *
 * DREVOPS_NOTIFY_PROJECT="Site Name" \
 * DREVOPS_DRUPAL_SITE_EMAIL="from@example.com" \
 * DREVOPS_NOTIFY_EMAIL_RECIPIENTS="to1@example.com|Jane Doe, to2@example.com|John Doe" \
 * DREVOPS_NOTIFY_REF="git-branch" \
 * DREVOPS_NOTIFY_ENVIRONMENT_URL="https://environment-url-example.com" \
 * php notify-email.php
 *
 * php notify-email.php "Site Name", "from@example.com",
 * "to1@example.com|Jane Doe, to2@example.com|John Doe", "git-branch",
 * "https://environment-url-example.com"
 */

array_shift($argv);

$site_name = getenv('DREVOPS_NOTIFY_PROJECT') ?: $argv[0] ?? NULL;
$from_email = getenv('DREVOPS_DRUPAL_SITE_EMAIL') ?: $argv[1] ?? NULL;
$recipients = getenv('DREVOPS_NOTIFY_EMAIL_RECIPIENTS') ?: $argv[2] ?? NULL;
$branch = getenv('DREVOPS_NOTIFY_REF') ?? $argv[3] ?? NULL;
$url = getenv('DREVOPS_NOTIFY_ENVIRONMENT_URL') ?: $argv[4] ?? NULL;

# ------------------------------------------------------------------------------

echo "[INFO] Started email notification.";

# @formatter:off
if(empty($site_name))  { print "[FAIL] Both environment variable DREVOPS_NOTIFY_PROJECT and the first argument are empty."; exit(1); }
if(empty($from_email)) { print "[FAIL] Both environment variable DREVOPS_DRUPAL_SITE_EMAIL and the second argument are empty."; exit(1); }
if(empty($recipients)) { print "[FAIL] Both environment variable DREVOPS_NOTIFY_EMAIL_RECIPIENTS and the third argument are empty."; exit(1); }
if(empty($branch))     { print "[FAIL] Both environment variable DREVOPS_NOTIFY_REF and the fourth argument are empty."; exit(1); }
if(empty($url))        { print "[FAIL] Both environment variable DREVOPS_NOTIFY_ENVIRONMENT_URL and the fifth argument are empty."; exit(1); }
# @formatter:on

date_default_timezone_set('Australia/Melbourne');
$timestamp = date('d/m/Y H:i:s T');

$subject = sprintf('%s deployment notification of "%s"', $site_name, $branch);
$content = <<<EOF
## This is an automated message ##

Site $site_name "$branch" branch has been deployed at $timestamp and is available at $url.

Login at: $url/user/login
EOF;

$sent = [];
$recipients = explode(',', $recipients);
foreach ($recipients as $email_with_name) {
  [$email, $name] = explode('|', trim($email_with_name));
  $email = trim($email);
  $name = trim($name);
  if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $to = !empty($name) ? '"' . $name . '" <' . $email . '>' : $email;
    mail($to, $subject, $content, 'From: ' . $from_email);
    $sent[] = $email;
  }
}

if (count($sent) > 0) {
  print '       Notification email(s) sent to: ' . implode(', ', $sent) . "\n";
}
else {
  print '       No notification emails were sent.' . "\n";
}

echo "[ OK ] Finished email notification.";
