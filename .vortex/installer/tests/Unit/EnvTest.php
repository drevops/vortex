<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\DataProvider;
use DrevOps\VortexInstaller\Utils\Env;
use DrevOps\VortexInstaller\Utils\File;

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
    static::envSet($name, $expected);

    $this->assertSame($expected, Env::get($name, $default));
  }

  public static function dataProviderGet(): \Iterator {
    yield ['VAR', 'VAL1', 'DEF1', 'VAL1'];
    yield ['VAR', 'VAL1', NULL, 'VAL1'];
    yield ['VAR', 'VAL1', 'VAL2', 'VAL1'];
    yield ['VAR', '', 'DEF1', 'DEF1'];
    yield ['VAR', '', NULL, ''];
    yield ['VAR', '', 'VAL2', 'VAL2'];
  }

  #[DataProvider('dataProviderGetFromDotenv')]
  public function testGetFromDotenv(string $name, ?string $value, ?string $value_dotenv, ?string $expected): void {
    if ($expected !== NULL) {
      static::envSet($name, $expected);
    }
    else {
      static::envUnset($name);
    }

    $content = '';
    if ($value_dotenv) {
      $content = sprintf('%s=%s', $name, $value_dotenv);
    }

    $filename = $this->createFixtureEnvFile($content);

    $actual = Env::getFromDotenv($name, dirname($filename));
    $this->assertEquals($expected, $actual);
  }

  public static function dataProviderGetFromDotenv(): \Iterator {
    yield ['VAR', 'VAL1', NULL, 'VAL1'];
    yield ['VAR', 'VAL1', 'VALDOTENV1', 'VAL1'];
    yield ['VAR', NULL, 'VALDOTENV1', 'VALDOTENV1'];
    yield ['VAR', NULL, NULL, NULL];
  }

  #[DataProvider('dataProviderPutFromDotenv')]
  public function testPutFromDotenv(string $name, ?string $value, ?string $value_dotenv, bool $override_existing, ?string $expected): void {
    if ($value) {
      static::envSet($name, $value);
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

  public static function dataProviderPutFromDotenv(): \Iterator {
    yield ['VAR', 'VAL1', NULL, FALSE, 'VAL1'];
    yield ['VAR', 'VAL1', 'VALDOTENV1', FALSE, 'VAL1'];
    yield ['VAR', NULL, 'VALDOTENV1', FALSE, 'VALDOTENV1'];
    yield ['VAR', NULL, NULL, FALSE, NULL];
    yield ['VAR', 'VAL1', NULL, TRUE, 'VAL1'];
    yield ['VAR', 'VAL1', 'VALDOTENV1', TRUE, 'VALDOTENV1'];
    yield ['VAR', NULL, 'VALDOTENV1', TRUE, 'VALDOTENV1'];
    yield ['VAR', NULL, NULL, TRUE, NULL];
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

    $this->assertDirectoriesIdentical(static::$sut, $fixture_dir . '/after');
  }

  #[DataProvider('dataProviderFormatValueForDotenv')]
  public function testFormatValueForDotenv(string $input, string $expected): void {
    $reflection = new \ReflectionClass(Env::class);
    $method = $reflection->getMethod('formatValueForDotenv');

    $result = $method->invoke(NULL, $input);
    $this->assertEquals($expected, $result);
  }

  public static function dataProviderFormatValueForDotenv(): \Iterator {
    // Values without special characters or whitespace - should not be quoted.
    yield ['simple_value', 'simple_value'];
    yield ['123', '123'];
    yield ['true', 'true'];
    yield ['path/to/file', 'path/to/file'];
    yield ['with-dashes', 'with-dashes'];
    yield ['with_underscores', 'with_underscores'];
    yield ['UPPERCASE', 'UPPERCASE'];
    yield ['mixedCase', 'mixedCase'];
    yield ['email@domain.com', 'email@domain.com'];
    yield ['https://example.com', 'https://example.com'];
    yield ['', ''];
    // Values with whitespace - should be quoted.
    yield ['value with spaces', '"value with spaces"'];
    yield [' leading space', '" leading space"'];
    yield ['trailing space ', '"trailing space "'];
    yield [' both spaces ', '" both spaces "'];
    yield ['multiple   spaces', '"multiple   spaces"'];
    yield ["tab\tcharacter", "\"tab\tcharacter\""];
    yield ["new\nline", "\"new\nline\""];
    yield ['path with spaces/to/file', '"path with spaces/to/file"'];
    yield ['sentence with multiple words', '"sentence with multiple words"'];
    // Values with shell special characters - should be quoted.
    yield ['value#comment', '"value#comment"'];
    yield ['value$variable', '"value$variable"'];
    yield ['value!history', '"value!history"'];
    yield ['command;another', '"command;another"'];
    yield ['background&process', '"background&process"'];
    yield ['pipe|value', '"pipe|value"'];
    yield ['redirect>output', '"redirect>output"'];
    yield ['input<file', '"input<file"'];
    yield ['glob*pattern', '"glob*pattern"'];
    yield ['wildcard?match', '"wildcard?match"'];
    yield ['group(content)', '"group(content)"'];
    yield ['expand{a,b}', '"expand{a,b}"'];
    yield ['array[index]', '"array[index]"'];
    yield ['command`substitution', '"command`substitution"'];
    yield ["single'quote", '"single\'quote"'];
    yield ['double"quote', '"double\\"quote"'];
    // Combined cases (whitespace + special characters).
    yield ['value with "quotes"', '"value with \\"quotes\\""'];
    yield ['email|name with spaces', '"email|name with spaces"'];
    yield ['command; with spaces', '"command; with spaces"'];
    yield ['path with spaces & special', '"path with spaces & special"'];
    // Edge cases.
    // = is not a special character, so no quoting needed.
    yield ['key=value', 'key=value'];
    yield ['webmaster@your-site-domain.example|Webmaster', '"webmaster@your-site-domain.example|Webmaster"'];
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

    File::remove($filename);
  }

  public static function dataProviderParseDotenv(): \Iterator {
    // Valid .env content.
    yield ['VAR1=value1', ['VAR1' => 'value1'], NULL];
    yield ["VAR1=value1\nVAR2=value2", ['VAR1' => 'value1', 'VAR2' => 'value2'], NULL];
    yield ['VAR="quoted value"', ['VAR' => 'quoted value'], NULL];
    yield ['VAR=', ['VAR' => ''], NULL];
    yield ['', [], NULL];
    // Valid content with comments.
    yield ["VAR1=value1\n# This is a comment\nVAR2=value2", ['VAR1' => 'value1', 'VAR2' => 'value2'], NULL];
    yield ['VAR="value with # in quotes"', ['VAR' => 'value with # in quotes'], NULL];
    // Invalid .env content that should throw exceptions.
    yield ['VAR[invalid', NULL, 'Unable to parse file'];
    yield ['VAR=value1' . "\n" . 'INVALID[bracket', NULL, 'Unable to parse file'];
    yield ["VAR1=value1\nVAR2[invalid=value2", NULL, 'Unable to parse file'];
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
    File::remove($filename);
  }

  #[DataProvider('dataProviderToValue')]
  public function testToValue(string $input, mixed $expected): void {
    $result = Env::toValue($input);
    $this->assertSame($expected, $result);
  }

  public static function dataProviderToValue(): \Iterator {
    // String constants.
    yield ['true', TRUE];
    yield ['false', FALSE];
    yield ['null', NULL];
    // Numeric values.
    yield ['123', 123];
    yield ['0', 0];
    yield ['-456', -456];
    // Regular strings.
    yield ['regular_string', 'regular_string'];
    yield ['non-numeric', 'non-numeric'];
    // List values (contains comma).
    yield ['item1,item2,item3', ['item1', 'item2', 'item3']];
    yield ['single,item', ['single', 'item']];
  }

  public function testPut(): void {
    $name = 'TEST_PUT_VAR';
    $value = 'test_value';

    Env::put($name, $value);

    $this->assertEquals($value, getenv($name));
    static::envUnset($name);
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
    static::envUnset('TEST_VAR');

    $result = Env::getFromDotenv('TEST_VAR', $dir);
    $this->assertEquals('dotenv_value', $result);

    File::remove($dotenv_file);
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
      File::remove($filename);
    }
  }

  public function testWriteValueDotenvAddNewVariableToFileWithoutNewline(): void {
    // No trailing newline.
    $filename = $this->createFixtureEnvFile('EXISTING_VAR=value');

    Env::writeValueDotenv('NEW_VAR', 'new_value', $filename);

    $content = file_get_contents($filename);
    $expected = "EXISTING_VAR=value\nNEW_VAR=new_value\n";
    $this->assertEquals($expected, $content);

    File::remove($filename);
  }

  public function testWriteValueDotenvReplaceVariableToFileWithoutNewline(): void {
    // No trailing newline.
    $filename = $this->createFixtureEnvFile('EXISTING_VAR=old_value');

    Env::writeValueDotenv('NEW_VAR', 'new value with spaces', $filename);

    $content = file_get_contents($filename);
    $expected = "EXISTING_VAR=old_value\nNEW_VAR=\"new value with spaces\"\n";
    $this->assertEquals($expected, $content);

    File::remove($filename);
  }

  public function testWriteValueDotenvAddEmptyVariable(): void {
    $filename = $this->createFixtureEnvFile("EXISTING_VAR=value\n");

    // Test adding a variable that doesn't exist with null value.
    Env::writeValueDotenv('NEW_VAR', NULL, $filename);

    $content = file_get_contents($filename);
    $expected = "EXISTING_VAR=value\nNEW_VAR=\n";
    $this->assertEquals($expected, $content);

    File::remove($filename);
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

    File::remove($filename);
  }

  #[DataProvider('dataProviderWriteValueDotenvWithEnabled')]
  public function testWriteValueDotenvWithEnabled(string $initial_content, string $name, ?string $value, bool $enabled, string $expected_content): void {
    $filename = $this->createFixtureEnvFile($initial_content);

    Env::writeValueDotenv($name, $value, $filename, $enabled);

    $content = file_get_contents($filename);
    $this->assertEquals($expected_content, $content);

    File::remove($filename);
  }

  public static function dataProviderWriteValueDotenvWithEnabled(): \Iterator {
    // Test commenting out an active variable.
    yield 'disable active variable' => [
      "VAR=active_value\n",
      'VAR',
      'active_value',
      FALSE,
      "# VAR=active_value\n",
    ];
    // Test activating a commented variable.
    yield 'enable commented variable' => [
      "# VAR=commented_value\n",
      'VAR',
      'new_value',
      TRUE,
      "VAR=new_value\n",
    ];
    // Test updating and commenting out an active variable.
    yield 'disable and update active variable' => [
      "VAR=old_value\n",
      'VAR',
      'new_value',
      FALSE,
      "# VAR=new_value\n",
    ];
    // Test updating and activating a commented variable.
    yield 'enable and update commented variable' => [
      "# VAR=old_value\n",
      'VAR',
      'new_value',
      TRUE,
      "VAR=new_value\n",
    ];
    // Test adding new disabled variable.
    yield 'add new disabled variable' => [
      "EXISTING=value\n",
      'NEW_VAR',
      'new_value',
      FALSE,
      "EXISTING=value\n# NEW_VAR=new_value\n",
    ];
    // Test adding new active variable (default behavior).
    yield 'add new active variable' => [
      "EXISTING=value\n",
      'NEW_VAR',
      'new_value',
      TRUE,
      "EXISTING=value\nNEW_VAR=new_value\n",
    ];
    // Test with commented variable with spaces after #.
    yield 'update variable commented with spaces' => [
      "#  VAR=old_value\n",
      'VAR',
      'new_value',
      TRUE,
      "VAR=new_value\n",
    ];
    // Test disabled with NULL value (empty).
    yield 'disabled empty variable' => [
      "EXISTING=value\n",
      'NEW_VAR',
      NULL,
      FALSE,
      "EXISTING=value\n# NEW_VAR=\n",
    ];
    // Test active with NULL value (empty).
    yield 'active empty variable' => [
      "EXISTING=value\n",
      'NEW_VAR',
      NULL,
      TRUE,
      "EXISTING=value\nNEW_VAR=\n",
    ];
    // Test disabling variable with special characters.
    yield 'disable variable with special chars' => [
      "VAR=value\n",
      'VAR',
      'value with spaces',
      FALSE,
      "# VAR=\"value with spaces\"\n",
    ];
    // Test enabling variable with special characters.
    yield 'enable variable with special chars' => [
      "# VAR=old\n",
      'VAR',
      'value with spaces',
      TRUE,
      "VAR=\"value with spaces\"\n",
    ];
    // Test with multiple variables, disable one.
    yield 'disable one among multiple variables' => [
      "VAR1=value1\nVAR2=value2\nVAR3=value3\n",
      'VAR2',
      'new_value2',
      FALSE,
      "VAR1=value1\n# VAR2=new_value2\nVAR3=value3\n",
    ];
    // Test with multiple variables, enable commented one.
    yield 'enable one among multiple variables' => [
      "VAR1=value1\n# VAR2=value2\nVAR3=value3\n",
      'VAR2',
      'new_value2',
      TRUE,
      "VAR1=value1\nVAR2=new_value2\nVAR3=value3\n",
    ];
  }

  public function testParseDotenvFileGetContentsFailure(): void {
    // Create a directory instead of a file (will cause file_get_contents
    // to fail).
    $dirname = tempnam(sys_get_temp_dir(), '.env');
    File::remove($dirname);
    mkdir($dirname);

    $result = Env::parseDotenv($dirname);
    $this->assertEquals([], $result);

    File::remove($dirname);
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
