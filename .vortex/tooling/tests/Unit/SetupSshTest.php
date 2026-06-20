<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use AlexSkrypnyk\File\File;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[Group('deploy')]
#[RunTestsInSeparateProcesses]
class SetupSshTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();

    // Unset all potential environment variables that could leak from other
    // tests.
    $this->envUnsetMultiple([
      'VORTEX_DEPLOY_SSH_FINGERPRINT',
      'VORTEX_DEPLOY_SSH_FILE',
      'VORTEX_SSH_REMOVE_ALL_KEYS',
      'VORTEX_SSH_DISABLE_STRICT_HOST_KEY_CHECKING',
      'SSH_AUTH_SOCK',
      'SSH_AGENT_PID',
    ]);

    $this->envSetMultiple([
      'VORTEX_SSH_PREFIX' => 'DEPLOY',
      'HOME' => self::$tmp,
    ]);
  }

  public function testMissingPrefix(): void {
    $this->envUnset('VORTEX_SSH_PREFIX');

    $this->runScriptError('src/setup-ssh', 'Missing required value for VORTEX_SSH_PREFIX');
  }

  public function testSshKeyDisabled(): void {
    $this->envSet('VORTEX_DEPLOY_SSH_FILE', 'false');

    $output = $this->runScript('src/setup-ssh', 0);

    $this->assertStringContainsString('SSH key is set to false meaning that it is not required. Skipping setup.', $output);
  }

  public function testBasicSetupWithDefaultFile(): void {
    // Create SSH key file.
    $ssh_dir = self::$tmp . '/.ssh';
    File::mkdir($ssh_dir, 0700);
    $key_file = $ssh_dir . '/id_rsa';
    File::dump($key_file, "fake ssh key\n");
    chmod($key_file, 0600);

    // Simulate agent running by setting SSH_AUTH_SOCK to an existing file.
    $socket_file = self::$tmp . '/agent.sock';
    File::dump($socket_file);
    $this->envSet('SSH_AUTH_SOCK', $socket_file);

    // Mock ssh-add -l - key is already loaded.
    $this->mockShellExec('id_rsa');

    $output = $this->runScript('src/setup-ssh');

    $this->assertStringContainsString('Started SSH setup.', $output);
    $this->assertStringContainsString('Using SSH key file ' . $key_file . '.', $output);
    $this->assertStringContainsString('SSH agent already has ' . $key_file . ' key loaded.', $output);
    $this->assertStringContainsString('Finished SSH setup.', $output);
  }

  public function testCustomFilePath(): void {
    $custom_file = self::$tmp . '/.ssh/custom_key';
    $this->envSet('VORTEX_DEPLOY_SSH_FILE', $custom_file);

    // Create SSH key file.
    File::mkdir(dirname($custom_file), 0700);
    File::dump($custom_file, "fake ssh key\n");
    chmod($custom_file, 0600);

    // Simulate agent running.
    $socket_file = self::$tmp . '/agent.sock';
    File::dump($socket_file);
    $this->envSet('SSH_AUTH_SOCK', $socket_file);

    // Mock ssh-add -l - key is already loaded.
    $this->mockShellExec('custom_key');

    $output = $this->runScript('src/setup-ssh');

    $this->assertStringContainsString('Using SSH key file ' . $custom_file . '.', $output);
  }

  public function testMissingKeyFile(): void {
    // Don't create the key file.
    $key_file = self::$tmp . '/.ssh/id_rsa';

    $this->runScriptError('src/setup-ssh', 'SSH key file ' . $key_file . ' does not exist.');
  }

  public function testStartSshAgent(): void {
    // Create SSH key file.
    $ssh_dir = self::$tmp . '/.ssh';
    File::mkdir($ssh_dir, 0700);
    $key_file = $ssh_dir . '/id_rsa';
    File::dump($key_file, "fake ssh key\n");
    chmod($key_file, 0600);

    // No SSH_AUTH_SOCK set = agent not running.
    // Mock shell commands.
    $this->mockShellExecMultiple([
      // ssh-agent output.
      ['value' => "SSH_AUTH_SOCK=/tmp/ssh-abc123/agent.123; export SSH_AUTH_SOCK;\nSSH_AGENT_PID=456; export SSH_AGENT_PID;\n"],
      // ssh-add -l (no keys loaded).
      ['value' => ''],
    ]);

    // Mock passthru for ssh-add commands.
    $this->mockPassthru([
      'cmd' => 'ssh-add ' . escapeshellarg($key_file),
      'output' => 'Identity added',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => 'ssh-add -l',
      'output' => '2048 SHA256:abc123 ' . $key_file . ' (RSA)',
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/setup-ssh');

    $this->assertStringContainsString('Starting SSH agent.', $output);
    $this->assertStringContainsString('SSH agent does not have a required key loaded.', $output);
  }

  public function testRemoveAllKeys(): void {
    $this->envSet('VORTEX_SSH_REMOVE_ALL_KEYS', '1');

    // Create SSH key file.
    $ssh_dir = self::$tmp . '/.ssh';
    File::mkdir($ssh_dir, 0700);
    $key_file = $ssh_dir . '/id_rsa';
    File::dump($key_file, "fake ssh key\n");
    chmod($key_file, 0600);

    // Simulate agent running.
    $socket_file = self::$tmp . '/agent.sock';
    File::dump($socket_file);
    $this->envSet('SSH_AUTH_SOCK', $socket_file);

    // Mock ssh-add -l (no keys).
    $this->mockShellExec('');

    // Mock passthru for removing and adding keys.
    $this->mockPassthru([
      'cmd' => 'ssh-add -D',
      'output' => 'All identities removed.',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => 'ssh-add ' . escapeshellarg($key_file),
      'output' => 'Identity added',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => 'ssh-add -l',
      'output' => '2048 SHA256:abc123 ' . $key_file . ' (RSA)',
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/setup-ssh');

    $this->assertStringContainsString('Removing all keys from the SSH agent.', $output);
  }

  public function testDisableStrictHostKeyChecking(): void {
    $this->envSet('VORTEX_SSH_DISABLE_STRICT_HOST_KEY_CHECKING', '1');

    // Create SSH key file.
    $ssh_dir = self::$tmp . '/.ssh';
    File::mkdir($ssh_dir, 0700);
    $key_file = $ssh_dir . '/id_rsa';
    File::dump($key_file, "fake ssh key\n");
    chmod($key_file, 0600);

    // Simulate agent running.
    $socket_file = self::$tmp . '/agent.sock';
    File::dump($socket_file);
    $this->envSet('SSH_AUTH_SOCK', $socket_file);

    // Mock ssh-add -l - key is already loaded.
    $this->mockShellExec('id_rsa');

    $output = $this->runScript('src/setup-ssh');

    $this->assertStringContainsString('Disabling strict host key checking.', $output);

    // Verify config file was created.
    $config_file = $ssh_dir . '/config';
    $this->assertFileExists($config_file);
    $config_content = File::read($config_file);
    $this->assertStringContainsString('StrictHostKeyChecking no', $config_content);
    $this->assertStringContainsString('UserKnownHostsFile /dev/null', $config_content);
  }

  public function testFingerprintBasedKeyMd5(): void {
    $fingerprint = '11:22:33:44:55:66:77:88:99:aa:bb:cc:dd:ee:ff:00';
    $this->envSet('VORTEX_DEPLOY_SSH_FINGERPRINT', $fingerprint);

    // Create SSH key file with fingerprint-based name.
    $ssh_dir = self::$tmp . '/.ssh';
    File::mkdir($ssh_dir, 0700);
    $expected_file = $ssh_dir . '/id_rsa_112233445566778899aabbccddeeff00';
    File::dump($expected_file, "fake ssh key\n");
    chmod($expected_file, 0600);

    // Simulate agent running.
    $socket_file = self::$tmp . '/agent.sock';
    File::dump($socket_file);
    $this->envSet('SSH_AUTH_SOCK', $socket_file);

    // Mock ssh-add -l - key is already loaded.
    $this->mockShellExec('id_rsa_112233445566778899aabbccddeeff00');

    $output = $this->runScript('src/setup-ssh');

    $this->assertStringContainsString('Using fingerprint-based deploy key', $output);
    $this->assertStringContainsString('Using SSH key file ' . $expected_file . '.', $output);
  }

  public function testSshAddFailure(): void {
    // Create SSH key file.
    $ssh_dir = self::$tmp . '/.ssh';
    File::mkdir($ssh_dir, 0700);
    $key_file = $ssh_dir . '/id_rsa';
    File::dump($key_file, "fake ssh key\n");
    chmod($key_file, 0600);

    // Simulate agent running.
    $socket_file = self::$tmp . '/agent.sock';
    File::dump($socket_file);
    $this->envSet('SSH_AUTH_SOCK', $socket_file);

    // Mock ssh-add -l (no keys).
    $this->mockShellExec('');

    // Mock ssh-add failure.
    $this->mockPassthru([
      'cmd' => 'ssh-add ' . escapeshellarg($key_file),
      'output' => 'Could not add identity: key invalid format',
      'result_code' => 1,
    ]);

    $this->runScriptError('src/setup-ssh', 'Failed to add SSH key to agent.');
  }

  public function testFingerprintBasedKeySha256(): void {
    $fingerprint_sha256 = 'SHA256:abcdefghijklmnopqrstuvwxyz123456789';
    $fingerprint_md5 = '11:22:33:44:55:66:77:88:99:aa:bb:cc:dd:ee:ff:00';
    $this->envSet('VORTEX_DEPLOY_SSH_FINGERPRINT', $fingerprint_sha256);

    // Create SSH key files.
    $ssh_dir = self::$tmp . '/.ssh';
    File::mkdir($ssh_dir, 0700);

    // Create expected file (found first by glob due to alphabetical order).
    $expected_file = $ssh_dir . '/id_rsa_112233445566778899aabbccddeeff00';
    File::dump($expected_file, "fake ssh key\n");
    chmod($expected_file, 0600);

    // Create another file (found second by glob).
    $other_file = $ssh_dir . '/id_rsa_other';
    File::dump($other_file, "fake ssh key\n");
    chmod($other_file, 0600);

    // Simulate agent running.
    $socket_file = self::$tmp . '/agent.sock';
    File::dump($socket_file);
    $this->envSet('SSH_AUTH_SOCK', $socket_file);

    // Mock shell commands.
    $this->mockShellExecMultiple([
      // ssh-keygen -l -E sha256 (first file matches).
      ['value' => '2048 ' . $fingerprint_sha256 . ' ' . $expected_file . ' (RSA)'],
      // ssh-keygen -l -E md5.
      ['value' => '2048 MD5:' . $fingerprint_md5 . ' ' . $expected_file . ' (RSA)'],
      // ssh-add -l - key is already loaded.
      ['value' => 'id_rsa_112233445566778899aabbccddeeff00'],
    ]);

    $output = $this->runScript('src/setup-ssh');

    $this->assertStringContainsString('Searching for MD5 hash as fingerprint starts with SHA256.', $output);
    $this->assertStringContainsString('Found matching existing key file ' . $expected_file . '.', $output);
    $this->assertStringContainsString('Using SSH key file ' . $expected_file . '.', $output);
  }

}
