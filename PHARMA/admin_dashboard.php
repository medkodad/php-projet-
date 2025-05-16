<?php
// Bda l'page dyalna, kanbdaw b session_start() bach n7fdo l'etat dyal l'utilisateur
session_start();

// Kanchofo wach l'utilisateur connecté wla la
if (!isset($_SESSION['user_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Had l'code kay3ti message d'accueil l'admin
$welcomeMessage = "Bienvenue ";
if (isset($_SESSION['user_name'])) {
    $welcomeMessage .= $_SESSION['user_name'];
}
if (isset($_SESSION['user_role'])) {
    $welcomeMessage .= " (" . $_SESSION['user_role'] . ")";
}

// Connexion l database
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

// Had l'query kayjib l'statistiques dyal l'produits
// Kay7sbo:
// - Total dyal l'produits
// - Produits li 3ndhom stock 9lil (moins de 30)
// - Produits li expirés
$productStats = $pdo->query("
    SELECT 
        COUNT(*) as total_products,
        SUM(CASE WHEN quantity < 30 THEN 1 ELSE 0 END) as low_stock_products,
        SUM(CASE WHEN expired < CURDATE() THEN 1 ELSE 0 END) as expired_products
    FROM products
")->fetch();

// Had l'query kayjib l'statistiques dyal l'catégories
// Kay7sbo:
// - Total dyal l'catégories
// - Nombre dyal sous-catégories
$categoryStats = $pdo->query("
    SELECT 
        COUNT(*) as total_categories,
        COUNT(CASE WHEN parent_id IS NOT NULL THEN 1 END) as subcategories
    FROM categories
")->fetch();

// Had l'query kayjib l'statistiques dyal l'stock
// Kay7sbo:
// - Total dyal l'articles f stock
// - Nombre dyal l'articles f niveau critique
// - Moyenne dyal l'niveau dyal stock
$stockStats = $pdo->query("
    SELECT 
        COUNT(*) as total_stock_items,
        SUM(CASE WHEN current_quantity <= critical_level THEN 1 ELSE 0 END) as critical_stock,
        AVG((current_quantity / capacity) * 100) as avg_stock_level
    FROM stock
")->fetch();

// Had l'query kayjib l'statistiques dyal l'fournisseurs
// Kay7sbo:
// - Total dyal l'fournisseurs
$supplierStats = $pdo->query("
    SELECT COUNT(*) as total_suppliers FROM fournisseur
")->fetch();

// Had l'query kayjib l'produits li tzado 9lil
// Kayjib:
// - Smiya dyal l'produit
// - Catégorie dyal l'produit
// - Quantité dyal stock
$recentProducts = $pdo->query("
    SELECT p.*, c.name as category_name, s.current_quantity
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN stock s ON p.id = s.product_id
    ORDER BY p.id DESC
    LIMIT 5
")->fetchAll();

// Had l'query kayjib l'produits li f niveau critique
// Kayjib:
// - Smiya dyal l'produit
// - Quantité actuelle
// - Niveau critique
$criticalProducts = $pdo->query("
    SELECT p.*, s.current_quantity, s.critical_level
    FROM products p
    JOIN stock s ON p.id = s.product_id
    WHERE s.current_quantity <= s.critical_level
    ORDER BY s.current_quantity ASC
    LIMIT 3
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Admin - SMART PHARMA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            light: '#e3f2fd',
                            dark: '#0d47a1',
                        },
                        accent: '#2196f3',
                        success: '#4CAF50',
                        warning: '#FFC107',
                        error: '#F44336',
                    },
                    boxShadow: {
                        '3d': '0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
                        '3d-hover': '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
                    },
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.css">
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
        .nav-tab {
            transition: all 0.3s ease;
        }
        .nav-tab:hover {
            border-bottom: 3px solid #e3f2fd;
        }
        .nav-tab.active {
            border-bottom: 3px solid #2196f3;
            color: #0d47a1;
            font-weight: 600;
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
        .notification-badge {
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
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
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <div class="bg-primary-dark text-white w-64 flex-shrink-0 flex flex-col">
            <!-- Logo -->
            <div class="flex items-center justify-between p-4 border-b border-primary-light">
                <div class="flex items-center">
                    <div class="bg-white p-2 rounded-lg mr-3">
                        <img src="images/logopharma.png" alt="Logo" class="h-8 w-8">
                    </div>
                    <span class="text-xl font-bold">SMART PHARMA</span>
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
                            <i class="fas fa-tachometer-alt text-accent mr-2"></i>
                            Tableau de Bord - SMART PHARMA
                        </h1>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <!-- Search -->
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <input type="text" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent sm:text-sm" placeholder="Rechercher...">
                        </div>
                        
                        <!-- Notifications -->
                        <div class="relative">
                            <button class="p-1 rounded-full text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-accent">
                                <span class="sr-only">Notifications</span>
                                <i class="fas fa-bell text-xl"></i>
                                <span class="notification-badge absolute top-0 right-0 bg-error rounded-full w-3 h-3"></span>
                            </button>
                        </div>
                        
                        <!-- User Menu -->
                        <div class="relative">
                            <button id="user-menu-button" class="flex items-center focus:outline-none">
                                <span class="sr-only">Open user menu</span>
                                <span class="ml-2 text-sm font-medium text-gray-700 hidden md:block"><?php echo isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Admin'; ?></span>
                                <i class="fas fa-chevron-down ml-1 text-gray-400 text-xs"></i>
                            </button>
                            
                            <!-- Dropdown menu -->
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
                <!-- Welcome Banner -->
                <div class="card-3d bg-gradient-to-r from-primary-dark to-accent p-6 rounded-lg text-white mb-6">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                        <div>
                            <h1 class="text-2xl font-bold mb-2"><?php echo $welcomeMessage; ?></h1>
                            <p class="opacity-90">Tableau de bord de gestion des produits et stocks</p>
                        </div>
                        <div class="mt-4 md:mt-0">
                            <span class="role-badge role-admin">
                                <i class="fas fa-user-shield mr-1"></i> Administrateur
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="card-3d bg-white p-6 rounded-lg">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-blue-100 p-3 rounded-full">
                                <i class="fas fa-pills text-blue-500 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-gray-500">Produits actifs</h3>
                                <p class="text-2xl font-bold"><?php echo $productStats['total_products']; ?></p>
                                <p class="text-xs text-blue-500 mt-1">
                                    <i class="fas fa-exclamation-triangle mr-1"></i> <?php echo $productStats['low_stock_products']; ?> en stock faible
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-3d bg-white p-6 rounded-lg">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-green-100 p-3 rounded-full">
                                <i class="fas fa-tags text-green-500 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-gray-500">Catégories</h3>
                                <p class="text-2xl font-bold"><?php echo $categoryStats['total_categories']; ?></p>
                                <p class="text-xs text-green-500 mt-1">
                                    <i class="fas fa-sitemap mr-1"></i> <?php echo $categoryStats['subcategories']; ?> sous-catégories
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-3d bg-white p-6 rounded-lg">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-purple-100 p-3 rounded-full">
                                <i class="fas fa-box-open text-purple-500 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-gray-500">Stock moyen</h3>
                                <p class="text-2xl font-bold"><?php echo round($stockStats['avg_stock_level'], 1); ?>%</p>
                                <p class="text-xs text-purple-500 mt-1">
                                    <i class="fas fa-exclamation-circle mr-1"></i> <?php echo $stockStats['critical_stock']; ?> produits critiques
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-3d bg-white p-6 rounded-lg">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-yellow-100 p-3 rounded-full">
                                <i class="fas fa-truck text-yellow-500 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-gray-500">Fournisseurs</h3>
                                <p class="text-2xl font-bold"><?php echo $supplierStats['total_suppliers']; ?></p>
                                <p class="text-xs text-yellow-500 mt-1">
                                    <i class="fas fa-boxes mr-1"></i> <?php echo $stockStats['total_stock_items']; ?> articles en stock
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Charts Row -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    
                   
                
                <!-- Recent Products -->
                <div class="card-3d bg-white p-6 rounded-lg mb-8">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-primary-dark">Produits récemment ajoutés</h2>
                        <a href="list-produits.php" class="text-sm font-medium text-accent hover:text-primary-dark">Voir tous les produits</a>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produit</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Catégorie</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($recentProducts as $product): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-pills text-blue-500"></i>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($product['name']); ?></div>
                                                <div class="text-sm text-gray-500">REF: <?php echo htmlspecialchars($product['batch_code']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($product['category_name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="stock-status <?php echo $product['current_quantity'] < 30 ? 'status-warning' : 'status-normal'; ?>">
                                            <?php echo $product['current_quantity']; ?> (<?php echo $product['current_quantity'] < 30 ? 'ALERTE' : 'OK'; ?>)
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Supplier Actions -->
             

    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    <script>
        // Toggle user menu
        document.getElementById('user-menu-button').addEventListener('click', function() {
            document.getElementById('user-menu-dropdown').classList.toggle('hidden');
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            const userMenu = document.getElementById('user-menu-dropdown');
            const button = document.getElementById('user-menu-button');
            
            if (!button.contains(event.target)) {
                userMenu.classList.add('hidden');
            }
        });
        
        // Stock Chart
        const stockCtx = document.getElementById('stockChart').getContext('2d');
        const stockChart = new Chart(stockCtx, {
            type: 'bar',
            data: {
                labels: ['Sem 1', 'Sem 2', 'Sem 3', 'Sem 4'],
                datasets: [
                    {
                        label: 'Stock normal',
                        data: [85, 79, 82, 78],
                        backgroundColor: '#4CAF50',
                        borderColor: '#2e7d32',
                        borderWidth: 1
                    },
                    {
                        label: 'Stock alerte',
                        data: [10, 15, 12, 14],
                        backgroundColor: '#FFC107',
                        borderColor: '#ff8f00',
                        borderWidth: 1
                    },
                    {
                        label: 'Stock critique',
                        data: [5, 6, 6, 8],
                        backgroundColor: '#F44336',
                        borderColor: '#c62828',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Pourcentage du stock'
                        }
                    }
                }
            }
        });
        
        // Simulate notification
        setTimeout(() => {
            const notificationBadge = document.querySelector('.notification-badge');
            notificationBadge.classList.remove('bg-error');
            notificationBadge.classList.add('bg-gray-400');
            notificationBadge.style.animation = 'none';
        }, 5000);
    </script>
</body>
</html>