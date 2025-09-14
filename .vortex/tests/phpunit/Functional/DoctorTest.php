<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Functional;

class DoctorTest extends FunctionalTestCase {

  public function testInfo(): void {
    $this->cmd('ahoy doctor info', [
      'System information report',
      'OPERATING SYSTEM',
      'DOCKER',
      'DOCKER COMPOSE',
      'PYGMY',
      'AHOY',
    ]);
  }

}
