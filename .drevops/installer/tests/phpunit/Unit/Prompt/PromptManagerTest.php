<?php

namespace DrevOps\Installer\Tests\Unit\Prompt;

use DrevOps\Installer\Bag\Answers;
use DrevOps\Installer\Bag\Config;
use DrevOps\Installer\Prompt\Concrete\TestPrompt;
use DrevOps\Installer\Prompt\PromptManager;

/**
 * @coversDefaultClass \DrevOps\Installer\Prompt\PromptManager
 */
class PromptManagerTest extends PromptUnitTestCase {

  /**
   * @covers ::__construct
   */
  public function testConstructor(): void {
    Answers::getInstance()->fromValues(['test_question' => 'test_answer']);
    $config_before = Config::getInstance()->fromValues(['test' => 'value']);

    $manager = new PromptManager($this->io(), $config_before);

    $this->assertInstanceOf(PromptManager::class, $manager);
    $this->assertEquals(Answers::getInstance(), $manager->getAnswers());
  }

  /**
   * @covers ::askQuestions
   * @dataProvider dataProviderAskQuestions
   */
  public function testAskQuestions(mixed $callback, ?string $expectedException = NULL): void {
    if ($expectedException) {
      $this->expectException($expectedException);
    }

    $manager = new PromptManager($this->io(), Config::getInstance());
    $actual = $manager->askQuestions($callback);

    if (!$expectedException) {
      $this->assertInstanceOf(PromptManager::class, $actual);
    }
  }

  public static function dataProviderAskQuestions(): array {
    return [
      'valid callback' => [
        static function ($manager) : void {
        },
        NULL,
      ],
      'invalid callback not accessible' => [
        static function () {
            return (new \DrevOps\Installer\Tests\Unit\Prompt\PromptTestClassWithProtectedCallback())->callbackExample();
        },
        \RuntimeException::class,
      ],
      'invalid callback non existing' => [
        'string_callback',
        \RuntimeException::class,
      ],

    ];
  }

  /**
   * @covers ::ask
   * @covers ::getAnswers
   * @covers ::getAnswer
   * @covers ::setAnswer
   * @covers ::getPromptClass
   * @covers ::getAnswersSummary
   */
  public function testAskAnswers(): void {
    $manager = new PromptManager($this->io(), Config::getInstance());
    $actual = $manager->ask(TestPrompt::ID);
    $this->assertEquals('Fixture answer', $actual);
    $this->assertEquals('Fixture answer', $manager->getAnswers()->get(TestPrompt::ID));

    $manager->setAnswer(TestPrompt::ID, 'test_value');
    $this->assertEquals('test_value', $manager->getAnswer(TestPrompt::ID));

    $this->assertEquals('default answer', $manager->getAnswer('non_existing', 'default answer'));

    $summary = $manager->getAnswersSummary();
    $this->assertEquals(['Fixture title' => 'Fixture formatted value'], $summary);
  }

  /**
   * @covers ::getPromptClass
   */
  public function testGetPromptClassInvalid(): void {
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('The prompt class "DrevOps\Installer\Prompt\Concrete\InvalidIdPrompt" does not exist.');
    $manager = new PromptManager($this->io(), Config::getInstance());
    $this->callProtectedMethod($manager, 'getPromptClass', ['invalid_id']);
  }

}

/**
 *
 */
class PromptTestClassWithProtectedCallback {

  protected function callbackExample() {
  }

}
