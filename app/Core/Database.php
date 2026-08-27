<?php

namespace App\Core;

use PDO;
use PDOException;

/**
 * Database
 *
 * Thin singleton wrapper around PDO. Always uses prepared statements
 * (enforced by ATTR_EMULATE_PREPARES = false) so every query in the
 * app is protected against SQL injection by construction.
 */
class Database
{
    private static ?PDO $instance = null;

    public static function connection(): PDO
    {
        if (self::$instance === null) {
            $host = config('DB_HOST', '127.0.0.1');
            $port = config('DB_PORT', '3306');
            $name = config('DB_NAME', 'stockpilot');
            $user = config('DB_USER', 'root');
            $pass = config('DB_PASS', '');

            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

            try {
                self::$instance = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                // Never leak DSN/credentials in production
                if (config('APP_DEBUG', 'false') === 'true') {
                    die('Database connection failed: ' . $e->getMessage());
                }
                die('Database connection failed. Please check server configuration.');
            }
        }

        return self::$instance;
    }
}
