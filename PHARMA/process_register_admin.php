<?php
session_start();

// Configuration de la base de données
$host = 'localhost';
$dbname = 'pharmac_db';
$username = 'root';
$password = '';

try {
    // Connexion à la base de données
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Traitement du formulaire
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validation des champs
        if (
            empty($_POST['username']) || 
            empty($_POST['role']) || 
            empty($_POST['password']) || 
            empty($_POST['confirm_password'])
        ) {
            header("Location: register_admin.php?error=empty");
            exit();
        }

        $username = trim($_POST['username']);
        $role = trim($_POST['role']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        // Vérification de la correspondance des mots de passe
        if ($password !== $confirm_password) {
            header("Location: register_admin.php?error=password_mismatch");
            exit();
        }

        // Vérification si l'utilisateur existe déjà
        $checkQuery = "SELECT id FROM users WHERE username = :username";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':username', $username);
        $checkStmt->execute();

        if ($checkStmt->rowCount() > 0) {
            header("Location: register_admin.php?error=username_exists");
            exit();
        }

        // Hash du mot de passe
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insertion dans la base de données
        $query = "INSERT INTO users (username, password, role, created_at) 
                  VALUES (:username, :password, :role, NOW())";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':role', $role);

        if ($stmt->execute()) {
            header("Location: register_admin.php?success=account_created");
            exit();
        } else {
            header("Location: register_admin.php?error=database_error");
            exit();
        }
    }

} catch (PDOException $e) {
    // Journalisation des erreurs
    error_log("Database error: " . $e->getMessage(), 3, 'errors.log');
    header("Location: register_admin.php?error=database_error");
    exit();
}
?>