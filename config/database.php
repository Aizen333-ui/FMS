<?php
declare(strict_types=1);

/**
 * Database connection for the local server.
 *
 * Leave connection_string empty until MySQL is ready. The schema itself
 * lives in database/schema.mysql.sql and is applied by bin/install.php.
 *
 * Example after the database exists:
 *   'connection_string' => 'mysql:host=127.0.0.1;port=3306;dbname=factorydb;charset=utf8mb4',
 *   'username' => 'fms',
 *   'password' => 'secret',
 *
 * Tests may set FMS_CONNECTION_STRING (and optional FMS_DB_USER / FMS_DB_PASSWORD)
 * without writing credentials into this file.
 */
return [
   'connection_string' => 'mysql:host=127.0.0.1;port=3306;dbname=factorydb;charset=utf8mb4',
'username' => 'root',
'password' => '',
];
