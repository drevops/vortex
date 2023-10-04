<?php

namespace Drevops\Installer\Tests\Unit\Utils;

use Drevops\Installer\Tests\Unit\UnitTestBase;
use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\Token;

/**
 * @coversDefaultClass \Drevops\Installer\Utils\Token
 */
class TokenTest extends UnitTestBase {

  /**
   * @covers ::getConstants
   */
  public function testGetConstants() {
    $constants = Token::getConstants();

    $this->assertIsArray($constants);
    $this->assertContains(Token::COMMENTED_CODE, $constants);
  }

}
