<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use PHPUnit\Framework\Attributes\Group;

#[Group('scripts')]
class ProvisionSanitizeDbTest extends UnitTestCase {

  public function testBasicSanitize(): void {
    $this->envSet('VORTEX_PROVISION_SANITIZE_DB_PASSWORD', 'testpass');

    // Drush sql:sanitize.
    $this->mockPassthru([
      'cmd' => "./vendor/bin/drush -y sql:sanitize --sanitize-password='testpass' --sanitize-email='user+%uid@localhost'",
      'result_code' => 0,
    ]);

    // Reset user 0 mail and name.
    $this->mockPassthru([
      'cmd' => "./vendor/bin/drush -y sql:query \"UPDATE \\`users_field_data\\` SET mail = '', name = '' WHERE uid = '0';\"",
      'result_code' => 0,
    ]);

    // Reset user 0 name.
    $this->mockPassthru([
      'cmd' => "./vendor/bin/drush -y sql:query \"UPDATE \\`users_field_data\\` SET name = '' WHERE uid = '0';\"",
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/provision-sanitize-db');

    $this->assertStringContainsString('Sanitizing database.', $output);
    $this->assertStringContainsString('Sanitized database using drush sql:sanitize.', $output);
    $this->assertStringContainsString('Reset user 0 username and email.', $output);
  }

  public function testWithUsernameReplacement(): void {
    $this->envSetMultiple([
      'VORTEX_PROVISION_SANITIZE_DB_PASSWORD' => 'testpass',
      'VORTEX_PROVISION_SANITIZE_DB_REPLACE_USERNAME_WITH_EMAIL' => '1',
    ]);

    // Drush sql:sanitize.
    $this->mockPassthru([
      'cmd' => "./vendor/bin/drush -y sql:sanitize --sanitize-password='testpass' --sanitize-email='user+%uid@localhost'",
      'result_code' => 0,
    ]);

    // Replace username with email.
    $this->mockPassthru([
      'cmd' => "./vendor/bin/drush -y sql:query \"UPDATE \\`users_field_data\\` set users_field_data.name=users_field_data.mail WHERE uid <> '0';\"",
      'result_code' => 0,
    ]);

    // Reset user 0 mail and name.
    $this->mockPassthru([
      'cmd' => "./vendor/bin/drush -y sql:query \"UPDATE \\`users_field_data\\` SET mail = '', name = '' WHERE uid = '0';\"",
      'result_code' => 0,
    ]);

    // Reset user 0 name.
    $this->mockPassthru([
      'cmd' => "./vendor/bin/drush -y sql:query \"UPDATE \\`users_field_data\\` SET name = '' WHERE uid = '0';\"",
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/provision-sanitize-db');

    $this->assertStringContainsString('Updated username with user email.', $output);
  }

  public function testWithCustomSqlFile(): void {
    $sanitize_file = self::$tmp . '/sanitize-test.sql';
    file_put_contents($sanitize_file, 'DELETE FROM test_table;');

    $this->envSetMultiple([
      'VORTEX_PROVISION_SANITIZE_DB_PASSWORD' => 'testpass',
      'VORTEX_PROVISION_SANITIZE_DB_ADDITIONAL_FILE' => $sanitize_file,
    ]);

    // Drush sql:sanitize.
    $this->mockPassthru([
      'cmd' => "./vendor/bin/drush -y sql:sanitize --sanitize-password='testpass' --sanitize-email='user+%uid@localhost'",
      'result_code' => 0,
    ]);

    // Additional SQL file.
    $this->mockPassthru([
      'cmd' => './vendor/bin/drush -y sql:query --file=' . escapeshellarg($sanitize_file),
      'result_code' => 0,
    ]);

    // Reset user 0 mail and name.
    $this->mockPassthru([
      'cmd' => "./vendor/bin/drush -y sql:query \"UPDATE \\`users_field_data\\` SET mail = '', name = '' WHERE uid = '0';\"",
      'result_code' => 0,
    ]);

    // Reset user 0 name.
    $this->mockPassthru([
      'cmd' => "./vendor/bin/drush -y sql:query \"UPDATE \\`users_field_data\\` SET name = '' WHERE uid = '0';\"",
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/provision-sanitize-db');

    $this->assertStringContainsString('Applied custom sanitization commands from file.', $output);
  }

  public function testWithAdminEmail(): void {
    $this->envSetMultiple([
      'VORTEX_PROVISION_SANITIZE_DB_PASSWORD' => 'testpass',
      'DRUPAL_ADMIN_EMAIL' => 'admin@test.com',
    ]);

    // Drush sql:sanitize.
    $this->mockPassthru([
      'cmd' => "./vendor/bin/drush -y sql:sanitize --sanitize-password='testpass' --sanitize-email='user+%uid@localhost'",
      'result_code' => 0,
    ]);

    // Reset user 0 mail and name.
    $this->mockPassthru([
      'cmd' => "./vendor/bin/drush -y sql:query \"UPDATE \\`users_field_data\\` SET mail = '', name = '' WHERE uid = '0';\"",
      'result_code' => 0,
    ]);

    // Reset user 0 name.
    $this->mockPassthru([
      'cmd' => "./vendor/bin/drush -y sql:query \"UPDATE \\`users_field_data\\` SET name = '' WHERE uid = '0';\"",
      'result_code' => 0,
    ]);

    // Update user 1 email.
    $this->mockPassthru([
      'cmd' => "./vendor/bin/drush -y sql:query \"UPDATE \\`users_field_data\\` SET mail = 'admin@test.com' WHERE uid = '1';\"",
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/provision-sanitize-db');

    $this->assertStringContainsString('Updated user 1 email.', $output);
  }

  public function testCustomEmailAndPassword(): void {
    $this->envSetMultiple([
      'VORTEX_PROVISION_SANITIZE_DB_EMAIL' => 'custom+%uid@example.com',
      'VORTEX_PROVISION_SANITIZE_DB_PASSWORD' => 'custom_pass_123',
    ]);

    // Drush sql:sanitize with custom values.
    $this->mockPassthru([
      'cmd' => "./vendor/bin/drush -y sql:sanitize --sanitize-password='custom_pass_123' --sanitize-email='custom+%uid@example.com'",
      'result_code' => 0,
    ]);

    // Reset user 0 mail and name.
    $this->mockPassthru([
      'cmd' => "./vendor/bin/drush -y sql:query \"UPDATE \\`users_field_data\\` SET mail = '', name = '' WHERE uid = '0';\"",
      'result_code' => 0,
    ]);

    // Reset user 0 name.
    $this->mockPassthru([
      'cmd' => "./vendor/bin/drush -y sql:query \"UPDATE \\`users_field_data\\` SET name = '' WHERE uid = '0';\"",
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/provision-sanitize-db');

    $this->assertStringContainsString('Sanitized database using drush sql:sanitize.', $output);
  }

  public function testSqlFileNotFoundSkips(): void {
    $this->envSetMultiple([
      'VORTEX_PROVISION_SANITIZE_DB_PASSWORD' => 'testpass',
      'VORTEX_PROVISION_SANITIZE_DB_ADDITIONAL_FILE' => '/nonexistent/sanitize.sql',
    ]);

    // Drush sql:sanitize.
    $this->mockPassthru([
      'cmd' => "./vendor/bin/drush -y sql:sanitize --sanitize-password='testpass' --sanitize-email='user+%uid@localhost'",
      'result_code' => 0,
    ]);

    // Reset user 0 mail and name.
    $this->mockPassthru([
      'cmd' => "./vendor/bin/drush -y sql:query \"UPDATE \\`users_field_data\\` SET mail = '', name = '' WHERE uid = '0';\"",
      'result_code' => 0,
    ]);

    // Reset user 0 name.
    $this->mockPassthru([
      'cmd' => "./vendor/bin/drush -y sql:query \"UPDATE \\`users_field_data\\` SET name = '' WHERE uid = '0';\"",
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/provision-sanitize-db');

    // Additional file not found, so no custom sanitization message.
    $this->assertStringNotContainsString('Applied custom sanitization commands', $output);
  }

  public function testDrushCommandFails(): void {
    $this->envSet('VORTEX_PROVISION_SANITIZE_DB_PASSWORD', 'testpass');

    // Drush sql:sanitize fails.
    $this->mockPassthru([
      'cmd' => "./vendor/bin/drush -y sql:sanitize --sanitize-password='testpass' --sanitize-email='user+%uid@localhost'",
      'result_code' => 1,
    ]);

    $this->runScriptError('src/provision-sanitize-db', 'Drush command failed');
  }

  public function testFullOptions(): void {
    $sanitize_file = self::$tmp . '/sanitize.sql';
    file_put_contents($sanitize_file, 'DELETE FROM cache;');

    $this->envSetMultiple([
      'VORTEX_PROVISION_SANITIZE_DB_EMAIL' => 'test+%uid@example.com',
      'VORTEX_PROVISION_SANITIZE_DB_PASSWORD' => 'fullpass',
      'VORTEX_PROVISION_SANITIZE_DB_REPLACE_USERNAME_WITH_EMAIL' => '1',
      'VORTEX_PROVISION_SANITIZE_DB_ADDITIONAL_FILE' => $sanitize_file,
      'DRUPAL_ADMIN_EMAIL' => 'admin@fulltest.com',
    ]);

    // Drush sql:sanitize.
    $this->mockPassthru([
      'cmd' => "./vendor/bin/drush -y sql:sanitize --sanitize-password='fullpass' --sanitize-email='test+%uid@example.com'",
      'result_code' => 0,
    ]);

    // Replace username with email.
    $this->mockPassthru([
      'cmd' => "./vendor/bin/drush -y sql:query \"UPDATE \\`users_field_data\\` set users_field_data.name=users_field_data.mail WHERE uid <> '0';\"",
      'result_code' => 0,
    ]);

    // Additional SQL file.
    $this->mockPassthru([
      'cmd' => './vendor/bin/drush -y sql:query --file=' . escapeshellarg($sanitize_file),
      'result_code' => 0,
    ]);

    // Reset user 0 mail and name.
    $this->mockPassthru([
      'cmd' => "./vendor/bin/drush -y sql:query \"UPDATE \\`users_field_data\\` SET mail = '', name = '' WHERE uid = '0';\"",
      'result_code' => 0,
    ]);

    // Reset user 0 name.
    $this->mockPassthru([
      'cmd' => "./vendor/bin/drush -y sql:query \"UPDATE \\`users_field_data\\` SET name = '' WHERE uid = '0';\"",
      'result_code' => 0,
    ]);

    // Update user 1 email.
    $this->mockPassthru([
      'cmd' => "./vendor/bin/drush -y sql:query \"UPDATE \\`users_field_data\\` SET mail = 'admin@fulltest.com' WHERE uid = '1';\"",
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/provision-sanitize-db');

    $this->assertStringContainsString('Sanitized database using drush sql:sanitize.', $output);
    $this->assertStringContainsString('Updated username with user email.', $output);
    $this->assertStringContainsString('Applied custom sanitization commands from file.', $output);
    $this->assertStringContainsString('Reset user 0 username and email.', $output);
    $this->assertStringContainsString('Updated user 1 email.', $output);
  }

}
