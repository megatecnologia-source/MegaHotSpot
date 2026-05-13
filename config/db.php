<?php

class DB
{
    private static ?PDO $instance = null;

    private function __construct()
    {
    }
    private function __clone()
    {
    }

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $host = getenv('DB_HOST') ?: '127.0.0.1';
            $db = getenv('DB_NAME') ?: 'mega_hotspot';
            $user = getenv('DB_USER') ?: 'manu';
            $pass = getenv('DB_PASS') ?: '@3m4NU3l09';
            $charset = 'utf8mb4';

            $dsn = "mysql:host=$host;dbname=$db;charset=$charset";

            try {
                self::$instance = new PDO($dsn, $user, $pass);
                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$instance->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
                self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                // Log the real error to a file if needed, but not on screen
                throw new Exception("Falha na conexão com o banco de dados.");
            }
        }
        return self::$instance;
    }
}
