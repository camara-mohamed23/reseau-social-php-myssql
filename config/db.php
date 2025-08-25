<?php
declare(strict_types=1);

function pdo(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $DB_HOST = '127.0.0.1';
        $DB_PORT = 3307;        // <<--- ton port MySQL
        $DB_NAME = 'social';
        $DB_USER = 'root';
        $DB_PASS = '';          // si ton mot de passe est "root", mets 'root'

        $dsn = "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
    }
    return $pdo;
}
?>
