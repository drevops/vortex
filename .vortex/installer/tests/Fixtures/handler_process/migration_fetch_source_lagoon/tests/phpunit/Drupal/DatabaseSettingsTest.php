@@ -47,6 +47,17 @@
             'prefix' => '',
           ],
         ],
+        'migrate' => [
+          'default' => [
+            'database' => 'drupal',
+            'username' => 'drupal',
+            'password' => 'drupal',
+            'host' => 'localhost',
+            'port' => '',
+            'prefix' => '',
+            'driver' => 'mysql',
+          ],
+        ],
       ],
     ];
 
@@ -74,6 +85,17 @@
             'prefix' => '',
           ],
         ],
+        'migrate' => [
+          'default' => [
+            'database' => 'drupal',
+            'username' => 'drupal',
+            'password' => 'drupal',
+            'host' => 'localhost',
+            'port' => '',
+            'prefix' => '',
+            'driver' => 'mysql',
+          ],
+        ],
       ],
     ];
 
@@ -101,6 +123,17 @@
             'prefix' => '',
           ],
         ],
+        'migrate' => [
+          'default' => [
+            'database' => 'drupal',
+            'username' => 'drupal',
+            'password' => 'drupal',
+            'host' => 'localhost',
+            'port' => '',
+            'prefix' => '',
+            'driver' => 'mysql',
+          ],
+        ],
       ],
     ];
 
@@ -128,9 +161,55 @@
             'prefix' => '',
           ],
         ],
+        'migrate' => [
+          'default' => [
+            'database' => 'drupal',
+            'username' => 'drupal',
+            'password' => 'drupal',
+            'host' => 'localhost',
+            'port' => '',
+            'prefix' => '',
+            'driver' => 'mysql',
+          ],
+        ],
       ],
     ];
 
+    yield [
+      [
+        'DATABASE2_NAME' => 'migrate_db_name',
+        'DATABASE2_USERNAME' => 'migrate_db_user',
+        'DATABASE2_PASSWORD' => 'migrate_db_pass',
+        'DATABASE2_HOST' => 'migrate_db_host',
+        'DATABASE2_PORT' => '3307',
+      ],
+      [
+        'default' => [
+          'default' => [
+            'database' => 'drupal',
+            'username' => 'drupal',
+            'password' => 'drupal',
+            'host' => 'localhost',
+            'port' => '3306',
+            'charset' => 'utf8mb4',
+            'collation' => 'utf8mb4_general_ci',
+            'driver' => 'mysql',
+            'prefix' => '',
+          ],
+        ],
+        'migrate' => [
+          'default' => [
+            'database' => 'migrate_db_name',
+            'username' => 'migrate_db_user',
+            'password' => 'migrate_db_pass',
+            'host' => 'migrate_db_host',
+            'port' => '3307',
+            'prefix' => '',
+            'driver' => 'mysql',
+          ],
+        ],
+      ],
+    ];
   }
 
 }
