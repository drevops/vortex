<?php

declare(strict_types=1);

namespace DrevOps\Installer\Tests\Unit;

use DrevOps\Installer\Utils\Git;

/**
 * Class GitTest.
 *
 * GitTest fixture class.
 *
 * @coversDefaultClass \DrevOps\Installer\Utils\Git
 */
class GitTest extends UnitTestBase {

  /**
   * @dataProvider dataProviderExtractOwnerRepo
   * @covers ::extractOwnerRepo
   */
  public function testExtractOwnerRepo(string $uri, ?string $expected): void {
    $this->assertSame($expected, Git::extractOwnerRepo($uri));
  }

  public static function dataProviderExtractOwnerRepo(): array {
    return [
      ['git@github.com:owner/repo.git', 'owner/repo'],
      ['ssh://git@github.com/owner/repo.git', 'owner/repo'],
      ['https://github.com/owner/repo.git', 'owner/repo'],
      ['git://github.com/owner/repo.git', 'owner/repo'],
      ['https://github.com/owner/repo', 'owner/repo'],
      ['git@bitbucket.org:myteam/myproject.git', 'myteam/myproject'],
      ['ssh://git@gitlab.com/mygroup/myrepo.git', 'mygroup/myrepo'],
      ['https://gitlab.com/mygroup/myrepo.git', 'mygroup/myrepo'],
      ['file:///local/path/to/repo.git', NULL],
      ['/absolute/path/to/repo', NULL],
      ['invalid_string', NULL],
    ];
  }

}
