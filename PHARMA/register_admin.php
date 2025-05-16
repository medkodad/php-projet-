<?php
session_start();
// Vérifier si l'utilisateur est déjà connecté et est admin
if(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    header("Location: admin_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharma3D - Création compte Admin</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        body {
            display: flex;
            min-height: 100vh;
            background-color: #f5f5f5;
        }

        .left-panel {
            flex: 1;
            background-color: #1976d2;
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .right-panel {
            flex: 1;
            padding: 40px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background-color: white;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo {
            width: 120px;
            height: 120px;
            object-fit: contain;
        }

        h1 {
            font-size: 2rem;
            margin-bottom: 10px;
            text-align: center;
            color: #333;
        }

        .subtitle {
            font-size: 1rem;
            text-align: center;
            margin-bottom: 30px;
            color: #666;
        }

        .feature-icons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 80px;
        }

        .icon-button {
            width: 60px;
            height: 60px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .form-container {
            max-width: 400px;
            margin: 0 auto;
            width: 100%;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }

        input, select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border 0.3s;
        }

        input:focus, select:focus {
            border-color: #1976d2;
            outline: none;
        }

        .input-with-icon {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #777;
        }

        .input-with-icon input,
        .input-with-icon select {
            padding-left: 35px;
        }

        .eye-icon {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #777;
            cursor: pointer;
        }

        .submit-btn {
            width: 100%;
            padding: 15px;
            background-color: #1976d2;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
            transition: background-color 0.3s;
        }

        .submit-btn:hover {
            background-color: #1565c0;
        }

        .error-message {
            color: #d32f2f;
            background-color: #ffebee;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }

        .success-message {
            color: #388e3c;
            background-color: #e8f5e9;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }

        .login-link a {
            color: #1976d2;
            text-decoration: none;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }
            .left-panel {
                padding: 40px 20px;
            }
            .right-panel {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="left-panel">
        <div class="logo-container">
            <img src="images/logopharma.png" alt="Logo Pharma3D" class="logo">
        </div>
        <h1> SMART PHARMA</h1>
        <p class="subtitle">Système de gestion pharmaceutique</p>

        <div class="feature-icons">
            <div class="icon-button">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path d="M12 12C14.21 12 16 10.21 16 8C16 5.79 14.21 4 12 4C9.79 4 8 5.79 8 8C8 10.21 9.79 12 12 12ZM12 14C9.33 14 4 15.34 4 18V20H20V18C20 15.34 14.67 14 12 14Z" fill="white"/>
                </svg>
            </div>
            <div class="icon-button">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path d="M12 2L4 5V11.09C4 16.14 7.41 20.85 12 22C16.59 20.85 20 16.14 20 11.09V5L12 2Z" fill="white"/>
                </svg>
            </div>
            <div class="icon-button">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path d="M20 8H17V4H7V8H4C2.9 8 2 8.9 2 10V20C2 21.1 2.9 22 4 22H20C21.1 22 22 21.1 22 20V10C22 8.9 21.1 8 20 8Z" fill="white"/>
                </svg>
            </div>
        </div>
    </div>

    <div class="right-panel">
        <div class="form-container">
            <h1>Créer un compte Admin</h1>
            <p class="subtitle">Remplissez le formulaire pour créer un compte administrateur</p>

            <?php if(isset($_GET['error'])): ?>
                <div class="error-message">
                    <?php 
                    switch($_GET['error']) {
                        case 'empty':
                            echo 'Tous les champs sont obligatoires.';
                            break;
                        case 'password_mismatch':
                            echo 'Les mots de passe ne correspondent pas.';
                            break;
                        case 'username_exists':
                            echo 'Ce nom d\'utilisateur est déjà pris.';
                            break;
                        case 'invalid_role':
                            echo 'Le rôle sélectionné n\'est pas valide.';
                            break;
                        case 'database_error':
                            echo 'Erreur de connexion à la base de données.';
                            break;
                        default:
                            echo 'Une erreur est survenue. Veuillez réessayer.';
                    }
                    ?>
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['success']) && $_GET['success'] === 'account_created'): ?>
                <div class="success-message">
                    Compte créé avec succès! <a href="admin_login.php">Connectez-vous</a>
                </div>
            <?php endif; ?>

            <form action="process_register_admin.php" method="POST" autocomplete="off">
                <div class="form-group">
                    <label for="username">Nom d'utilisateur</label>
                    <div class="input-with-icon">
                        <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none">
                            <path d="M12 12C14.21 12 16 10.21 16 8C16 5.79 14.21 4 12 4C9.79 4 8 5.79 8 8C8 10.21 9.79 12 12 12ZM12 14C9.33 14 4 15.34 4 18V20H20V18C20 15.34 14.67 14 12 14Z" fill="#777"/>
                        </svg>
                        <input type="text" id="username" name="username" required placeholder="Entrez un nom d'utilisateur" value="<?php echo isset($_GET['username']) ? htmlspecialchars($_GET['username']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="role">Rôle</label>
                    <div class="input-with-icon">
                        <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none">
                            <path fill="#777" d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                        </svg>
                        <select id="role" name="role" required>
                            <option value="">Sélectionnez un rôle</option>
                            <option value="admin" <?php echo (isset($_GET['role']) && $_GET['role'] === 'admin') ? 'selected' : ''; ?>>Administrateur</option>
                            <option value="fournisseur" <?php echo (isset($_GET['role']) && $_GET['role'] === 'fournisseur') ? 'selected' : ''; ?>>Fournisseur</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <div class="input-with-icon">
                        <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none">
                            <path d="M18 8H17V6C17 3.24 14.76 1 12 1C9.24 1 7 3.24 7 6V8H6C4.9 8 4 8.9 4 10V20C4 21.1 4.9 22 6 22H18C19.1 22 20 21.1 20 20V10C20 8.9 19.1 8 18 8Z" fill="#777"/>
                        </svg>
                        <input type="password" id="password" name="password" required placeholder="Entrez un mot de passe">
                        <svg class="eye-icon" onclick="togglePassword('password')" width="18" height="18" viewBox="0 0 24 24" fill="none">
                            <path d="M12 4.5C7 4.5 2.73 7.61 1 12C2.73 16.39 7 19.5 12 19.5C17 19.5 21.27 16.39 23 12C21.27 7.61 17 4.5 12 4.5ZM12 17C9.24 17 7 14.76 7 12C7 9.24 9.24 7 12 7C14.76 7 17 9.24 17 12C17 14.76 14.76 17 12 17Z" fill="#777"/>
                        </svg>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirmer le mot de passe</label>
                    <div class="input-with-icon">
                        <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none">
                            <path d="M18 8H17V6C17 3.24 14.76 1 12 1C9.24 1 7 3.24 7 6V8H6C4.9 8 4 8.9 4 10V20C4 21.1 4.9 22 6 22H18C19.1 22 20 21.1 20 20V10C20 8.9 19.1 8 18 8Z" fill="#777"/>
                        </svg>
                        <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirmez le mot de passe">
                        <svg class="eye-icon" onclick="togglePassword('confirm_password')" width="18" height="18" viewBox="0 0 24 24" fill="none">
                            <path d="M12 4.5C7 4.5 2.73 7.61 1 12C2.73 16.39 7 19.5 12 19.5C17 19.5 21.27 16.39 23 12C21.27 7.61 17 4.5 12 4.5ZM12 17C9.24 17 7 14.76 7 12C7 9.24 9.24 7 12 7C14.76 7 17 9.24 17 12C17 14.76 14.76 17 12 17Z" fill="#777"/>
                        </svg>
                    </div>
                </div>

                <button type="submit" class="submit-btn">Créer le compte</button>

                <div class="login-link">
                    Déjà un compte? <a href="admin_login.php">Se connecter</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.nextElementSibling;
            
            if (field.type === "password") {
                field.type = "text";
                icon.innerHTML = '<path d="M12 6.5C9.11 6.5 6.56 7.94 4.61 10.17L2.5 7.5L1 9L3.15 11.5C1.64 13.26 1 15.33 1 17.5C1 19.67 1.64 21.74 3.15 23.5L1 26L2.5 27.5L4.61 24.83C6.56 27.06 9.11 28.5 12 28.5C14.89 28.5 17.44 27.06 19.39 24.83L21.5 27.5L23 26L20.85 23.5C22.36 21.74 23 19.67 23 17.5C23 15.33 22.36 13.26 20.85 11.5L23 9L21.5 7.5L19.39 10.17C17.44 7.94 14.89 6.5 12 6.5ZM12 9.5C16.42 9.5 20 13.08 20 17.5C20 21.92 16.42 25.5 12 25.5C7.58 25.5 4 21.92 4 17.5C4 13.08 7.58 9.5 12 9.5ZM12 12.5C9.24 12.5 7 14.74 7 17.5C7 20.26 9.24 22.5 12 22.5C14.76 22.5 17 20.26 17 17.5C17 14.74 14.76 12.5 12 12.5Z" fill="#777"/>';
            } else {
                field.type = "password";
                icon.innerHTML = '<path d="M12 4.5C7 4.5 2.73 7.61 1 12C2.73 16.39 7 19.5 12 19.5C17 19.5 21.27 16.39 23 12C21.27 7.61 17 4.5 12 4.5ZM12 17C9.24 17 7 14.76 7 12C7 9.24 9.24 7 12 7C14.76 7 17 9.24 17 12C17 14.76 14.76 17 12 17Z" fill="#777"/>';
            }
        }
    </script>
</body>
</html>