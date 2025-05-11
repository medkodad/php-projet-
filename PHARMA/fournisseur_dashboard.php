<?php
session_start();

// Vérifier si l'utilisateur est connecté et a le rôle fournisseur
if (!isset($_SESSION['user_id']) {
    header("Location: supplier_login.php");
    exit();
}

// Vérifier le rôle (à adapter selon votre système de rôles)
if ($_SESSION['user_role'] !== 'supplier') {
    header("Location: unauthorized.php");
    exit();
}

// Récupérer les informations du fournisseur
$supplierName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Fournisseur';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Fournisseur - PharmaStock</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .card-3d {
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .card-3d:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        .role-supplier {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        .status-pending {
            background-color: #fff8e1;
            color: #ff8f00;
        }
        .status-approved {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        .status-rejected {
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
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar Fournisseur -->
        <div class="bg-green-800 text-white w-64 flex-shrink-0 flex flex-col">
            <!-- Logo -->
            <div class="flex items-center justify-between p-4 border-b border-green-700">
                <div class="flex items-center">
                    <div class="bg-white p-2 rounded-lg mr-3">
                        <img src="images/logopharma.png" alt="Logo" class="h-8 w-8">
                    </div>
                    <span class="text-xl font-bold">PharmaStock</span>
                </div>
            </div>
            
            <!-- Profil Fournisseur -->
            <div class="p-4 border-b border-green-700 flex items-center">
                <div class="bg-green-700 rounded-full h-10 w-10 flex items-center justify-center mr-3">
                    <i class="fas fa-user-tie text-white"></i>
                </div>
                <div>
                    <p class="font-medium"><?php echo htmlspecialchars($supplierName); ?></p>
                    <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-800">
                        Fournisseur
                    </span>
                </div>
            </div>
            
            <!-- Menu -->
            <nav class="flex-1 overflow-y-auto py-2">
                <div class="px-2 space-y-1">
                    <a href="#" class="bg-green-700 text-white group flex items-center px-4 py-3 text-sm font-medium rounded-md">
                        <i class="fas fa-tachometer-alt mr-3"></i>
                        Tableau de bord
                    </a>
                    
                    <a href="supplier_products.php" class="text-green-200 hover:bg-green-700 hover:text-white group flex items-center px-4 py-3 text-sm font-medium rounded-md">
                        <i class="fas fa-boxes mr-3"></i>
                        Mes Produits
                    </a>
                    
                    <a href="supplier_orders.php" class="text-green-200 hover:bg-green-700 hover:text-white group flex items-center px-4 py-3 text-sm font-medium rounded-md">
                        <i class="fas fa-file-invoice mr-3"></i>
                        Mes Commandes
                    </a>
                    
                    <a href="supplier_stats.php" class="text-green-200 hover:bg-green-700 hover:text-white group flex items-center px-4 py-3 text-sm font-medium rounded-md">
                        <i class="fas fa-chart-line mr-3"></i>
                        Statistiques
                    </a>
                    
                    <a href="supplier_profile.php" class="text-green-200 hover:bg-green-700 hover:text-white group flex items-center px-4 py-3 text-sm font-medium rounded-md">
                        <i class="fas fa-user-cog mr-3"></i>
                        Mon Profil
                    </a>
                </div>
            </nav>
            
            <!-- Déconnexion -->
            <div class="p-4 border-t border-green-700">
                <a href="logout.php" class="group flex items-center text-green-200 hover:text-white">
                    <i class="fas fa-sign-out-alt mr-3"></i>
                    Déconnexion
                </a>
            </div>
        </div>
        
        <!-- Contenu Principal -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Topbar -->
            <header class="bg-white shadow-sm">
                <div class="flex items-center justify-between px-6 py-3">
                    <h1 class="text-xl font-bold text-gray-900">
                        <i class="fas fa-tachometer-alt text-green-600 mr-2"></i>
                        Tableau de Bord Fournisseur
                    </h1>
                    
                    <div class="flex items-center space-x-4">
                        <!-- Notifications -->
                        <div class="relative">
                            <button id="notif-btn" class="p-1 rounded-full text-gray-400 hover:text-gray-500 focus:outline-none">
                                <span class="sr-only">Notifications</span>
                                <i class="fas fa-bell text-xl"></i>
                                <span class="notification-badge absolute top-0 right-0 bg-red-500 rounded-full w-3 h-3"></span>
                            </button>
                            <!-- Dropdown Notifications -->
                            <div id="notif-dropdown" class="hidden absolute right-0 mt-2 w-72 bg-white rounded-md shadow-lg py-1 z-10">
                                <div class="px-4 py-2 border-b border-gray-200">
                                    <p class="text-sm font-medium text-gray-700">Notifications</p>
                                </div>
                                <div class="max-h-60 overflow-y-auto">
                                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 border-b border-gray-100">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0 mt-1">
                                                <i class="fas fa-check-circle text-green-500"></i>
                                            </div>
                                            <div class="ml-3">
                                                <p>Commande #CMD-789 validée</p>
                                                <p class="text-xs text-gray-500">Il y a 2 heures</p>
                                            </div>
                                        </div>
                                    </a>
                                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 border-b border-gray-100">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0 mt-1">
                                                <i class="fas fa-exclamation-triangle text-yellow-500"></i>
                                            </div>
                                            <div class="ml-3">
                                                <p>Stock faible pour Paracétamol</p>
                                                <p class="text-xs text-gray-500">Hier</p>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="px-4 py-2 text-center border-t border-gray-200">
                                    <a href="#" class="text-xs font-medium text-green-600 hover:text-green-800">
                                        Voir toutes les notifications
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Menu Utilisateur -->
                        <div class="relative">
                            <button id="user-menu-btn" class="flex items-center text-sm rounded-full focus:outline-none">
                                <span class="sr-only">Menu Utilisateur</span>
                                <span class="ml-2 text-sm font-medium text-gray-700 hidden md:block">
                                    <?php echo htmlspecialchars($supplierName); ?>
                                </span>
                                <i class="fas fa-chevron-down ml-1 text-gray-400 text-xs"></i>
                            </button>
                            
                            <!-- Dropdown Menu -->
                            <div id="user-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10">
                                <a href="supplier_profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-user mr-2"></i> Mon Profil
                                </a>
                                <a href="logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-sign-out-alt mr-2"></i> Déconnexion
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Contenu -->
            <main class="flex-1 overflow-y-auto p-6 bg-gray-100">
                <!-- Bannière de Bienvenue -->
                <div class="card-3d bg-gradient-to-r from-green-800 to-green-600 p-6 rounded-lg text-white mb-6">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                        <div>
                            <h1 class="text-2xl font-bold mb-2">Bonjour, <?php echo htmlspecialchars($supplierName); ?> !</h1>
                            <p class="opacity-90">Tableau de bord de gestion des produits et commandes</p>
                        </div>
                        <div class="mt-4 md:mt-0">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                <i class="fas fa-truck mr-1"></i> Fournisseur PharmaStock
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Statistiques -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <!-- Produits -->
                    <div class="card-3d bg-white p-6 rounded-lg">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-green-100 p-3 rounded-full">
                                <i class="fas fa-pills text-green-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-gray-500">Produits fournis</h3>
                                <p class="text-2xl font-bold">24</p>
                                <p class="text-xs text-green-600 mt-1">
                                    <i class="fas fa-plus-circle mr-1"></i> 3 nouveaux ce mois
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Commandes -->
                    <div class="card-3d bg-white p-6 rounded-lg">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-blue-100 p-3 rounded-full">
                                <i class="fas fa-file-invoice text-blue-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-gray-500">Commandes ce mois</h3>
                                <p class="text-2xl font-bold">14</p>
                                <p class="text-xs text-blue-600 mt-1">
                                    <i class="fas fa-chart-line mr-1"></i> +20% vs mois dernier
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Revenus -->
                    <div class="card-3d bg-white p-6 rounded-lg">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-purple-100 p-3 rounded-full">
                                <i class="fas fa-euro-sign text-purple-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-gray-500">Revenus ce mois</h3>
                                <p class="text-2xl font-bold">8 245,50 €</p>
                                <p class="text-xs text-purple-600 mt-1">
                                    <i class="fas fa-arrow-up mr-1"></i> 15% vs mois dernier
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Commandes Récentes -->
                <div class="card-3d bg-white p-6 rounded-lg mb-8">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-green-800">Commandes récentes</h2>
                        <a href="supplier_orders.php" class="text-sm font-medium text-green-600 hover:text-green-800">
                            Voir toutes les commandes
                        </a>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">N° Commande</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Produits</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Montant</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <a href="order_details.php?id=7894" class="text-green-600 hover:text-green-800">#CMD-7894</a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">15/05/2023</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">5 produits</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">1 245,50 €</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Livrée
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="order_details.php?id=7894" class="text-green-600 hover:text-green-800 mr-3">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <a href="order_details.php?id=7893" class="text-green-600 hover:text-green-800">#CMD-7893</a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">12/05/2023</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">8 produits</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">876,30 €</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            En cours
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="order_details.php?id=7893" class="text-green-600 hover:text-green-800 mr-3">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <a href="order_details.php?id=7892" class="text-green-600 hover:text-green-800">#CMD-7892</a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">10/05/2023</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">3 produits</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">342,75 €</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            Annulée
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="order_details.php?id=7892" class="text-green-600 hover:text-green-800 mr-3">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Produits à Réapprovisionner -->
                <div class="card-3d bg-white p-6 rounded-lg mb-8">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-green-800">Produits à réapprovisionner</h2>
                        <a href="supplier_products.php" class="text-sm font-medium text-green-600 hover:text-green-800">
                            Voir tous les produits
                        </a>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Produit</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Seuil</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 bg-red-100 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-pills text-red-500"></i>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">Paracétamol 500mg</div>
                                                <div class="text-sm text-gray-500">REF: MED-1234</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">12</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">50</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="#" class="text-green-600 hover:text-green-800 mr-3">
                                            <i class="fas fa-truck-loading"></i> Commander
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-capsules text-yellow-500"></i>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">Ibuprofène 200mg</div>
                                                <div class="text-sm text-gray-500">REF: MED-5678</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">32</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">50</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="#" class="text-green-600 hover:text-green-800 mr-3">
                                            <i class="fas fa-truck-loading"></i> Commander
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Statistiques de Ventes -->
                <div class="card-3d bg-white p-6 rounded-lg">
                    <h2 class="text-xl font-bold text-green-800 mb-6">Statistiques de ventes (30 jours)</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Graphique -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <canvas id="salesChart" height="250"></canvas>
                        </div>
                        
                        <!-- Top Produits -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Top 3 des produits</h3>
                            <div class="space-y-4">
                                <div class="flex items-center">
                                    <span class="flex-shrink-0 bg-green-600 text-white h-8 w-8 rounded-full flex items-center justify-center mr-3">
                                        1
                                    </span>
                                    <div class="flex-grow">
                                        <h4 class="text-sm font-medium text-gray-900">Paracétamol 500mg</h4>
                                        <p class="text-xs text-gray-500">120 unités vendues</p>
                                    </div>
                                    <span class="text-sm font-medium text-green-600">
                                        +15%
                                    </span>
                                </div>
                                <div class="flex items-center">
                                    <span class="flex-shrink-0 bg-blue-600 text-white h-8 w-8 rounded-full flex items-center justify-center mr-3">
                                        2
                                    </span>
                                    <div class="flex-grow">
                                        <h4 class="text-sm font-medium text-gray-900">Ibuprofène 200mg</h4>
                                        <p class="text-xs text-gray-500">98 unités vendues</p>
                                    </div>
                                    <span class="text-sm font-medium text-green-600">
                                        +8%
                                    </span>
                                </div>
                                <div class="flex items-center">
                                    <span class="flex-shrink-0 bg-purple-600 text-white h-8 w-8 rounded-full flex items-center justify-center mr-3">
                                        3
                                    </span>
                                    <div class="flex-grow">
                                        <h4 class="text-sm font-medium text-gray-900">Vitamine D3</h4>
                                        <p class="text-xs text-gray-500">75 unités vendues</p>
                                    </div>
                                    <span class="text-sm font-medium text-green-600">
                                        +22%
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Menu utilisateur
        document.getElementById('user-menu-btn').addEventListener('click', function() {
            document.getElementById('user-menu').classList.toggle('hidden');
        });

        // Notifications
        document.getElementById('notif-btn').addEventListener('click', function() {
            document.getElementById('notif-dropdown').classList.toggle('hidden');
        });

        // Fermer les menus quand on clique ailleurs
        document.addEventListener('click', function(event) {
            if (!document.getElementById('user-menu-btn').contains(event.target)) {
                document.getElementById('user-menu').classList.add('hidden');
            }
            if (!document.getElementById('notif-btn').contains(event.target)) {
                document.getElementById('notif-dropdown').classList.add('hidden');
            }
        });

        // Graphique des ventes
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: ['Sem 1', 'Sem 2', 'Sem 3', 'Sem 4'],
                datasets: [{
                    label: 'Ventes hebdomadaires',
                    data: [12, 19, 15, 22],
                    backgroundColor: 'rgba(46, 125, 50, 0.2)',
                    borderColor: 'rgba(46, 125, 50, 1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Nombre de commandes'
                        }
                    }
                }
            }
        });

        // Simuler la disparition de la notification après 5s
        setTimeout(() => {
            const notifBadge = document.querySelector('.notification-badge');
            if (notifBadge) {
                notifBadge.style.display = 'none';
            }
        }, 5000);
    </script>
</body>
</html>