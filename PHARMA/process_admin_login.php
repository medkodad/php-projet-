<?php
// Bda l'page dyalna, kanbdaw b session_start() bach n7fdo l'etat dyal l'utilisateur
session_start();
require_once 'config.php';

// Kanchofo wach l'request dyalna POST wla la
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kanakhdo l'data li dkhal l'utilisateur w n7fdoha
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Kanchofo wach l'champs mliyin wla la
    if (empty($username) || empty($password)) {
        header("Location: admin_login.php?error=empty&username=".urlencode($username));
        exit();
    }
    
    try {
        // Connexion l database
        $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8";
        $db = new PDO($dsn, DB_USER, DB_PASS, DB_OPTIONS);
        
        // Kanchofo wach l'utilisateur kayn f database
        $query = "SELECT id, password, role FROM users WHERE username = :username LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Kanchofo wach l'mot de passe s7a7
            if (password_verify($password, $user['password'])) {
                // Login njah, kan7fdo l'data f session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                
                // Kanredirigiw l'page li khasa b role dyal l'utilisateur
                if ($user['role'] === 'admin') {
                    header("Location: admin_dashboard.php");
                    exit();
                } elseif ($user['role'] === 'fournisseur') {
                    header("Location: fournisseur_dashboard.php");
                    exit();
                } else {
                    // Role machi ma3ruf
                    header("Location: admin_login.php?error=unauthorized&username=".urlencode($username));
                    exit();
                }
            } else {
                // Mot de passe ghalet
                header("Location: admin_login.php?error=invalid_credentials&username=".urlencode($username));
                exit();
            }
        } else {
            // Utilisateur ma kaynch
            header("Location: admin_login.php?error=invalid_credentials&username=".urlencode($username));
            exit();
        }
    } catch (PDOException $e) {
        // Error f database
        error_log("Database error: ".$e->getMessage());
        
        // Message d'erreur détaillé f mode développement
        $errorMsg = (ENVIRONMENT === 'development') 
            ? "Erreur de connexion: " . $e->getMessage()
            : "Erreur de connexion à la base de données. Veuillez réessayer plus tard.";
        
        header("Location: admin_login.php?error=db_error&message=".urlencode($errorMsg)."&username=".urlencode($username));
        exit();
    }
} else {
    // Ila machi POST request, nredirigiw l login page
    header("Location: admin_login.php");
    exit();
}
?>