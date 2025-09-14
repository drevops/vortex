<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Traits\Steps;

/**
 * Provides service testing steps (Solr, Redis).
 */
trait StepServicesTrait {

  protected function stepSolr(): void {
    $this->logStepStart();

    $this->logSubstep('Testing Solr service connectivity');
    $this->cmd('ahoy cli curl -s "http://solr:8983/solr/drupal/select?q=*:*&rows=0&wt=json"', 'response');

    $this->logStepFinish();
  }

  protected function stepRedis(): void {
    $this->logStepStart();

    $this->logSubstep('Redis service is running');
    $this->cmd('ahoy flush-redis', 'OK');

    $this->logSubstep('Disable Redis integration');
    $this->addVarToFile('.env', 'DRUPAL_REDIS_ENABLED', '0');
    $this->syncToContainer();

    $this->cmd('ahoy up');
    sleep(10);
    $this->cmd('ahoy flush-redis');

    $this->logSubstep('Assert that Redis integration is not working');
    $this->cmd('ahoy drush cr');
    $this->cmd('ahoy cli curl -L -s -f "http://nginx:8080" >/dev/null');
    $this->cmd('docker compose exec -T redis redis-cli --scan', '! config');

    $this->logSubstep('Assert that Redis is not connected in Drupal');
    $this->cmd('docker compose exec -T cli drush core:requirements --filter="title~=#(Redis)#i" --field=severity', 'Warning');

    $this->restoreFile('.env');
    $this->syncToContainer();

    $this->logSubstep('Enable Redis integration');
    $this->addVarToFile('.env', 'DRUPAL_REDIS_ENABLED', '1');
    $this->syncToContainer();

    $this->cmd('ahoy up');
    sleep(10);
    $this->cmd('ahoy flush-redis');

    $this->logSubstep('Assert that Redis integration is working');
    $this->cmd('ahoy drush cr');
    $this->cmd('ahoy cli curl -L -s -f "http://nginx:8080" >/dev/null');
    $this->cmd('docker compose exec -T redis redis-cli --scan', 'config');

    $this->logSubstep('Assert that Redis is connected in Drupal');
    $this->cmd('docker compose exec -T cli drush core:requirements --filter="title~=#(Redis)#i" --field=severity', 'OK');

    $this->logSubstep('Cleanup after test');
    $this->restoreFile('.env');
    $this->syncToContainer();
    $this->cmd('ahoy up cli');

    $this->logStepFinish();
  }

}
