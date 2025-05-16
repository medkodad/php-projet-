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

// Traitement de l'ajout de stock
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_stock'])) {
    $product_id = $_POST['product_id'];
    $supplier_id = $_POST['supplier_id'] ?? null;
    $current_quantity = $_POST['current_quantity'];
    $critical_level = $_POST['critical_level'];
    $capacity = $_POST['capacity'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO stock (product_id, supplier_id, current_quantity, critical_level, capacity, last_restock_date, status) 
                              VALUES (:product_id, :supplier_id, :current_quantity, :critical_level, :capacity, NOW(), 'normal')");
        $stmt->execute([
            ':product_id' => $product_id,
            ':supplier_id' => $supplier_id,
            ':current_quantity' => $current_quantity,
            ':critical_level' => $critical_level,
            ':capacity' => $capacity
        ]);
        
        $_SESSION['success_message'] = "Le stock a été ajouté avec succès!";
        header("Location: stock_management.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Erreur lors de l'ajout du stock: " . $e->getMessage();
    }
}

// Construction de la requête avec jointures
$sql = "SELECT s.*, 
               p.name as product_name, p.img as product_img, p.price as product_price,
               f.supplier_name,
               c.name as category_name
        FROM stock s
        JOIN products p ON s.product_id = p.id
        LEFT JOIN fournisseur f ON s.supplier_id = f.supplier_id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE 1=1";

$params = [];

// Filtres
if (isset($_GET['status']) && $_GET['status'] != 'all') {
    $status_condition = "";
    switch ($_GET['status']) {
        case 'normal':
            $status_condition = "s.current_quantity > s.critical_level";
            break;
        case 'low':
            $status_condition = "s.current_quantity <= s.critical_level AND s.current_quantity > 0";
            break;
        case 'critical':
            $status_condition = "s.current_quantity <= s.critical_level AND s.current_quantity > 0";
            break;
        case 'out_of_stock':
            $status_condition = "s.current_quantity <= 0";
            break;
    }
    $sql .= " AND " . $status_condition;
}

if (isset($_GET['supplier_id']) && $_GET['supplier_id'] != 'all') {
    $sql .= " AND s.supplier_id = :supplier_id";
    $params[':supplier_id'] = $_GET['supplier_id'];
}

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $sql .= " AND (p.name LIKE :search OR s.stock_id LIKE :search)";
    $params[':search'] = '%' . $_GET['search'] . '%';
}

$sql .= " ORDER BY s.current_quantity ASC, s.last_restock_date DESC";

// Exécution de la requête
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$stockItems = $stmt->fetchAll();

// Récupérer les fournisseurs pour le filtre
$fournisseurs = $pdo->query("SELECT supplier_id, supplier_name FROM fournisseur ORDER BY supplier_name")->fetchAll();

// Récupérer les produits pour le formulaire d'ajout
$products = $pdo->query("SELECT id, name FROM products ORDER BY name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Stocks - PharmaStock</title>
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
        .progress-bar {
            height: 0.5rem;
            border-radius: 0.25rem;
            overflow: hidden;
            background-color: #e5e7eb;
        }
        .progress-bar-fill {
            height: 100%;
            transition: width 0.3s ease;
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
                            <i class="fas fa-boxes text-blue-500 mr-2"></i>
                            Gestion des Stocks
                        </h1>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <!-- Search -->
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <input type="text" id="search-input" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent sm:text-sm" placeholder="Rechercher...">
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
            
            <br>
                
                <!-- Filters -->
                <div class="card-3d bg-white p-4 rounded-lg mb-6">
                    <form method="GET" action="stock_management.php">
                        <div class="flex flex-col md:flex-row md:items-center md:space-x-6 space-y-4 md:space-y-0">
                           <div class="flex-1">
    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
    <select 
        id="status" 
        name="status" 
        class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
    >
        <option value="all" <?= (empty($_GET['status']) || $_GET['status'] === 'all') ? 'selected' : '' ?>>Tous les statuts</option>
        <option value="normal" <?= (isset($_GET['status']) && $_GET['status'] === 'normal') ? 'selected' : '' ?>>Normal</option>
        <option value="low" <?= (isset($_GET['status']) && $_GET['status'] === 'low') ? 'selected' : '' ?>>Faible</option>
        <option value="critical" <?= (isset($_GET['status']) && $_GET['status'] === 'critical') ? 'selected' : '' ?>>Critique</option>
        <option value="out_of_stock" <?= (isset($_GET['status']) && $_GET['status'] === 'out_of_stock') ? 'selected' : '' ?>>Épuisé</option>
    </select>
</div>
                            
                            <div class="flex-1">
                                <label for="supplier_id" class="block text-sm font-medium text-gray-700 mb-1">Fournisseur</label>
                                <select id="supplier_id" name="supplier_id" class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                    <option value="all">Tous les fournisseurs</option>
                                    <?php foreach ($fournisseurs as $fournisseur): ?>
                                        <option value="<?php echo $fournisseur['supplier_id']; ?>" <?php echo (isset($_GET['supplier_id']) && $_GET['supplier_id'] == $fournisseur['supplier_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($fournisseur['supplier_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="flex-1">
                                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Recherche</label>
                                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md" placeholder="ID stock ou nom produit">
                            </div>
                            
                            <div class="flex items-end space-x-2">
                                <button type="submit" class="btn-3d bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg">
                                    <i class="fas fa-filter mr-2"></i> Filtrer
                                </button>
                                <a href="stock_management.php" class="btn-3d bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded-lg">
                                    <i class="fas fa-times mr-2"></i> Réinitialiser
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Stock Table -->
                
                    
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
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Stock</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produit</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fournisseur</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Niveau critique</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dernière réappro</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                    <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($stockItems as $item): ?>
                                <?php 
                                    // Déterminer le statut du stock
                                    $stockStatus = 'status-normal';
                                    $stockText = 'NORMAL';
                                    $progressColor = 'bg-green-500';
                                    $percentage = ($item['current_quantity'] / $item['capacity']) * 100;
                                    
                                    if ($item['current_quantity'] <= 0) {
                                        $stockStatus = 'status-critical';
                                        $stockText = 'ÉPUISÉ';
                                        $progressColor = 'bg-gray-500';
                                        $percentage = 0;
                                    } elseif ($item['current_quantity'] <= $item['critical_level']) {
                                        $stockStatus = 'status-critical';
                                        $stockText = 'CRITIQUE';
                                        $progressColor = 'bg-red-500';
                                    } elseif ($item['current_quantity'] <= ($item['critical_level'] * 2)) {
                                        $stockStatus = 'status-warning';
                                        $stockText = 'FAIBLE';
                                        $progressColor = 'bg-yellow-500';
                                    }
                                ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($item['stock_id']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <?php if (!empty($item['product_img']) && file_exists($item['product_img'])): ?>
                                                <img class="h-10 w-10 rounded-lg object-cover" src="<?php echo htmlspecialchars($item['product_img']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                                <?php else: ?>
                                                <div class="h-10 w-10 rounded-lg bg-gray-200 flex items-center justify-center">
                                                    <i class="fas fa-pills text-gray-500"></i>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($item['category_name']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo $item['supplier_name'] ? htmlspecialchars($item['supplier_name']) : '—'; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-24 mr-4">
                                                <div class="progress-bar">
                                                    <div class="progress-bar-fill <?php echo $progressColor; ?>" style="width: <?php echo $percentage; ?>%"></div>
                                                </div>
                                            </div>
                                            <div class="text-sm text-gray-900">
                                                <?php echo $item['current_quantity']; ?> / <?php echo $item['capacity']; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo $item['critical_level']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo $item['last_restock_date'] ? date('d/m/Y', strtotime($item['last_restock_date'])) : '—'; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="stock-status <?php echo $stockStatus; ?>"><?php echo $stockText; ?></span>
                                    </td>
                                   
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($stockItems)): ?>
                                <tr>
                                    <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">
                                        Aucun enregistrement de stock trouvé
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
                                    Affichage de <span class="font-medium">1</span> à <span class="font-medium"><?php echo count($stockItems); ?></span> sur <span class="font-medium"><?php echo count($stockItems); ?></span> résultats
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

        // Live search functionality
        document.getElementById('search-input').addEventListener('input', function() {
            const searchValue = this.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchValue) ? '' : 'none';
            });
        });
    </script>
</body>
</html>