<?php

use Drupal\xautoload\Discovery\ClassMapGenerator;

require_once dirname(__DIR__) . '/xautoload.early.lib.inc';

_xautoload_register();

xautoload()->finder->addPsr4('Drupal\xautoload\Tests\\', __DIR__ . '/lib/');

// Use a non-cached class map generator.
xautoload()->getServiceContainer()->set('classMapGenerator', new ClassMapGenerator());