<?php
session_start();
require_once 'config.php';

// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Initialisation des variables
$user_id = $_SESSION['user_id'];
$message = '';
$messageType = '';
$user_name = $_SESSION['user_name'] ?? '';
$user_role = $_SESSION['user_role'] ?? '';
$created_at = '';

// Récupération des infos utilisateur
try {
    $stmt = $pdo->prepare("SELECT id, username, role, created_at FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        header("Location: admin_login.php");
        exit();
    }

    // Assignation des valeurs
    $user_name = $user['username'];
    $user_role = $user['role'];
    $created_at = date('d/m/Y', strtotime($user['created_at']));
    $last_login = date('d/m/Y H:i', strtotime('-2 hours')); // Simulé

} catch (PDOException $e) {
    $message = "Erreur de base de données: " . $e->getMessage();
    $messageType = "error";
}

// Traitement du changement de mot de passe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $message = "Tous les champs sont requis";
        $messageType = "error";
    } elseif ($new_password !== $confirm_password) {
        $message = "Les mots de passe ne correspondent pas";
        $messageType = "error";
    } else {
        try {
            // Vérification du mot de passe actuel
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            if ($user && password_verify($current_password, $user['password'])) {
                // Mise à jour du mot de passe
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update_stmt->execute([$hashed_password, $user_id]);

                $message = "Mot de passe mis à jour avec succès";
                $messageType = "success";
            } else {
                $message = "Mot de passe actuel incorrect";
                $messageType = "error";
            }
        } catch (PDOException $e) {
            $message = "Erreur de base de données: " . $e->getMessage();
            $messageType = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Utilisateur - PharmaStock</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .card-3d {
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .card-3d:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        .role-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .role-admin {
            background-color: #e3f2fd;
            color: #0d47a1;
        }
        .role-supplier {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        .notification-success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #4CAF50;
        }
        .notification-error {
            background-color: #ffebee;
            color: #c62828;
            border-left: 4px solid #F44336;
        }
        .primary-light {
            background-color: #e3f2fd;
            color: #0d47a1;
        }
        .primary-dark {
            background-color: #0d47a1;
            color: white;
        }
        .accent {
            color: #2196f3;
        }
        .error {
            background-color: #F44336;
        }
    </style>
</head>
<body class="bg-gray-100 flex">
    <!-- Sidebar -->
    <div class="bg-blue-900 text-white w-64 flex-shrink-0 flex flex-col h-screen sticky top-0">
        <!-- Logo -->
        <div class="flex items-center justify-between p-4 border-b border-blue-700">
            <div class="flex items-center">
                <div class="bg-white p-2 rounded-lg mr-3">
                    <img src="images/logopharma.png" alt="Logo" class="h-8 w-8">
                </div>
                <span class="text-xl font-bold">PharmaStock</span>
            </div>
        </div>
        
        <!-- User Profile -->
        <div class="p-4 border-b border-blue-700 flex items-center">
            <div class="bg-blue-100 rounded-full h-10 w-10 flex items-center justify-center mr-3">
                <i class="fas fa-user text-blue-800"></i>
            </div>
            <div>
                <p class="font-medium"><?= htmlspecialchars($user_name) ?></p>
                <span class="role-badge <?= strtolower($user_role) === 'admin' ? 'role-admin' : 'role-supplier' ?> text-xs">
                    <?= htmlspecialchars($user_role) ?>
                </span>
            </div>
        </div>
        
        <!-- Menu -->
        <nav class="flex-1 overflow-y-auto py-2">
            <div class="px-2 space-y-1">
                <a href="admin_dashboard.php" class="bg-blue-800 text-white group flex items-center px-4 py-3 text-sm font-medium rounded-md">
                    <i class="fas fa-tachometer-alt mr-3 text-blue-300"></i>
                    Tableau de bord
                </a>
                
                <a href="list-produits.php" class="text-blue-200 hover:bg-blue-800 hover:text-white group flex items-center px-4 py-3 text-sm font-medium rounded-md">
                    <i class="fas fa-pills mr-3"></i>
                    Produits
                </a>
                
                <a href="category_list.php" class="text-blue-200 hover:bg-blue-800 hover:text-white group flex items-center px-4 py-3 text-sm font-medium rounded-md">
                    <i class="fas fa-tags mr-3"></i>
                    Catégories
                </a>
                
                <a href="stock_list.php" class="text-blue-200 hover:bg-blue-800 hover:text-white group flex items-center px-4 py-3 text-sm font-medium rounded-md">
                    <i class="fas fa-boxes mr-3"></i>
                    Stock
                    <span class="ml-auto bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">5</span>
                </a>
                
                <a href="fournisseur_list.php" class="text-blue-200 hover:bg-blue-800 hover:text-white group flex items-center px-4 py-3 text-sm font-medium rounded-md">
                    <i class="fas fa-truck mr-3"></i>
                    Fournisseurs
                </a>
                
                <a href="#" class="text-blue-200 hover:bg-blue-800 hover:text-white group flex items-center px-4 py-3 text-sm font-medium rounded-md">
                    <i class="fas fa-chart-line mr-3"></i>
                    Statistiques
                </a>
            </div>
            
            <!-- Section Admin -->
            <div class="px-4 py-3 mt-4 border-t border-blue-700">
                <h3 class="text-xs font-semibold text-blue-300 uppercase tracking-wider">
                    Administration
                </h3>
                <div class="mt-2 space-y-1">
                    <a href="#" class="text-blue-200 hover:bg-blue-800 hover:text-white group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <i class="fas fa-user-shield mr-3"></i>
                        Utilisateurs
                    </a>
                    
                    <a href="#" class="text-blue-200 hover:bg-blue-800 hover:text-white group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <i class="fas fa-file-invoice mr-3"></i>
                        Rapports
                    </a>
                </div>
            </div>
        </nav>
        
        <!-- Déconnexion -->
        <div class="p-4 border-t border-blue-700">
            <a href="logout.php" class="group flex items-center text-blue-200 hover:text-white">
                <i class="fas fa-sign-out-alt mr-3"></i>
                Déconnexion
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 overflow-auto">
        <main class="container mx-auto py-8 px-4">
            <!-- Message d'état -->
            <?php if ($message): ?>
                <div class="p-4 mb-6 rounded-lg <?= $messageType === 'error' ? 'notification-error' : 'notification-success' ?>">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-<?= $messageType === 'error' ? 'exclamation-circle' : 'check-circle' ?> mr-2"></i>
                        </div>
                        <div>
                            <?= htmlspecialchars($message) ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Profile Header -->
            <div class="card-3d bg-gradient-to-r from-blue-800 to-blue-600 p-6 rounded-lg text-white mb-6">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                    <div class="flex items-center">
                        <div class="h-16 w-16 rounded-full bg-white flex items-center justify-center mr-4">
                            <i class="fas fa-user text-3xl text-blue-800"></i>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold"><?= htmlspecialchars($user_name) ?></h1>
                            <div class="flex items-center">
                                <span class="role-badge <?= strtolower($user_role) === 'admin' ? 'role-admin' : 'role-supplier' ?> mr-3">
                                    <i class="fas fa-user-shield mr-1"></i> <?= htmlspecialchars($user_role) ?>
                                </span>
                                <span class="text-sm opacity-90">Dernière connexion: <?= $last_login ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 md:mt-0">
                        <button id="edit-profile-btn" class="bg-white text-blue-800 hover:bg-gray-100 px-4 py-2 rounded-lg font-medium transition">
                            <i class="fas fa-edit mr-2"></i> Modifier le profil
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Profil et paramètres -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Informations Personnelles -->
                    <div class="card-3d bg-white p-6 rounded-lg">
                        <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-id-card text-blue-600 mr-3"></i>
                            Informations Personnelles
                        </h2>
                        
                        <form id="profile-form" method="POST" action="" class="hidden">
                            <div class="mb-6">
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nom d'utilisateur</label>
                                <input type="text" id="name" name="name" value="<?= htmlspecialchars($user_name) ?>" 
                                       class="block w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <div class="border-t border-gray-200 pt-4 mt-4">
                                <h3 class="text-lg font-medium text-gray-900 mb-2">Changer le mot de passe</h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                                    <div>
                                        <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Mot de passe actuel</label>
                                        <input type="password" id="current_password" name="current_password" 
                                               class="block w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    
                                    <div>
                                        <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">Nouveau mot de passe</label>
                                        <input type="password" id="new_password" name="new_password" 
                                               class="block w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    
                                    <div>
                                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirmer le mot de passe</label>
                                        <input type="password" id="confirm_password" name="confirm_password" 
                                               class="block w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex justify-end">
                                <button type="button" id="cancel-edit" class="bg-gray-300 text-gray-700 hover:bg-gray-400 px-4 py-2 rounded-lg font-medium mr-3 transition">
                                    Annuler
                                </button>
                                <button type="submit" name="update_password" class="bg-blue-800 text-white hover:bg-blue-700 px-4 py-2 rounded-lg font-medium transition">
                                    <i class="fas fa-save mr-2"></i> Enregistrer
                                </button>
                            </div>
                        </form>
                        
                        <div id="profile-info" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <p class="text-sm text-gray-500 mb-1">Nom d'utilisateur</p>
                                    <p class="font-medium"><?= htmlspecialchars($user_name) ?></p>
                                </div>
                                
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <p class="text-sm text-gray-500 mb-1">Rôle</p>
                                    <p class="font-medium">
                                        <span class="role-badge <?= strtolower($user_role) === 'admin' ? 'role-admin' : 'role-supplier' ?>">
                                            <?= htmlspecialchars($user_role) ?>
                                        </span>
                                    </p>
                                </div>
                                
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <p class="text-sm text-gray-500 mb-1">Date de création</p>
                                    <p class="font-medium"><?= $created_at ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Colonne secondaire -->
                <div class="space-y-6">
                    <!-- Carte Sécurité -->
                    <div class="card-3d bg-white p-6 rounded-lg">
                        <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-shield-alt text-blue-600 mr-3"></i>
                            Sécurité
                        </h2>
                        <div class="space-y-3">
                            <div class="flex items-center">
                                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                <span>Connexion sécurisée (HTTPS)</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                <span>Authentification à deux facteurs</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Carte Activité -->
                    <div class="card-3d bg-white p-6 rounded-lg">
                        <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-history text-blue-600 mr-3"></i>
                            Activité récente
                        </h2>
                        <div class="space-y-4">
                            <div class="flex items-start">
                                <div class="bg-blue-100 text-blue-600 rounded-full h-8 w-8 flex items-center justify-center mr-3">
                                    <i class="fas fa-sign-in-alt text-sm"></i>
                                </div>
                                <div>
                                    <p class="font-medium">Connexion réussie</p>
                                    <p class="text-sm text-gray-500"><?= $last_login ?></p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <div class="bg-green-100 text-green-600 rounded-full h-8 w-8 flex items-center justify-center mr-3">
                                    <i class="fas fa-user-edit text-sm"></i>
                                </div>
                                <div>
                                    <p class="font-medium">Profil mis à jour</p>
                                    <p class="text-sm text-gray-500"><?= date('d/m/Y H:i', strtotime('-1 day')) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Gestion du formulaire de profil
        document.getElementById('edit-profile-btn').addEventListener('click', function() {
            document.getElementById('profile-info').classList.add('hidden');
            document.getElementById('profile-form').classList.remove('hidden');
        });

        document.getElementById('cancel-edit').addEventListener('click', function() {
            document.getElementById('profile-info').classList.remove('hidden');
            document.getElementById('profile-form').classList.add('hidden');
        });
    </script>
</body>
</html>