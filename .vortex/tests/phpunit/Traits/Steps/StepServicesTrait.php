<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Traits\Steps;

use AlexSkrypnyk\File\File;
use DrevOps\Vortex\Tests\Traits\LoggerTrait;

/**
 * Provides service testing steps (Solr, Redis).
 */
trait StepServicesTrait {

  use LoggerTrait;

  protected function stepSolr(): void {
    $this->logStepStart();

    $this->logSubstep('Testing Solr service connectivity');
    $this->processRun('ahoy cli curl -s "http://solr:8983/solr/drupal/select?q=*:*&rows=0&wt=json"');
    $this->assertProcessSuccessful();
    $this->assertProcessOutputContains('response');

    $this->logStepFinish();
  }

  protected function stepRedis(): void {
    $this->logStepStart();

    $this->logSubstep('Redis service is running');
    $this->processRun('ahoy flush-redis');
    $this->assertProcessOutputContains('OK');

    $this->logSubstep('Disable Redis integration');
    $this->addVarToFile('.env', 'DRUPAL_REDIS_ENABLED', '0');
    $this->syncToContainer();

    $this->processRun('ahoy up');
    $this->assertProcessSuccessful();
    sleep(10);
    $this->processRun('ahoy flush-redis');

    $this->logSubstep('Assert that Redis integration is working');
    $this->processRun('ahoy drush cr');
    $this->processRun('ahoy cli curl -L -s "http://nginx:8080" >/dev/null');
    $this->processRun('docker compose exec redis redis-cli --scan');
    $this->assertProcessOutputNotContains('config');

    $this->logSubstep('Assert that Redis is not connected in Drupal');
    $this->processRun('docker compose exec cli drush core:requirements --filter="title~=#(Redis)#i" --field=severity');
    $this->assertProcessOutputContains('Warning');

    $this->restoreFile('.env');
    $this->syncToContainer();

    $this->logSubstep('Enable Redis integration');
    $this->addVarToFile('.env', 'DRUPAL_REDIS_ENABLED', '1');
    $this->syncToContainer();

    $this->processRun('ahoy up');
    $this->assertProcessSuccessful();
    sleep(10);
    $this->processRun('ahoy flush-redis');

    $this->logSubstep('Assert that Redis integration is working');
    $this->processRun('ahoy drush cr');
    $this->processRun('ahoy cli curl -L -s "http://nginx:8080" >/dev/null');
    $this->processRun('docker compose exec redis redis-cli --scan');
    $this->assertProcessOutputContains('config');

    $this->logSubstep('Assert that Redis is connected in Drupal');
    $this->processRun('docker compose exec cli drush core:requirements --filter="title~=#(Redis)#i" --field=severity');
    $this->assertProcessOutputContains('OK');

    $this->logSubstep('Cleanup after test');
    $this->restoreFile('.env');
    $this->syncToContainer();
    $this->processRun('ahoy up cli');
    $this->assertProcessSuccessful();

    $this->logStepFinish();
  }

}
