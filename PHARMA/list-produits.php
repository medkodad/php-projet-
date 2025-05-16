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

// Construction de la requête avec filtres
$sql = "SELECT p.*, c.name as category_name 
        FROM products p 
        JOIN categories c ON p.category_id = c.id 
        WHERE 1=1";

$params = [];

// Filtre par catégorie
if (isset($_GET['category_id']) && $_GET['category_id'] != 'all') {
    $sql .= " AND p.category_id = :category_id";
    $params[':category_id'] = $_GET['category_id'];
}

// Filtre par statut
if (isset($_GET['status']) && $_GET['status'] != 'all') {
    switch ($_GET['status']) {
        case 'active':
            $sql .= " AND p.active = 1";
            break;
        case 'inactive':
            $sql .= " AND p.active = 0";
            break;
        case 'low':
            $sql .= " AND p.quantity < 30";
            break;
        case 'expired':
            $sql .= " AND p.expired < CURDATE()";
            break;
    }
}

// Filtre par recherche
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $sql .= " AND (p.name LIKE :search OR p.description LIKE :search OR p.batch_code LIKE :search)";
    $params[':search'] = '%' . $_GET['search'] . '%';
}

$sql .= " ORDER BY p.id DESC";

// Exécution de la requête
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Récupérer les catégories pour le filtre
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Fermer la connexion
$pdo = null;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Produits - PharmaStock</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Styles identiques à votre tableau de bord */
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
        .stock-status {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .status-normal {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        .status-warning {
            background-color: #fff8e1;
            color: #ff8f00;
        }
        .status-critical {
            background-color: #ffebee;
            color: #c62828;
        }
        .status-expired {
            background-color: #f5f5f5;
            color: #616161;
        }
        .badge-active {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        .badge-inactive {
            background-color: #ffebee;
            color: #c62828;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar (identique à votre tableau de bord) -->
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
                            <i class="fas fa-pills text-blue-500 mr-2"></i>
                            Liste des Produits
                        </h1>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <!-- Search -->
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <input type="text" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent sm:text-sm" placeholder="Rechercher un produit...">
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
               
                
                <!-- Products Table -->
                <div class="card-3d bg-white p-6 rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produit</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prix</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expiration</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Catégorie</th>
                                    <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($products as $product): ?>
                                <?php 
                                    // Déterminer le statut du stock
                                    $stockStatus = 'status-normal';
                                    $stockText = $product['quantity'] . ' (OK)';
                                    
                                    if ($product['quantity'] < 10) {
                                        $stockStatus = 'status-critical';
                                        $stockText = $product['quantity'] . ' (CRITIQUE)';
                                    } elseif ($product['quantity'] < 30) {
                                        $stockStatus = 'status-warning';
                                        $stockText = $product['quantity'] . ' (ALERTE)';
                                    }
                                    
                                    // Vérifier si le produit est expiré
                                    $expired = strtotime($product['expired']) < time();
                                    $expiryStatus = $expired ? 'status-expired' : '';
                                ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($product['id']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <?php if (!empty($product['img']) && file_exists($product['img'])): ?>
                                                <img class="h-10 w-10 rounded-lg object-cover" src="<?php echo htmlspecialchars($product['img']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                                <?php else: ?>
                                                <div class="h-10 w-10 rounded-lg bg-gray-200 flex items-center justify-center">
                                                    <i class="fas fa-pills text-gray-500"></i>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($product['name']); ?></div>
                                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($product['batch_code']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate"><?php echo htmlspecialchars($product['description']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo number_format($product['price'], 2); ?> €</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="stock-status <?php echo $stockStatus; ?>"><?php echo $stockText; ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm <?php echo $expired ? 'text-red-600' : 'text-gray-500'; ?>">
                                            <?php echo date('d/m/Y', strtotime($product['expired'])); ?>
                                            <?php if ($expired): ?>
                                            <span class="stock-status status-expired ml-2">EXPIRÉ</span>
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="stock-status <?php echo $product['active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                            <?php echo $product['active'] ? 'ACTIF' : 'INACTIF'; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($product['category_name']); ?>
                                    </td>
                                   
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($products)): ?>
                                <tr>
                                    <td colspan="9" class="px-6 py-4 text-center text-sm text-gray-500">
                                        Aucun produit trouvé
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6 mt-4">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <a href="#" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Précédent
                            </a>
                            <a href="#" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Suivant
                            </a>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Affichage de <span class="font-medium">1</span> à <span class="font-medium"><?php echo count($products); ?></span> sur <span class="font-medium"><?php echo count($products); ?></span> produits
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                    <a href="#" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <span class="sr-only">Précédent</span>
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                    <a href="#" aria-current="page" class="z-10 bg-blue-50 border-blue-500 text-blue-600 relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                        1
                                    </a>
                                    <a href="#" class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                        2
                                    </a>
                                    <a href="#" class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                        3
                                    </a>
                                    <a href="#" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <span class="sr-only">Suivant</span>
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </nav>
                            </div>
                        </div>
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