<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\DataProvider;
use DrevOps\VortexInstaller\Utils\Env;

/**
 * Class InstallerDotEnvTest.
 *
 * InstallerDotEnvTest fixture class.
 */
#[CoversClass(Env::class)]
#[RunTestsInSeparateProcesses]
class EnvTest extends UnitTestCase {

  /**
   * Backup value of the $GLOBALS['_SERVER'] variable.
   *
   * @var array
   */
  protected $backupServer;

  /**
   * Backup value of the $GLOBALS['_ENV'] variable.
   *
   * @var array
   */
  protected $backupEnv;

  protected function setUp(): void {
    $this->backupEnv = $GLOBALS['_ENV'];
    $this->backupServer = $GLOBALS['_SERVER'];

    parent::setUp();
  }

  protected function tearDown(): void {
    $GLOBALS['_ENV'] = $this->backupEnv;
    $GLOBALS['_SERVER'] = $this->backupServer;

    parent::tearDown();
  }

  #[DataProvider('dataProviderGet')]
  public function testGet(string $name, string $value, ?string $default, ?string $expected): void {
    putenv(sprintf('%s=%s', $name, $expected));

    $this->assertSame($expected, Env::get($name, $default));

    putenv($name);
  }

  public static function dataProviderGet(): array {
    return [
      ['VAR', 'VAL1', 'DEF1', 'VAL1'],
      ['VAR', 'VAL1', NULL, 'VAL1'],
      ['VAR', 'VAL1', 'VAL2', 'VAL1'],
      ['VAR', '', 'DEF1', 'DEF1'],
      ['VAR', '', NULL, ''],
      ['VAR', '', 'VAL2', 'VAL2'],
    ];
  }

  #[DataProvider('dataProviderGetFromDotenv')]
  public function testGetFromDotenv(string $name, ?string $value, ?string $value_dotenv, ?string $expected): void {
    putenv(sprintf('%s=%s', $name, $expected));

    $content = '';
    if ($value_dotenv) {
      $content = sprintf('%s=%s', $name, $value_dotenv);
    }

    $filename = $this->createFixtureEnvFile($content);

    $actual = Env::getFromDotenv($name, dirname($filename));
    $this->assertEquals($expected, $actual);

    putenv($name);
  }

  public static function dataProviderGetFromDotenv(): array {
    return [
      ['VAR', 'VAL1', NULL, 'VAL1'],
      ['VAR', 'VAL1', 'VALDOTENV1', 'VAL1'],
      ['VAR', NULL, 'VALDOTENV1', 'VALDOTENV1'],
      ['VAR', NULL, NULL, NULL],
    ];
  }

  #[DataProvider('dataProviderPutFromDotenv')]
  public function testPutFromDotenv(string $name, ?string $value, ?string $value_dotenv, bool $override_existing, ?string $expected): void {
    if ($value) {
      putenv(sprintf('%s=%s', $name, $value));
      $GLOBALS['_ENV'][$name] = $value;
      $GLOBALS['_SERVER'][$name] = $value;
    }

    $content = '';
    if ($value_dotenv) {
      $content = sprintf('%s=%s', $name, $value_dotenv);
    }
    $filename = $this->createFixtureEnvFile($content);

    Env::putFromDotenv($filename, $override_existing);

    $this->assertEquals($expected, getenv($name));
    $this->assertEquals($GLOBALS['_ENV'][$name], $expected);
    $this->assertEquals($GLOBALS['_SERVER'][$name], $expected);
  }

  public static function dataProviderPutFromDotenv(): array {
    return [
      ['VAR', 'VAL1', NULL, FALSE, 'VAL1'],
      ['VAR', 'VAL1', 'VALDOTENV1', FALSE, 'VAL1'],
      ['VAR', NULL, 'VALDOTENV1', FALSE, 'VALDOTENV1'],
      ['VAR', NULL, NULL, FALSE, NULL],

      ['VAR', 'VAL1', NULL, TRUE, 'VAL1'],
      ['VAR', 'VAL1', 'VALDOTENV1', TRUE, 'VALDOTENV1'],
      ['VAR', NULL, 'VALDOTENV1', TRUE, 'VALDOTENV1'],
      ['VAR', NULL, NULL, TRUE, NULL],
    ];
  }

  public function testWriteValueDotenv(): void {
    $fixture_dir = __DIR__ . '/Fixtures/env';
    $actual_file = static::$sut . '/.env';
    copy($fixture_dir . '/_baseline/.env', $actual_file);

    // Apply updates to every variable to transform it to the after state.
    Env::writeValueDotenv('SIMPLE_VAR', 'new_simple_value', $actual_file);
    Env::writeValueDotenv('QUOTED_VAR', 'new value with spaces', $actual_file);
    Env::writeValueDotenv('EMPTY_VAR', '', $actual_file);
    Env::writeValueDotenv('QUOTED_CONTENT_VAR', 'new value with "quotes"', $actual_file);
    Env::writeValueDotenv('EQUALS_VAR', 'new key=value', $actual_file);
    Env::writeValueDotenv('SPECIAL_VAR', 'new !@#$%^&*()', $actual_file);
    Env::writeValueDotenv('MULTILINE_VAR', "new line1\nline2", $actual_file);
    Env::writeValueDotenv('URL_VAR', 'new https://example.com/path?param=value', $actual_file);
    Env::writeValueDotenv('SPACE_VAR', ' new leading and trailing ', $actual_file);
    Env::writeValueDotenv('NUMERIC_VAR', 'new_123', $actual_file);
    Env::writeValueDotenv('BOOL_VAR', 'false', $actual_file);
    Env::writeValueDotenv('PATH_VAR', '/path/to/new file', $actual_file);
    Env::writeValueDotenv('EMAIL_VAR', 'new user@domain.com', $actual_file);
    // Remove this variable.
    Env::writeValueDotenv('REMOVE_VAR', NULL, $actual_file);
    // Add new variable.
    Env::writeValueDotenv('NEW_VAR', 'new_added_value', $actual_file);

    $this->assertDirectoryEqualsDirectory($fixture_dir . '/after', static::$sut);
  }

  #[DataProvider('dataProviderFormatValueForDotenv')]
  public function testFormatValueForDotenv(string $input, string $expected): void {
    $reflection = new \ReflectionClass(Env::class);
    $method = $reflection->getMethod('formatValueForDotenv');
    $method->setAccessible(TRUE);

    $result = $method->invoke(NULL, $input);
    $this->assertEquals($expected, $result);
  }

  public static function dataProviderFormatValueForDotenv(): array {
    return [
      // Values without special characters or whitespace - should not be quoted.
      ['simple_value', 'simple_value'],
      ['123', '123'],
      ['true', 'true'],
      ['path/to/file', 'path/to/file'],
      ['with-dashes', 'with-dashes'],
      ['with_underscores', 'with_underscores'],
      ['UPPERCASE', 'UPPERCASE'],
      ['mixedCase', 'mixedCase'],
      ['email@domain.com', 'email@domain.com'],
      ['https://example.com', 'https://example.com'],
      ['', ''],

      // Values with whitespace - should be quoted.
      ['value with spaces', '"value with spaces"'],
      [' leading space', '" leading space"'],
      ['trailing space ', '"trailing space "'],
      [' both spaces ', '" both spaces "'],
      ['multiple   spaces', '"multiple   spaces"'],
      ["tab\tcharacter", "\"tab\tcharacter\""],
      ["new\nline", "\"new\nline\""],
      ['path with spaces/to/file', '"path with spaces/to/file"'],
      ['sentence with multiple words', '"sentence with multiple words"'],

      // Values with shell special characters - should be quoted.
      ['value#comment', '"value#comment"'],
      ['value$variable', '"value$variable"'],
      ['value!history', '"value!history"'],
      ['command;another', '"command;another"'],
      ['background&process', '"background&process"'],
      ['pipe|value', '"pipe|value"'],
      ['redirect>output', '"redirect>output"'],
      ['input<file', '"input<file"'],
      ['glob*pattern', '"glob*pattern"'],
      ['wildcard?match', '"wildcard?match"'],
      ['group(content)', '"group(content)"'],
      ['expand{a,b}', '"expand{a,b}"'],
      ['array[index]', '"array[index]"'],
      ['command`substitution', '"command`substitution"'],
      ["single'quote", '"single\'quote"'],
      ['double"quote', '"double\\"quote"'],

      // Combined cases (whitespace + special characters).
      ['value with "quotes"', '"value with \\"quotes\\""'],
      ['email|name with spaces', '"email|name with spaces"'],
      ['command; with spaces', '"command; with spaces"'],
      ['path with spaces & special', '"path with spaces & special"'],

      // Edge cases.
    // = is not a special character, so no quoting needed.
      ['key=value', 'key=value'],
      ['webmaster@your-site-domain.example|Webmaster', '"webmaster@your-site-domain.example|Webmaster"'],
    ];
  }

  #[DataProvider('dataProviderParseDotenv')]
  public function testParseDotenv(string $content, ?array $expected, ?string $exception_message): void {
    $filename = $this->createFixtureEnvFile($content);

    if ($exception_message) {
      $this->expectException(\RuntimeException::class);
      $this->expectExceptionMessageMatches('/' . preg_quote($exception_message, '/') . '/');
    }

    $result = Env::parseDotenv($filename);

    if (!$exception_message) {
      $this->assertEquals($expected, $result);
    }

    unlink($filename);
  }

  public static function dataProviderParseDotenv(): array {
    return [
      // Valid .env content.
      ['VAR1=value1', ['VAR1' => 'value1'], NULL],
      ["VAR1=value1\nVAR2=value2", ['VAR1' => 'value1', 'VAR2' => 'value2'], NULL],
      ['VAR="quoted value"', ['VAR' => 'quoted value'], NULL],
      ['VAR=', ['VAR' => ''], NULL],
      ['', [], NULL],

      // Valid content with comments.
      ["VAR1=value1\n# This is a comment\nVAR2=value2", ['VAR1' => 'value1', 'VAR2' => 'value2'], NULL],
      ['VAR="value with # in quotes"', ['VAR' => 'value with # in quotes'], NULL],

      // Invalid .env content that should throw exceptions.
      ['VAR[invalid', NULL, 'Unable to parse file'],
      ['VAR=value1' . "\n" . 'INVALID[bracket', NULL, 'Unable to parse file'],
      ["VAR1=value1\nVAR2[invalid=value2", NULL, 'Unable to parse file'],
    ];
  }

  public function testParseDotenvFileNotReadable(): void {
    $result = Env::parseDotenv('/nonexistent/file.env');
    $this->assertEquals([], $result);
  }

  public function testParseDotenvFileReadFailure(): void {
    // Create a file we can't read.
    $filename = $this->createFixtureEnvFile('VAR=value');
    chmod($filename, 0000);

    $result = Env::parseDotenv($filename);
    $this->assertEquals([], $result);

    // Clean up.
    chmod($filename, 0644);
    unlink($filename);
  }

  #[DataProvider('dataProviderToValue')]
  public function testToValue(string $input, mixed $expected): void {
    $result = Env::toValue($input);
    $this->assertSame($expected, $result);
  }

  public static function dataProviderToValue(): array {
    return [
      // String constants.
      ['true', TRUE],
      ['false', FALSE],
      ['null', NULL],

      // Numeric values.
      ['123', 123],
      ['0', 0],
      ['-456', -456],

      // Regular strings.
      ['regular_string', 'regular_string'],
      ['non-numeric', 'non-numeric'],

      // List values (contains comma).
      ['item1,item2,item3', ['item1', 'item2', 'item3']],
      ['single,item', ['single', 'item']],
    ];
  }

  public function testPut(): void {
    $name = 'TEST_PUT_VAR';
    $value = 'test_value';

    Env::put($name, $value);

    $this->assertEquals($value, getenv($name));

    putenv($name);
  }

  public function testGetFromDotenvFileNotReadable(): void {
    $result = Env::getFromDotenv('SOME_VAR', '/nonexistent/directory');
    $this->assertNull($result);
  }

  public function testGetFromDotenvReturnsParsedValue(): void {
    // Test the case when environment variable is not set but .env file exists.
    $content = "TEST_VAR=dotenv_value";
    $filename = $this->createFixtureEnvFile($content);
    $dir = dirname($filename);

    // Move the temp file to be named .env in the directory.
    $dotenv_file = $dir . '/.env';
    rename($filename, $dotenv_file);

    // Ensure no environment variable is set by clearing any existing value.
    if (getenv('TEST_VAR') !== FALSE) {
      putenv('TEST_VAR');
    }

    $result = Env::getFromDotenv('TEST_VAR', $dir);
    $this->assertEquals('dotenv_value', $result);

    unlink($dotenv_file);
  }

  public function testWriteValueDotenvFileNotReadable(): void {
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('File /nonexistent/file.env is not readable.');

    Env::writeValueDotenv('TEST_VAR', 'value', '/nonexistent/file.env');
  }

  public function testWriteValueDotenvFileReadFailure(): void {
    // Create a file we can't read.
    $filename = $this->createFixtureEnvFile('VAR=value');
    chmod($filename, 0000);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage(sprintf('File %s is not readable.', $filename));

    try {
      Env::writeValueDotenv('TEST_VAR', 'value', $filename);
    }
    finally {
      // Clean up.
      chmod($filename, 0644);
      unlink($filename);
    }
  }

  public function testWriteValueDotenvAddNewVariableToFileWithoutNewline(): void {
    // No trailing newline.
    $filename = $this->createFixtureEnvFile('EXISTING_VAR=value');

    Env::writeValueDotenv('NEW_VAR', 'new_value', $filename);

    $content = file_get_contents($filename);
    $expected = "EXISTING_VAR=value\nNEW_VAR=new_value\n";
    $this->assertEquals($expected, $content);

    unlink($filename);
  }

  public function testWriteValueDotenvReplaceVariableToFileWithoutNewline(): void {
    // No trailing newline.
    $filename = $this->createFixtureEnvFile('EXISTING_VAR=old_value');

    Env::writeValueDotenv('NEW_VAR', 'new value with spaces', $filename);

    $content = file_get_contents($filename);
    $expected = "EXISTING_VAR=old_value\nNEW_VAR=\"new value with spaces\"\n";
    $this->assertEquals($expected, $content);

    unlink($filename);
  }

  public function testWriteValueDotenvAddEmptyVariable(): void {
    $filename = $this->createFixtureEnvFile("EXISTING_VAR=value\n");

    // Test adding a variable that doesn't exist with null value.
    Env::writeValueDotenv('NEW_VAR', NULL, $filename);

    $content = file_get_contents($filename);
    $expected = "EXISTING_VAR=value\nNEW_VAR=\n";
    $this->assertEquals($expected, $content);

    unlink($filename);
  }

  public function testWriteValueDotenvAddEmptyVariableToFileWithoutNewline(): void {
    // No trailing newline.
    $filename = $this->createFixtureEnvFile('EXISTING_VAR=value');

    // Test adding a variable that doesn't exist with null value to a file
    // without newline.
    Env::writeValueDotenv('NEW_VAR', NULL, $filename);

    $content = file_get_contents($filename);
    $expected = "EXISTING_VAR=value\nNEW_VAR=\n";
    $this->assertEquals($expected, $content);

    unlink($filename);
  }

  public function testParseDotenvFileGetContentsFailure(): void {
    // Create a directory instead of a file (will cause file_get_contents
    // to fail).
    $dirname = tempnam(sys_get_temp_dir(), '.env');
    unlink($dirname);
    mkdir($dirname);

    $result = Env::parseDotenv($dirname);
    $this->assertEquals([], $result);

    rmdir($dirname);
  }

  protected function createFixtureEnvFile(string $content): string {
    $filename = tempnam(sys_get_temp_dir(), '.env');

    if ($filename === FALSE) {
      throw new \RuntimeException('Failed to create temporary file.');
    }

    if (file_put_contents($filename, $content) === FALSE) {
      throw new \RuntimeException('Failed to write to temporary file.');
    }

    return $filename;
  }

}
