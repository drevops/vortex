<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Functional;

use AlexSkrypnyk\File\File;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the Solr core lifecycle on a persistent volume.
 */
class DockerComposeSolrTest extends FunctionalTestCase {

  const SOLR_URL = 'http://localhost:8983/solr';

  const SOLR_DOC_ID = 'vortex-test-doc';

  protected function setUp(): void {
    parent::setUp();

    static::$sutInstallerEnv = [];
    static::$sutInstallerPrompts = [];

    $this->forceVolumesUnmounted();

    $this->dockerCleanup();
  }

  #[Group('p5')]
  public function testSolrCoreConfigUpdate(): void {
    $this->prepareSut();
    $this->solrStart();

    $this->logSubstep('Asserting the initial core config');
    $this->assertSame('2000', $this->solrMltTimeAllowed(), 'Core should load the config-set shipped in the image');

    $this->solrIndexDocument();

    $this->logSubstep('Changing the config-set and rolling out a new image on the same volume');
    File::replaceContentInFile('.docker/config/solr/config-set/solrcore.properties', 'solr.mlt.timeAllowed=2000', 'solr.mlt.timeAllowed=2345');
    $this->assertFileContainsString('.docker/config/solr/config-set/solrcore.properties', 'solr.mlt.timeAllowed=2345');
    $this->solrStart(recreate: TRUE);

    $this->logSubstep('Asserting the existing core uses the changed config-set');
    $this->assertSame('2345', $this->solrMltTimeAllowed(), 'Existing core should load the changed config-set');
    $this->assertSame(1, $this->solrDocumentCount(), 'Index should survive the core config refresh');

    $this->logSubstep('Asserting Solr is a direct child of the init process');
    $process = $this->cmd('docker compose exec -T solr ps -o ppid= -C java', txt: 'Find the parent of the Java process');
    $this->assertSame('1', trim($process->getOutput()), 'Java should be a direct child of tini to receive SIGTERM');
  }

  #[Group('p5')]
  public function testSolrCoreIncompatibleConfigReplaced(): void {
    $this->prepareSut();
    $this->solrStart();
    $this->solrIndexDocument();

    $this->logSubstep('Writing a Solr 8 response writer into the core config on the volume');
    $this->cmd('docker compose exec -T solr sed -i', arg: ['s#</config>#<queryResponseWriter name="xslt" class="solr.XSLTResponseWriter"/></config>#', '/var/solr/data/drupal/conf/solrconfig.xml'], txt: 'Inject Solr 8 config into the core on the volume');
    $this->cmd('docker compose exec -T solr grep -q solr.XSLTResponseWriter /var/solr/data/drupal/conf/solrconfig.xml', txt: 'Core config on the volume should contain the Solr 8 response writer');

    $this->logSubstep('Rolling out the same image on the same volume');
    $this->solrStart(recreate: TRUE);

    $this->logSubstep('Asserting the core loads with the config-set from the image');
    $this->assertSame(1, $this->solrDocumentCount(), 'Index should survive the core config refresh');
  }

  #[Group('p5')]
  public function testSolrBuildFailsWhenDataDirUnmatched(): void {
    $this->prepareSut();

    $this->logSubstep('Changing the dataDir in the config-set so the build no longer matches it');
    File::replaceContentInFile('.docker/config/solr/config-set/solrconfig.xml', '<dataDir>${solr.data.dir:}</dataDir>', '<dataDir>${solr.data.dir:/var/solr/data}</dataDir>');
    $this->assertFileContainsString('.docker/config/solr/config-set/solrconfig.xml', '<dataDir>${solr.data.dir:/var/solr/data}</dataDir>');

    $this->cmdFail('docker compose build solr', txt: 'Build should fail when the index location cannot be moved out of the core directory', tio: 600);
  }

  /**
   * Start the Solr service and wait for the 'drupal' core to load.
   *
   * @param bool $recreate
   *   Replace the running container, keeping the volume.
   */
  protected function solrStart(bool $recreate = FALSE): void {
    $this->logSubstep($recreate ? 'Recreating the Solr container' : 'Starting the Solr container');
    // The image tag is shared across SUTs, so build to avoid a stale image.
    $this->cmd('docker compose up -d --build solr' . ($recreate ? ' --force-recreate' : ''), txt: 'Start Solr container', tio: 600);

    $status = [];
    for ($i = 0; $i < 60; $i++) {
      $process = $this->processRun('docker compose exec -T solr curl -sf', [static::SOLR_URL . '/admin/cores?action=STATUS&core=drupal&wt=json']);
      $decoded = json_decode($process->getOutput(), TRUE);

      // A core that is still loading is listed without its 'index' details.
      if ($process->isSuccessful() && is_array($decoded) && (isset($decoded['initFailures']['drupal']) || isset($decoded['status']['drupal']['index']))) {
        $status = $decoded;
        break;
      }

      sleep(2);
    }

    $this->assertNotEmpty($status, 'Solr should finish loading the core');
    $this->assertArrayNotHasKey('drupal', (array) ($status['initFailures'] ?? []), 'Core should not fail to load: ' . json_encode($status['initFailures'] ?? []));
    $this->assertSame('drupal', $status['status']['drupal']['name'] ?? NULL, 'Core should be loaded');
  }

  protected function solrIndexDocument(): void {
    $this->logSubstep('Indexing a document');
    $this->cmd('docker compose exec -T solr curl -sf -H Content-Type:application/json --data-binary', arg: ['[{"id":"' . static::SOLR_DOC_ID . '"}]', static::SOLR_URL . '/drupal/update?commit=true'], txt: 'Index a document');
    $this->assertSame(1, $this->solrDocumentCount(), 'Indexed document should be searchable');
  }

  protected function solrDocumentCount(): int {
    $response = $this->solrGetJson('/drupal/select?q=id:' . static::SOLR_DOC_ID . '&rows=0&wt=json');

    return (int) ($response['response']['numFound'] ?? -1);
  }

  protected function solrMltTimeAllowed(): ?string {
    $response = $this->solrGetJson('/drupal/config/requestHandler?componentName=/mlt&wt=json');

    return $response['config']['requestHandler']['/mlt']['defaults']['timeAllowed'] ?? NULL;
  }

  protected function solrGetJson(string $path): array {
    $process = $this->cmd('docker compose exec -T solr curl -sf', arg: [static::SOLR_URL . $path]);
    $decoded = json_decode($process->getOutput(), TRUE);
    $this->assertIsArray($decoded, 'Solr should return JSON for ' . $path);

    return $decoded;
  }

}
