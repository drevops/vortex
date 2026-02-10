@@ -48,6 +48,17 @@
               'prefix' => '',
             ],
           ],
+          'migrate' => [
+            'default' => [
+              'database' => 'drupal',
+              'username' => 'drupal',
+              'password' => 'drupal',
+              'host' => 'localhost',
+              'port' => '',
+              'prefix' => '',
+              'driver' => 'mysql',
+            ],
+          ],
         ],
       ],
 
@@ -75,6 +86,17 @@
               'prefix' => '',
             ],
           ],
+          'migrate' => [
+            'default' => [
+              'database' => 'drupal',
+              'username' => 'drupal',
+              'password' => 'drupal',
+              'host' => 'localhost',
+              'port' => '',
+              'prefix' => '',
+              'driver' => 'mysql',
+            ],
+          ],
         ],
       ],
 
@@ -102,6 +124,17 @@
               'prefix' => '',
             ],
           ],
+          'migrate' => [
+            'default' => [
+              'database' => 'drupal',
+              'username' => 'drupal',
+              'password' => 'drupal',
+              'host' => 'localhost',
+              'port' => '',
+              'prefix' => '',
+              'driver' => 'mysql',
+            ],
+          ],
         ],
       ],
 
@@ -127,6 +160,52 @@
               'collation' => 'mysql_utf8mb3_bin',
               'driver' => 'mysql',
               'prefix' => '',
+            ],
+          ],
+          'migrate' => [
+            'default' => [
+              'database' => 'drupal',
+              'username' => 'drupal',
+              'password' => 'drupal',
+              'host' => 'localhost',
+              'port' => '',
+              'prefix' => '',
+              'driver' => 'mysql',
+            ],
+          ],
+        ],
+      ],
+      [
+        [
+          'DATABASE2_NAME' => 'migrate_db_name',
+          'DATABASE2_USERNAME' => 'migrate_db_user',
+          'DATABASE2_PASSWORD' => 'migrate_db_pass',
+          'DATABASE2_HOST' => 'migrate_db_host',
+          'DATABASE2_PORT' => '3307',
+        ],
+        [
+          'default' => [
+            'default' => [
+              'database' => 'drupal',
+              'username' => 'drupal',
+              'password' => 'drupal',
+              'host' => 'localhost',
+              'port' => '3306',
+              'charset' => 'utf8mb4',
+              'collation' => 'utf8mb4_general_ci',
+              'driver' => 'mysql',
+              'prefix' => '',
+            ],
+          ],
+          'migrate' => [
+            'default' => [
+              'database' => 'migrate_db_name',
+              'username' => 'migrate_db_user',
+              'password' => 'migrate_db_pass',
+              'host' => 'migrate_db_host',
+              'port' => '3307',
+              'prefix' => '',
+              'driver' => 'mysql',
             ],
           ],
         ],
