<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}


// Database connection
$db = new PDO('mysql:host=localhost;dbname=PHARMA;charset=utf8', 'username', 'password');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Get current admin data
$admin_id = $_SESSION['admin_id'];
$stmt = $db->prepare("SELECT * FROM admin WHERE id_Admin = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    die("Administrateur non trouvé");
}

// Initialize variables
$errors = [];
$success = false;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $nom = filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_STRING);
    $prenom = filter_input(INPUT_POST, 'prenom', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $mot_de_passe = $_POST['mot_de_passe'];
    $confirm_password = $_POST['confirm_password'];

    // Validate inputs
    if (empty($nom)) {
        $errors['nom'] = "Le nom est requis";
    }
    
    if (empty($prenom)) {
        $errors['prenom'] = "Le prénom est requis";
    }
    
    if (empty($email) {
        $errors['email'] = "L'email est requis";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "L'email n'est pas valide";
    }
    
    // Check if password is being changed
    $password_changed = false;
    if (!empty($mot_de_passe) {
        if (strlen($mot_de_passe) < 8) {
            $errors['mot_de_passe'] = "Le mot de passe doit contenir au moins 8 caractères";
        } elseif ($mot_de_passe !== $confirm_password) {
            $errors['confirm_password'] = "Les mots de passe ne correspondent pas";
        } else {
            $password_changed = true;
        }
    }

    // Check if email already exists (for another admin)
    if ($email !== $admin['email']) {
        $stmt = $db->prepare("SELECT id_Admin FROM admin WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors['email'] = "Cet email est déjà utilisé par un autre administrateur";
        }
    }

    // Update if no errors
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            if ($password_changed) {
                // Update with password
                $hashed_password = password_hash($mot_de_passe, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE admin SET nom = ?, prenom = ?, email = ?, mot_de_passe = ? WHERE id_Admin = ?");
                $stmt->execute([$nom, $prenom, $email, $hashed_password, $admin_id]);
            } else {
                // Update without password
                $stmt = $db->prepare("UPDATE admin SET nom = ?, prenom = ?, email = ? WHERE id_Admin = ?");
                $stmt->execute([$nom, $prenom, $email, $admin_id]);
            }
            
            $db->commit();
            $success = true;
            
            // Update session data if email changed
            if ($email !== $admin['email']) {
                $_SESSION['admin_email'] = $email;
            }
            
            // Refresh admin data
            $stmt = $db->prepare("SELECT * FROM admin WHERE id_Admin = ?");
            $stmt->execute([$admin_id]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            $db->rollBack();
            $errors['database'] = "Erreur de base de données: " . $e->getMessage();
        }
    }
}

// Redirect back to profile page with success message
if ($success) {
    $_SESSION['success_message'] = "Profil mis à jour avec succès";
    header("Location: profil.php");
    exit();
}

// If we get here, there were errors - store them in session and redirect back
$_SESSION['errors'] = $errors;
$_SESSION['form_data'] = $_POST;
header("Location: profil.php");
exit();
?>