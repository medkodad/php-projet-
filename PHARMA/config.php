<?php
// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'pharmac_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Options PDO
define('DB_OPTIONS', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

// Mode d'environnement (development/production)
define('ENVIRONMENT', 'development');
?>