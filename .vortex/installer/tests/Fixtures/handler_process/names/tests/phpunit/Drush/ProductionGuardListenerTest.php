@@ -97,11 +97,11 @@
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
 
