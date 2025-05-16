<?php
// Had l'file kay3ti l'configuration dyal database
// Kay3ti:
// - Host (localhost)
// - Database name (pharmac_db)
// - Username (root)
// - Password (empty)
// - Character set (utf8mb4)

// Configuration dyal database
define('DB_HOST', 'localhost');
define('DB_NAME', 'pharmac_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Options li kaykhdmo m3a PDO
// Kay3ti:
// - Error mode: Exception
// - Fetch mode: Associative array
// - Emulate prepares: False
define('DB_OPTIONS', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

// Connexion l database
try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, DB_OPTIONS);
} catch (PDOException $e) {
    // Ila kan error f connexion, nlogiwha w n9tlo l'execution
    error_log('Database connection error: ' . $e->getMessage());
    die('Erreur de connexion à la base de données');
}
?>