<?php

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;

return static function (RectorConfig $rectorConfig): void {
  $rectorConfig->sets([
    SetList::TYPE_DECLARATION,
  ]);

  $rectorConfig->skip([
    // Dependencies.
    '*/vendor/*',
  ]);

  $rectorConfig->importNames(TRUE, FALSE);
  $rectorConfig->importShortClasses(FALSE);
};
