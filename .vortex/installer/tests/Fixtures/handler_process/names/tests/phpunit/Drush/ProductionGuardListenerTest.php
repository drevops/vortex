@@ -120,15 +120,15 @@
     $prod = ['prod'];
 
     yield 'sql:sync, production target' => ['sql:sync', ['source' => '@self', 'target' => '@prod'], '@self', $prod, NULL, FALSE, [static::blocked('sql:sync', '@prod')]];
-    yield 'sql:sync, production target with a site' => ['sql:sync', ['source' => '@self', 'target' => '@star_wars.prod'], '@self', $prod, NULL, FALSE, [static::blocked('sql:sync', '@star_wars.prod')]];
+    yield 'sql:sync, production target with a site' => ['sql:sync', ['source' => '@self', 'target' => '@the_new_hope.prod'], '@self', $prod, NULL, FALSE, [static::blocked('sql:sync', '@the_new_hope.prod')]];
     yield 'sql:sync, self target resolved to production' => ['sql:sync', ['source' => '@stage', 'target' => '@self'], '@prod', $prod, NULL, FALSE, [static::blocked('sql:sync', '@prod')]];
     yield 'sql:sync, production source' => ['sql:sync', ['source' => '@prod', 'target' => '@self'], '@self', $prod, NULL, TRUE];
     yield 'sql:sync, non-production target' => ['sql:sync', ['source' => '@prod', 'target' => '@stage'], '@self', $prod, NULL, TRUE];
-    yield 'sql:sync, environment ending with the production name' => ['sql:sync', ['source' => '@self', 'target' => '@star_wars.preprod'], '@self', $prod, NULL, TRUE];
+    yield 'sql:sync, environment ending with the production name' => ['sql:sync', ['source' => '@self', 'target' => '@the_new_hope.preprod'], '@self', $prod, NULL, TRUE];
     yield 'sql:sync, environment starting with the production name' => ['sql:sync', ['source' => '@self', 'target' => '@production'], '@self', $prod, NULL, TRUE];
     yield 'sql:sync, site named as the production environment' => ['sql:sync', ['source' => '@self', 'target' => '@prod.stage'], '@self', $prod, NULL, TRUE];
     yield 'sql:sync, production target resolved to a default environment' => ['sql:sync', ['source' => '@self', 'target' => '@prod'], '@self', $prod, NULL, FALSE, [static::blocked('sql:sync', '@prod')], ['@prod' => '@prod.live']];
-    yield 'sql:sync, target resolved to a production environment' => ['sql:sync', ['source' => '@self', 'target' => '@live'], '@self', $prod, NULL, FALSE, [static::blocked('sql:sync', '@star_wars.prod')], ['@live' => '@star_wars.prod']];
+    yield 'sql:sync, target resolved to a production environment' => ['sql:sync', ['source' => '@self', 'target' => '@live'], '@self', $prod, NULL, FALSE, [static::blocked('sql:sync', '@the_new_hope.prod')], ['@live' => '@the_new_hope.prod']];
     yield 'sql:sync, non-production target resolved to a default environment' => ['sql:sync', ['source' => '@self', 'target' => '@stage'], '@self', $prod, NULL, TRUE, [], ['@stage' => '@stage.live']];
 
     yield 'core:rsync, production target' => ['core:rsync', ['source' => '@self:%files', 'target' => '@prod:%files'], '@self', $prod, NULL, FALSE, [static::blocked('core:rsync', '@prod')]];
