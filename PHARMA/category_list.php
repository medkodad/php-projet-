<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Connexion à la base de données
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

// Récupérer les catégories avec leurs parents
$sql = "SELECT c.id, c.name, c.parent_id, p.name as parent_name 
        FROM categories c
        LEFT JOIN categories p ON c.parent_id = p.id
        ORDER BY COALESCE(c.parent_id, c.id), c.id";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$categories = $stmt->fetchAll();

// Récupérer les catégories principales pour le formulaire
$mainCategories = $pdo->query("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY name")->fetchAll();



// Fermer la connexion
$pdo = null;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Catégories - PharmaStock</title>
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
        .badge-primary {
            background-color: #e3f2fd;
            color: #0d47a1;
        }
        .badge-secondary {
            background-color: #e8f5e9;
            color: #2e7d32;
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
                    <p class="font-medium"><?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Admin'; ?></p>
                    <span class="role-badge role-admin text-xs"><?php echo isset($_SESSION['user_role']) ? htmlspecialchars($_SESSION['user_role']) : 'Admin'; ?></span>
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
                            <i class="fas fa-tags text-blue-500 mr-2"></i>
                            Gestion des Catégories
                        </h1>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <!-- Search -->
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <input type="text" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent sm:text-sm" placeholder="Rechercher une catégorie...">
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
                <!-- Header with Add Button -->
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-blue-900">Liste des Catégories</h2>
                </div>
                
                <!-- Add Category Form -->
              
                
                <!-- Categories Table -->
                <div class="card-3d bg-white p-6 rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Parent</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($category['id']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($category['name']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo isset($category['parent_name']) ? htmlspecialchars($category['parent_name']) : '—'; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs rounded-full <?php echo !empty($category['parent_id']) ? 'badge-secondary' : 'badge-primary'; ?>">
                                            <?php echo !empty($category['parent_id']) ? 'Sous-catégorie' : 'Catégorie principale'; ?>
                                        </span>
                                    </td>
                                   
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($categories)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                        Aucune catégorie trouvée
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