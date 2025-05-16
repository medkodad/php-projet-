<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: admin_login.php");
    exit();
}

require_once 'config.php';

try {
    $pdo = new PDO(
        "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}

// Traitement de l'ajout de fournisseur


// Récupération des fournisseurs
$search = $_GET['search'] ?? '';
$sql = "SELECT * FROM fournisseur WHERE supplier_name LIKE ? OR contact_email LIKE ? OR contact_phone LIKE ? ORDER BY supplier_name";
$stmt = $pdo->prepare($sql);
$stmt->execute(["%$search%", "%$search%", "%$search%"]);
$fournisseurs = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Fournisseurs - PharmaStock</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .card-3d {
            transform-style: preserve-3d;
            transition: all 0.5s ease;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .card-3d:hover {
            transform: translateY(-5px) rotateX(5deg);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .btn-3d {
            transition: all 0.3s ease;
            transform-style: preserve-3d;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .btn-3d:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        .btn-3d:active {
            transform: translateY(1px);
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <div class="bg-blue-900 text-white w-64 flex-shrink-0 flex flex-col">
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
              <div class="p-4 border-b border-primary-light flex items-center">
                <div class="bg-primary-light rounded-full h-10 w-10 flex items-center justify-center mr-3">
                    <i class="fas fa-user text-primary-dark"></i>
                </div>
                <div>
                    <p class="font-medium"><?php echo isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Admin'; ?></p>
                    <span class="role-badge role-admin text-xs"><?php echo isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'Admin'; ?></span>
                </div>
            </div>
            
            <!-- Menu -->
            <nav class="flex-1 overflow-y-auto py-2">
                <div class="px-2 space-y-1">
                    <a href="admin_dashboard.php" class="bg-primary-light text-white group flex items-center px-4 py-3 text-sm font-medium rounded-md">
                        <i class="fas fa-tachometer-alt mr-3 text-accent"></i>
                        Tableau de bord
                    </a>
                    
                    <a href="list-produits.php" class="text-primary-light hover:bg-primary-light hover:text-white group flex items-center px-4 py-3 text-sm font-medium rounded-md">
                        <i class="fas fa-pills mr-3"></i>
                        Produits
                    </a>
                    
                    <a href="category_list.php" class="text-primary-light hover:bg-primary-light hover:text-white group flex items-center px-4 py-3 text-sm font-medium rounded-md">
                        <i class="fas fa-tags mr-3"></i>
                        Catégories
                    </a>
                    
                    <a href="stock_list.php" class="text-primary-light hover:bg-primary-light hover:text-white group flex items-center px-4 py-3 text-sm font-medium rounded-md">
                        <i class="fas fa-boxes mr-3"></i>
                        Stock
                       
                    </a>
                    
                    <a href="fournisseur_list.php" class="text-primary-light hover:bg-primary-light hover:text-white group flex items-center px-4 py-3 text-sm font-medium rounded-md">
                        <i class="fas fa-truck mr-3"></i>
                        Fournisseurs
                    </a>
                    
                    
                </div>
                
                <!-- Section Admin -->
                <div class="px-4 py-3 mt-4 border-t border-primary-light">
                    <h3 class="text-xs font-semibold text-primary-light uppercase tracking-wider">
                        Administration
                    </h3>
                    <div class="mt-2 space-y-1">
                        <a href="#" class="text-primary-light hover:bg-primary-light hover:text-white group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                            <i class="fas fa-user-shield mr-3"></i>
                            Utilisateurs
                        </a>
                        
                        <a href="#" class="text-primary-light hover:bg-primary-light hover:text-white group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                            <i class="fas fa-file-invoice mr-3"></i>
                            Rapports
                        </a>
                    </div>
                </div>
            </nav>
            
            <!-- Déconnexion -->
            <div class="p-4 border-t border-primary-light">
                <a href="logout.php" class="group flex items-center text-primary-light hover:text-white">
                    <i class="fas fa-sign-out-alt mr-3"></i>
                    Déconnexion
                </a>
            </div>
        </div>
        
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Topbar -->
            <header class="bg-white shadow-sm">
                <div class="flex items-center justify-between px-6 py-3">
                    <div class="flex items-center">
                        <h1 class="text-xl font-bold text-gray-900">
                            <i class="fas fa-truck text-blue-500 mr-2"></i>
                            Gestion des Fournisseurs
                        </h1>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <!-- Search -->
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <form method="GET" action="fournisseurs.php">
                                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent sm:text-sm" placeholder="Rechercher...">
                            </form>
                        </div>
                        
                        <!-- User Menu -->
                        <div class="relative">
                            <button id="user-menu-button" class="flex items-center focus:outline-none">
                                <span class="sr-only">Open user menu</span>
                                <span class="ml-2 text-sm font-medium text-gray-700 hidden md:block"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></span>
                                <i class="fas fa-chevron-down ml-1 text-gray-400 text-xs"></i>
                            </button>
                            
                            <div id="user-menu-dropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10">
                                <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-user mr-2"></i> Profil
                                </a>
                                <a href="logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-sign-out-alt mr-2"></i> Déconnexion
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Main Content -->
            <main class="flex-1 overflow-y-auto p-6 bg-gray-100">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="card-3d bg-white p-6 rounded-lg">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-blue-100 p-3 rounded-full">
                                <i class="fas fa-truck text-blue-500 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-gray-500">Fournisseurs</h3>
                                <p class="text-2xl font-bold"><?php echo count($fournisseurs); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-3d bg-white p-6 rounded-lg">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-green-100 p-3 rounded-full">
                                <i class="fas fa-envelope text-green-500 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-gray-500">Avec email</h3>
                                <?php 
                                    $withEmail = array_filter($fournisseurs, function($f) { 
                                        return !empty($f['contact_email']); 
                                    });
                                ?>
                                <p class="text-2xl font-bold"><?php echo count($withEmail); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-3d bg-white p-6 rounded-lg">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-yellow-100 p-3 rounded-full">
                                <i class="fas fa-phone text-yellow-500 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-gray-500">Avec téléphone</h3>
                                <?php 
                                    $withPhone = array_filter($fournisseurs, function($f) { 
                                        return !empty($f['contact_phone']); 
                                    });
                                ?>
                                <p class="text-2xl font-bold"><?php echo count($withPhone); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Fournisseurs Table -->
                <div class="card-3d bg-white p-6 rounded-lg">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-blue-900">Liste des Fournisseurs</h2>
                      
                    </div>
                    
                    <!-- Messages d'alerte -->
                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline"><?php echo $_SESSION['success_message']; ?></span>
                            <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                                <i class="fas fa-times cursor-pointer" onclick="this.parentElement.parentElement.style.display='none'"></i>
                            </span>
                        </div>
                        <?php unset($_SESSION['success_message']); ?>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline"><?php echo $_SESSION['error_message']; ?></span>
                            <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                                <i class="fas fa-times cursor-pointer" onclick="this.parentElement.parentElement.style.display='none'"></i>
                            </span>
                        </div>
                        <?php unset($_SESSION['error_message']); ?>
                    <?php endif; ?>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Téléphone</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Créé le</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mis à jour</th>
                                    <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($fournisseurs as $fournisseur): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($fournisseur['supplier_id']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($fournisseur['supplier_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($fournisseur['contact_email']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($fournisseur['contact_phone']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('d/m/Y H:i', strtotime($fournisseur['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('d/m/Y H:i', strtotime($fournisseur['updated_at'])); ?>
                                    </td>
                                    
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($fournisseurs)): ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                                        Aucun fournisseur trouvé
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal d'ajout -->
    <div id="add-supplier-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-1/2 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Ajouter un nouveau fournisseur</h3>
                <button onclick="document.getElementById('add-supplier-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="fournisseurs.php">
                <div class="space-y-4">
                    <div>
                        <label for="supplier_name" class="block text-sm font-medium text-gray-700">Nom du fournisseur *</label>
                        <input type="text" id="supplier_name" name="supplier_name" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="contact_email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" id="contact_email" name="contact_email" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="contact_phone" class="block text-sm font-medium text-gray-700">Téléphone</label>
                        <input type="tel" id="contact_phone" name="contact_phone" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="document.getElementById('add-supplier-modal').classList.add('hidden')" class="btn-3d bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded-lg">
                        Annuler
                    </button>
                    <button type="submit" name="add_supplier" class="btn-3d bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg">
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Toggle user menu
        document.getElementById('user-menu-button').addEventListener('click', function() {
            const dropdown = document.getElementById('user-menu-dropdown');
            dropdown.classList.toggle('hidden');
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            const userMenu = document.getElementById('user-menu-dropdown');
            const button = document.getElementById('user-menu-button');
            
            if (!button.contains(event.target)) {
                userMenu.classList.add('hidden');
            }
        });
    </script>
</body>
</html>