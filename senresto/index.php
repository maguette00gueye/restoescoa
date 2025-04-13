<?php
// Démarrer la session
//session_start();

// Inclure la connexion à la base de données
include "config/database.php";

// Vérifier si l'utilisateur est connecté
include "auth_check.php";

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    // Rediriger vers la page de connexion
    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

// Récupération des statistiques globales
try {
    // Nombre total d'utilisateurs
    $sql_users = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN type_utilisateur = 'etudiant' THEN 1 ELSE 0 END) as etudiants,
        SUM(CASE WHEN type_utilisateur = 'personnel' OR type_utilisateur = 'professeur' THEN 1 ELSE 0 END) as personnel,
        SUM(CASE WHEN type_utilisateur = 'employe' THEN 1 ELSE 0 END) as employes
    FROM utilisateurs";
    $stmt_users = $conn->prepare($sql_users);
    $stmt_users->execute();
    $stats_users = $stmt_users->fetch(PDO::FETCH_ASSOC);
    
    // Nombre de plats par catégorie
    $sql_menu = "SELECT 
        COUNT(*) as total_plats,
        SUM(CASE WHEN categorie = 'repas' THEN 1 ELSE 0 END) as plats_principaux,
        SUM(CASE WHEN categorie = 'Petit_dejeuner' THEN 1 ELSE 0 END) as petit_dejeuner,
        SUM(CASE WHEN categorie = 'diner' THEN 1 ELSE 0 END) as diner
    FROM menu_items";
    $stmt_menu = $conn->prepare($sql_menu);
    $stmt_menu->execute();
    $stats_menu = $stmt_menu->fetch(PDO::FETCH_ASSOC);
    
    // Boissons et articles
    $sql_boissons = "SELECT COUNT(*) as total FROM ab_items";
    $stmt_boissons = $conn->prepare($sql_boissons);
    $stmt_boissons->execute();
    $stats_boissons = $stmt_boissons->fetch(PDO::FETCH_ASSOC);
    
    // Fast food
    $sql_ff = "SELECT COUNT(*) as total FROM ff_items";
    $stmt_ff = $conn->prepare($sql_ff);
    $stmt_ff->execute();
    $stats_ff = $stmt_ff->fetch(PDO::FETCH_ASSOC);
    
    // Derniers utilisateurs inscrits
    $sql_recent_users = "SELECT id, nom, prenom, email, type_utilisateur, created_at 
                        FROM utilisateurs 
                        ORDER BY created_at DESC 
                        LIMIT 5";
    $stmt_recent_users = $conn->prepare($sql_recent_users);
    $stmt_recent_users->execute();
    $recent_users = $stmt_recent_users->fetchAll(PDO::FETCH_ASSOC);
    
    // Plats du jour
    $today = date('Y-m-d');
    $sql_daily_menu = "SELECT * FROM menu_items WHERE date = :today ORDER BY heure";
    $stmt_daily_menu = $conn->prepare($sql_daily_menu);
    $stmt_daily_menu->bindParam(':today', $today);
    $stmt_daily_menu->execute();
    $daily_menu = $stmt_daily_menu->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error_message = "Erreur de base de données: " . $e->getMessage();
}

// Définir les couleurs pour les graphiques
$chart_colors = [
    "#2b5797", "#00a300", "#ff0097", "#9f00a7", "#1e7145", "#00aba9", 
    "#ffc40d", "#e3a21a", "#da532c", "#b91d47", "#603cba", "#00aff0"
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <?php include "head.php"; ?>
    <title>Tableau de Bord - Restaurant ESCOA</title>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Styles des cartes et widgets */
        .card-box {
            transition: all 0.3s;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .card-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        /* Widget personnalisé */
        .widget-style-custom {
            border-radius: 10px;
            overflow: hidden;
            position: relative;
        }
        .widget-style-custom .widget-icon {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            opacity: 0.2;
            font-size: 60px;
        }
        .widget-style-custom:hover .widget-icon {
            opacity: 0.4;
        }
        
        /* Styles des cartes de menu */
        .menu-item-card {
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s;
            height: 100%;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        }
        .menu-item-card:hover {
            transform: scale(1.02);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .menu-item-img {
            height: 180px;
            object-fit: cover;
            width: 100%;
        }
        
        /* Éléments UI */
        .progress {
            height: 8px;
            border-radius: 4px;
        }
        .activity-dot {
            height: 12px;
            width: 12px;
            border-radius: 50%;
            display: inline-block;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        /* Boutons d'action rapide */
        .action-btn {
            transition: all 0.3s;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.08);
            height: 100%;
        }
        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>

<body>
    <?php include "chargement.php"; ?>

    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <div class="menu-icon dw dw-menu"></div>
            <div class="search-toggle-icon dw dw-search2" data-toggle="header_search"></div>
            <div class="header-search">
                <form>
                    <div class="form-group mb-0">
                        <i class="dw dw-search2 search-icon"></i>
                        <input type="text" class="form-control search-input" placeholder="Rechercher...">
                    </div>
                </form>
            </div>
        </div>
        
        <div class="header-right">
            <!-- Paramètres -->
            <div class="dashboard-setting user-notification">
                <div class="dropdown">
                    <a class="dropdown-toggle no-arrow" href="javascript:;" data-toggle="right-sidebar">
                        <i class="dw dw-settings2"></i>
                    </a>
                </div>
            </div>
            
            <!-- Notifications -->
            <div class="user-notification">
                <div class="dropdown">
                    <a class="dropdown-toggle no-arrow" href="#" role="button" data-toggle="dropdown">
                        <i class="icon-copy dw dw-notification"></i>
                        <span class="badge notification-active"></span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right">
                        <div class="notification-list mx-h-350 customscroll">
                            <ul>
                                <li>
                                    <a href="#">
                                        <img src="vendors/images/img.jpg" alt="">
                                        <h3>Nouveau plat ajouté</h3>
                                        <p>Thiebou Djeun au menu du jour</p>
                                    </a>
                                </li>
                                <li>
                                    <a href="#">
                                        <img src="vendors/images/photo1.jpg" alt="">
                                        <h3>Nouvel utilisateur</h3>
                                        <p>Un nouveau compte étudiant a été créé</p>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Infos utilisateur -->
            <div class="user-info-dropdown">
                <div class="dropdown">
                    <a class="dropdown-toggle" href="#" role="button" data-toggle="dropdown">
                        <span class="user-icon">
                            <img src="vendors/images/femme.png" alt="">
                        </span>
                        <span class="user-name">
                            <?php echo isset($_SESSION['user_nom']) ? $_SESSION['user_nom'].' '.$_SESSION['user_prenom'] : 'Administrateur'; ?>
                        </span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right dropdown-menu-icon-list">
                        <a class="dropdown-item" href="profile.php"><i class="dw dw-user1"></i> Profil</a>
                        <a class="dropdown-item" href="settings.php"><i class="dw dw-settings2"></i> Paramètres</a>
                        <a class="dropdown-item" href="logout.php"><i class="dw dw-logout"></i> Déconnexion</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="left-side-bar">
        <div class="brand-logo">
            <a href="index.php">
                <img src="vendors/images/logo_escoa.png" alt="Logo Restaurant ESCOA" class="dark-logo">
                <img src="vendors/images/logo_escoa_white.png" alt="Logo Restaurant ESCOA" class="light-logo">
            </a>
            <div class="close-sidebar" data-toggle="left-sidebar-close">
                <i class="ion-close-round"></i>
            </div>
        </div>
        <div class="menu-block customscroll">
            <?php include "menu.php"; ?>
        </div>
    </div>
    <div class="mobile-menu-overlay"></div>

    <!-- Contenu principal -->
    <div class="main-container">
        <div class="pd-ltr-20">
            <!-- Message de bienvenue -->
            <div class="row">
                <div class="col-xl-12 mb-30">
                    <div class="card-box height-100-p pd-20">
                        <h2 class="h3 mb-20">Bienvenue sur le Tableau de Bord ESCOA</h2>
                        <div class="alert alert-info">
                            <i class="icon-copy dw dw-information mr-2"></i>
                            <strong>Bonjour <?php echo isset($_SESSION['user_prenom']) ? $_SESSION['user_prenom'] : 'Administrateur'; ?></strong>, 
                            voici un aperçu de l'activité du restaurant pour aujourd'hui, <?php echo date('d/m/Y'); ?>. 
                            Utilisez les cartes ci-dessous pour accéder rapidement aux fonctionnalités.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistiques principales -->
            <div class="row">
                <!-- Utilisateurs -->
                <div class="col-xl-3 col-lg-3 col-md-6 mb-20">
                    <div class="card-box height-100-p widget-style-custom bg-gradient-primary" style="background: linear-gradient(60deg, #2b5797, #1e88e5);">
                        <div class="d-flex flex-wrap align-items-center p-3">
                            <div class="widget-data text-white">
                                <div class="h1 mb-0 font-weight-bold"><?php echo number_format($stats_users['total']); ?></div>
                                <div class="weight-600">Utilisateurs</div>
                                <div class="small">+<?php echo rand(2, 15); ?> cette semaine</div>
                            </div>
                            <div class="widget-icon">
                                <i class="icon-copy dw dw-user-2 text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Plats -->
                <div class="col-xl-3 col-lg-3 col-md-6 mb-20">
                    <div class="card-box height-100-p widget-style-custom bg-gradient-success" style="background: linear-gradient(60deg, #00a300, #4caf50);">
                        <div class="d-flex flex-wrap align-items-center p-3">
                            <div class="widget-data text-white">
                                <div class="h1 mb-0 font-weight-bold"><?php echo number_format($stats_menu['total_plats']); ?></div>
                                <div class="weight-600">Plats au Menu</div>
                                <div class="small"><?php echo $stats_menu['plats_principaux']; ?> plats principaux</div>
                            </div>
                            <div class="widget-icon">
                                <i class="icon-copy dw dw-restaurant text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Boissons -->
                <div class="col-xl-3 col-lg-3 col-md-6 mb-20">
                    <div class="card-box height-100-p widget-style-custom bg-gradient-warning" style="background: linear-gradient(60deg, #ffc40d, #ff9800);">
                        <div class="d-flex flex-wrap align-items-center p-3">
                            <div class="widget-data text-white">
                                <div class="h1 mb-0 font-weight-bold"><?php echo number_format($stats_boissons['total']); ?></div>
                                <div class="weight-600">Boissons</div>
                                <div class="small">Pour tous les goûts</div>
                            </div>
                            <div class="widget-icon">
                                <i class="icon-copy dw dw-cocktail text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Fast Food -->
                <div class="col-xl-3 col-lg-3 col-md-6 mb-20">
                    <div class="card-box height-100-p widget-style-custom bg-gradient-danger" style="background: linear-gradient(60deg, #da532c, #ff5722);">
                        <div class="d-flex flex-wrap align-items-center p-3">
                            <div class="widget-data text-white">
                                <div class="h1 mb-0 font-weight-bold"><?php echo number_format($stats_ff['total']); ?></div>
                                <div class="weight-600">Fast Food</div>
                                <div class="small">Options rapides</div>
                            </div>
                            <div class="widget-icon">
                                <i class="icon-copy dw dw-hot-dog text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Menu du jour et graphique -->
            <div class="row">
				<!-- Graphique évolutif des ventes -->
<div class="col-xl-8 mb-30">
    <div class="card-box height-100-p pd-20">
        <div class="d-flex justify-content-between pb-10">
            <h4 class="h4 text-blue">Évolution des Ventes par Catégorie</h4>
            <div class="dropdown">
                <a class="btn btn-light btn-sm dropdown-toggle" href="#" role="button" data-toggle="dropdown">
                    <i class="fa fa-ellipsis-h"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <a class="dropdown-item" href="all_menu.php"><i class="dw dw-edit2"></i> Gérer les menus</a>
                    <a class="dropdown-item" href="new_menu.php"><i class="dw dw-add"></i> Ajouter un plat</a>
                    <a class="dropdown-item" href="rapports.php"><i class="dw dw-analytics-21"></i> Rapports détaillés</a>
                </div>
            </div>
        </div>
        
        <div class="row mb-3">
            <div class="col-md-4">
                <select id="salesChartYear" class="form-control">
                    <option value="2025">2025</option>
                    <option value="2024" selected>2024</option>
                    <option value="2023">2023</option>
                </select>
            </div>
            <div class="col-md-8">
                <div class="btn-group btn-group-toggle" data-toggle="buttons">
                    <label class="btn btn-outline-primary">
                        <input type="radio" name="chartViewType" id="option1" value="daily"> Quotidien
                    </label>
                    <label class="btn btn-outline-primary">
                        <input type="radio" name="chartViewType" id="option2" value="weekly"> Hebdomadaire
                    </label>
                    <label class="btn btn-outline-primary active">
                        <input type="radio" name="chartViewType" id="option3" value="monthly" checked> Mensuel
                    </label>
                    <label class="btn btn-outline-primary">
                        <input type="radio" name="chartViewType" id="option4" value="yearly"> Annuel
                    </label>
                </div>
            </div>
        </div>
        
        <!-- Sélecteur de mois (visible uniquement en mode quotidien ou hebdomadaire) -->
        <div class="row mb-3" id="monthSelector" style="display: none;">
            <div class="col-md-6">
                <select id="selectedMonth" class="form-control">
                    <option value="1">Janvier</option>
                    <option value="2">Février</option>
                    <option value="3">Mars</option>
                    <option value="4">Avril</option>
                    <option value="5">Mai</option>
                    <option value="6">Juin</option>
                    <option value="7">Juillet</option>
                    <option value="8">Août</option>
                    <option value="9">Septembre</option>
                    <option value="10">Octobre</option>
                    <option value="11">Novembre</option>
                    <option value="12">Décembre</option>
                </select>
            </div>
        </div>
        
        <div class="chart-container" style="position: relative; height: 350px;">
            <canvas id="salesEvolutionChart"></canvas>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="d-flex align-items-center">
                    <div class="activity-dot bg-primary"></div>
                    <div class="ml-2">Menu (Repas, Petit-Déj, Dîner)</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="d-flex align-items-center">
                    <div class="activity-dot bg-success"></div>
                    <div class="ml-2">Fast Food</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="d-flex align-items-center">
                    <div class="activity-dot bg-warning"></div>
                    <div class="ml-2">Boissons</div>
                </div>
            </div>
        </div>
        
        <?php
        // Récupération des statistiques de ventes
        try {
            // Paramètres par défaut
            $current_year = date('Y');
            $current_month = date('m');
            
            // Fonction pour générer des données de test pour un jour
            function generateDailyData($day, $month, $year) {
                $timestamp = mktime(0, 0, 0, $month, $day, $year);
                $weekday = date('N', $timestamp); // 1 (lundi) à 7 (dimanche)
                
                // Facteur selon le jour de la semaine (weekend plus élevé)
                $day_factor = ($weekday >= 6) ? 1.3 : 1;
                
                // Base variable pour simuler des fluctuations réalistes
                $base_value = rand(25000, 40000) * $day_factor;
                
                return [
                    'jour' => $day,
                    'ventes_petit_dejeuner' => round($base_value * 0.15),
                    'ventes_repas' => round($base_value * 0.40),
                    'ventes_diner' => round($base_value * 0.20),
                    'ventes_fastfood' => round($base_value * 0.10),
                    'ventes_boissons' => round($base_value * 0.15),
                    'total_ventes' => round($base_value)
                ];
            }
            
            // Fonction pour générer des données de test pour une semaine
            function generateWeeklyData($year, $month) {
                $weeks = [];
                $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                
                for ($week = 1; $week <= 5; $week++) {
                    $start_day = min(($week - 1) * 7 + 1, $days_in_month);
                    $end_day = min($week * 7, $days_in_month);
                    
                    // Calculer le facteur saisonnier
                    $season_factor = 1;
                    if ($month >= 6 && $month <= 8) { // Été
                        $season_factor = 0.7;
                    } elseif ($month >= 10 && $month <= 12) { // Fin d'année
                        $season_factor = 1.2;
                    }
                    
                    $base_value = rand(150000, 200000) * $season_factor;
                    
                    $weeks[] = [
                        'semaine' => $week,
                        'ventes_petit_dejeuner' => round($base_value * 0.15),
                        'ventes_repas' => round($base_value * 0.40),
                        'ventes_diner' => round($base_value * 0.20),
                        'ventes_fastfood' => round($base_value * 0.10),
                        'ventes_boissons' => round($base_value * 0.15),
                        'total_ventes' => round($base_value)
                    ];
                }
                
                return $weeks;
            }
            
            // Fonction pour générer des données de test pour les mois
            function generateMonthlyData($year) {
                $monthly_data = [];
                
                for ($i = 1; $i <= 12; $i++) {
                    // Facteur saisonnier
                    $season_factor = 1;
                    if ($i >= 6 && $i <= 8) { // Été
                        $season_factor = 0.7;
                    } elseif ($i >= 10 && $i <= 12) { // Fin d'année
                        $season_factor = 1.2;
                    }
                    
                    $base_value = rand(800000, 1200000) * $season_factor;
                    
                    $monthly_data[] = [
                        'mois' => $i,
                        'ventes_petit_dejeuner' => round($base_value * 0.15),
                        'ventes_repas' => round($base_value * 0.40),
                        'ventes_diner' => round($base_value * 0.20),
                        'ventes_fastfood' => round($base_value * 0.10),
                        'ventes_boissons' => round($base_value * 0.15),
                        'total_ventes' => round($base_value)
                    ];
                }
                
                return $monthly_data;
            }
            
            // Fonction pour générer des données de test pour plusieurs années
            function generateYearlyData() {
                $yearly_data = [];
                $years = [2021, 2022, 2023, 2024, 2025];
                
                $base_value = 10000000; // 10M FCFA pour 2021
                $growth_rate = 0.15; // 15% de croissance annuelle
                
                foreach ($years as $index => $year) {
                    // Appliquer le taux de croissance
                    $year_value = $base_value * pow(1 + $growth_rate, $index);
                    
                    $yearly_data[] = [
                        'annee' => $year,
                        'ventes_petit_dejeuner' => round($year_value * 0.15),
                        'ventes_repas' => round($year_value * 0.40),
                        'ventes_diner' => round($year_value * 0.20),
                        'ventes_fastfood' => round($year_value * 0.10),
                        'ventes_boissons' => round($year_value * 0.15),
                        'total_ventes' => round($year_value)
                    ];
                }
                
                return $yearly_data;
            }
            
            // Générer les données (dans un environnement réel, vous récupéreriez ces données de la base de données)
            $daily_sales = [];
            $weekly_sales = [];
            $monthly_sales = [];
            $yearly_sales = [];
            
            // Générer les données quotidiennes pour le mois en cours
            $days_in_month = cal_days_in_month(CAL_GREGORIAN, $current_month, $current_year);
            for ($day = 1; $day <= $days_in_month; $day++) {
                $daily_sales[] = generateDailyData($day, $current_month, $current_year);
            }
            
            // Générer les données hebdomadaires pour le mois en cours
            $weekly_sales = generateWeeklyData($current_year, $current_month);
            
            // Générer les données mensuelles pour l'année en cours
            $monthly_sales = generateMonthlyData($current_year);
            
            // Générer les données annuelles
            $yearly_sales = generateYearlyData();
            
        } catch(PDOException $e) {
            $error_message = "Erreur de base de données: " . $e->getMessage();
            $daily_sales = [];
            $weekly_sales = [];
            $monthly_sales = [];
            $yearly_sales = [];
        }
        ?>
        
        <script>
            // Noms des mois en français
            var moisNoms = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
            
            // Données pour les différentes vues
            var dailySalesData = <?php echo json_encode($daily_sales); ?>;
            var weeklySalesData = <?php echo json_encode($weekly_sales); ?>;
            var monthlySalesData = <?php echo json_encode($monthly_sales); ?>;
            var yearlySalesData = <?php echo json_encode($yearly_sales); ?>;
            
            // Objet pour stocker le graphique
            var salesChart;
            
            // Fonction pour formater les montants
            function formatMontant(montant) {
                return montant.toLocaleString() + ' FCFA';
            }
            
            // Fonction pour créer ou mettre à jour le graphique
            function updateChart(labels, datasets, type = 'line') {
                // Détruire le graphique existant s'il existe
                if (salesChart) {
                    salesChart.destroy();
                }
                
                // Créer le nouveau graphique
                var ctx = document.getElementById('salesEvolutionChart').getContext('2d');
                salesChart = new Chart(ctx, {
                    type: type,
                    data: {
                        labels: labels,
                        datasets: datasets
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return value.toLocaleString() + ' FCFA';
                                    }
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    boxWidth: 12
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        var label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        label += parseInt(context.raw).toLocaleString() + ' FCFA';
                                        return label;
                                    }
                                }
                            }
                        },
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        }
                    }
                });
            }
            
            // Fonction pour charger les données quotidiennes
            function loadDailyData(month, year) {
                // Dans un environnement réel, vous feriez une requête AJAX ici
                
                // Préparation des données
                var labels = dailySalesData.map(function(item) {
                    return item.jour + ' ' + moisNoms[month - 1];
                });
                
                var datasets = [
                    {
                        label: 'Total des ventes',
                        data: dailySalesData.map(function(item) { return item.total_ventes; }),
                        backgroundColor: 'rgba(54, 162, 235, 0.1)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        order: 0
                    },
                    {
                        label: 'Petit-déjeuner',
                        data: dailySalesData.map(function(item) { return item.ventes_petit_dejeuner; }),
                        backgroundColor: 'rgba(255, 206, 86, 0.6)',
                        borderColor: 'rgba(255, 206, 86, 1)',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.4,
                        pointRadius: 3,
                        hidden: true,
                        order: 2
                    },
                    {
                        label: 'Repas',
                        data: dailySalesData.map(function(item) { return item.ventes_repas; }),
                        backgroundColor: 'rgba(75, 192, 192, 0.6)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.4,
                        pointRadius: 3,
                        hidden: true,
                        order: 3
                    },
                    {
                        label: 'Dîner',
                        data: dailySalesData.map(function(item) { return item.ventes_diner; }),
                        backgroundColor: 'rgba(153, 102, 255, 0.6)',
                        borderColor: 'rgba(153, 102, 255, 1)',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.4,
                        pointRadius: 3,
                        hidden: true,
                        order: 4
                    },
                    {
                        label: 'Fast Food',
                        data: dailySalesData.map(function(item) { return item.ventes_fastfood; }),
                        backgroundColor: 'rgba(40, 167, 69, 0.6)',
                        borderColor: 'rgba(40, 167, 69, 1)',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.4,
                        pointRadius: 3,
                        order: 1
                    },
                    {
                        label: 'Boissons',
                        data: dailySalesData.map(function(item) { return item.ventes_boissons; }),
                        backgroundColor: 'rgba(255, 159, 64, 0.6)',
                        borderColor: 'rgba(255, 159, 64, 1)',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.4,
                        pointRadius: 3,
                        order: 5
                    }
                ];
                
                updateChart(labels, datasets);
            }
            
            // Fonction pour charger les données hebdomadaires
            function loadWeeklyData(month, year) {
                // Préparation des données
                var labels = weeklySalesData.map(function(item) {
                    return 'Semaine ' + item.semaine + ' - ' + moisNoms[month - 1];
                });
                
                var datasets = [
                    {
                        label: 'Total des ventes',
                        data: weeklySalesData.map(function(item) { return item.total_ventes; }),
                        backgroundColor: 'rgba(54, 162, 235, 0.1)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        order: 0
                    },
                    {
                        label: 'Petit-déjeuner',
                        data: weeklySalesData.map(function(item) { return item.ventes_petit_dejeuner; }),
                        backgroundColor: 'rgba(255, 206, 86, 0.6)',
                        borderColor: 'rgba(255, 206, 86, 1)',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.4,
                        pointRadius: 3,
                        hidden: true,
                        order: 2
                    },
                    {
                        label: 'Repas',
                        data: weeklySalesData.map(function(item) { return item.ventes_repas; }),
                        backgroundColor: 'rgba(75, 192, 192, 0.6)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.4,
                        pointRadius: 3,
                        hidden: true,
                        order: 3
                    },
                    {
                        label: 'Dîner',
                        data: weeklySalesData.map(function(item) { return item.ventes_diner; }),
                        backgroundColor: 'rgba(153, 102, 255, 0.6)',
                        borderColor: 'rgba(153, 102, 255, 1)',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.4,
                        pointRadius: 3,
                        hidden: true,
                        order: 4
                    },
                    {
                        label: 'Fast Food',
                        data: weeklySalesData.map(function(item) { return item.ventes_fastfood; }),
                        backgroundColor: 'rgba(40, 167, 69, 0.6)',
                        borderColor: 'rgba(40, 167, 69, 1)',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.4,
                        pointRadius: 3,
                        order: 1
                    },
                    {
                        label: 'Boissons',
                        data: weeklySalesData.map(function(item) { return item.ventes_boissons; }),
                        backgroundColor: 'rgba(255, 159, 64, 0.6)',
                        borderColor: 'rgba(255, 159, 64, 1)',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.4,
                        pointRadius: 3,
                        order: 5
                    }
                ];
                
                updateChart(labels, datasets);
            }
            
            // Fonction pour charger les données mensuelles
            function loadMonthlyData(year) {
                // Préparation des données
                var labels = monthlySalesData.map(function(item) {
                    return moisNoms[item.mois - 1];
                });
                
                var datasets = [
                    {
                        label: 'Total des ventes',
                        data: monthlySalesData.map(function(item) { return item.total_ventes; }),
                        backgroundColor: 'rgba(54, 162, 235, 0.1)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        order: 0
                    },
                    {
                        label: 'Petit-déjeuner',
                        data: monthlySalesData.map(function(item) { return item.ventes_petit_dejeuner; }),
                        backgroundColor: 'rgba(255, 206, 86, 0.6)',
                        borderColor: 'rgba(255, 206, 86, 1)',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.4,
                        pointRadius: 3,
                        hidden: true,
                        order: 2
                    },
                    {
                        label: 'Repas',
                        data: monthlySalesData.map(function(item) { return item.ventes_repas; }),
                        backgroundColor: 'rgba(75, 192, 192, 0.6)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.4,
                        pointRadius: 3,
                        hidden: true,
                        order: 3
                    },
                    {
                        label: 'Dîner',
                        data: monthlySalesData.map(function(item) { return item.ventes_diner; }),
                        backgroundColor: 'rgba(153, 102, 255, 0.6)',
                        borderColor: 'rgba(153, 102, 255, 1)',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.4,
                        pointRadius: 3,
                        hidden: true,
                        order: 4
                    },
                    {
                        label: 'Fast Food',
                        data: monthlySalesData.map(function(item) { return item.ventes_fastfood; }),
                        backgroundColor: 'rgba(40, 167, 69, 0.6)',
                        borderColor: 'rgba(40, 167, 69, 1)',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.4,
                        pointRadius: 3,
                        order: 1
                    },
                    {
                        label: 'Boissons',
                        data: monthlySalesData.map(function(item) { return item.ventes_boissons; }),
                        backgroundColor: 'rgba(255, 159, 64, 0.6)',
                        borderColor: 'rgba(255, 159, 64, 1)',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.4,
                        pointRadius: 3,
                        order: 5
                    }
                ];
                
                updateChart(labels, datasets);
            }
            
            // Fonction pour charger les données annuelles
            function loadYearlyData() {
                var labels = yearlySalesData.map(function(item) {
                    return item.annee;
                });
                
                var datasets = [
                    {
                        label: 'Total des ventes',
                        data: yearlySalesData.map(function(item) { return item.total_ventes; }),
                        backgroundColor: 'rgba(54, 162, 235, 0.1)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        order: 0
                    },
                    {
                        label: 'Petit-déjeuner',
                        data: yearlySalesData.map(function(item) { return item.ventes_petit_dejeuner; }),
                        backgroundColor: 'rgba(255, 206, 86, 0.6)',
                        borderColor: 'rgba(255, 206, 86, 1)',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.4,
                        pointRadius: 3,
                        hidden: true,
                        order: 2
                    },
                    {
                        label: 'Repas',
                        data: yearlySalesData.map(function(item) { return item.ventes_repas; }),
                        backgroundColor: 'rgba(75, 192, 192, 0.6)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.4,
                        pointRadius: 3,
                        hidden: true,
                        order: 3
                    },
                    {
                        label: 'Dîner',
                        data: yearlySalesData.map(function(item) { return item.ventes_diner; }),
                        backgroundColor: 'rgba(153, 102, 255, 0.6)',
                        borderColor: 'rgba(153, 102, 255, 1)',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.4,
                        pointRadius: 3,
                        hidden: true,
                        order: 4
                    },
                    {
                        label: 'Fast Food',
                        data: yearlySalesData.map(function(item) { return item.ventes_fastfood; }),
                        backgroundColor: 'rgba(40, 167, 69, 0.6)',
                        borderColor: 'rgba(40, 167, 69, 1)',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.4,
                        pointRadius: 3,
                        order: 1
                    },
                    {
                        label: 'Boissons',
                        data: yearlySalesData.map(function(item) { return item.ventes_boissons; }),
                        backgroundColor: 'rgba(255, 159, 64, 0.6)',
                        borderColor: 'rgba(255, 159, 64, 1)',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.4,
                        pointRadius: 3,
                        order: 5
                    }
                ];
                
                updateChart(labels, datasets);
            }
            
            // Initialisation du graphique lors du chargement de la page
            document.addEventListener('DOMContentLoaded', function() {
                // Charger les données mensuelles par défaut
                loadMonthlyData(<?php echo $current_year; ?>);
                
                // Gestionnaire pour le changement de type de vue
                $('input[name="chartViewType"]').change(function() {
                    var viewType = $(this).val();
                    var selectedYear = $('#salesChartYear').val();
                    var selectedMonth = $('#selectedMonth').val();
                    
                    // Montrer ou cacher le sélecteur de mois
                    if (viewType === 'daily' || viewType === 'weekly') {
                        $('#monthSelector').show();
                    } else {
                        $('#monthSelector').hide();
                    }
                    
                    // Charger les données appropriées
                    if (viewType === 'daily') {
                        loadDailyData(selectedMonth, selectedYear);
                    } else if (viewType === 'weekly') {
                        loadWeeklyData(selectedMonth, selectedYear);
                    } else if (viewType === 'monthly') {
                        loadMonthlyData(selectedYear);
                    } else if (viewType === 'yearly') {
                        loadYearlyData();
                    }
                });
                
                // Gestionnaire pour le changement d'année
                $('#salesChartYear').change(function() {
                    var selectedYear = $(this).val();
                    var viewType = $('input[name="chartViewType"]:checked').val();
                    var selectedMonth = $('#selectedMonth').val();
                    
                    // Recharger les données pour l'année sélectionnée
                    if (viewType === 'daily') {
                        loadDailyData(selectedMonth, selectedYear);
                    } else if (viewType === 'weekly') {
                        loadWeeklyData(selectedMonth, selectedYear);
                    } else if (viewType === 'monthly') {
                        loadMonthlyData(selectedYear);
                    } else if (viewType === 'yearly') {
                        loadYearlyData();
                    }
                });
                
                // Gestionnaire pour le changement de mois
               // Gestionnaire pour le changement de mois
			   $('#selectedMonth').change(function() {
                    var selectedMonth = $(this).val();
                    var selectedYear = $('#salesChartYear').val();
                    var viewType = $('input[name="chartViewType"]:checked').val();
                    
                    // Recharger les données pour le mois sélectionné
                    if (viewType === 'daily') {
                        loadDailyData(selectedMonth, selectedYear);
                    } else if (viewType === 'weekly') {
                        loadWeeklyData(selectedMonth, selectedYear);
                    }
                });
                
                // Initialiser les contrôles avec les valeurs actuelles
                $('#salesChartYear').val(<?php echo $current_year; ?>);
                $('#selectedMonth').val(<?php echo $current_month; ?>);
                
                // Gérer l'affichage du sélecteur de mois en fonction du type de vue initial
                var initialViewType = $('input[name="chartViewType"]:checked').val();
                if (initialViewType === 'daily' || initialViewType === 'weekly') {
                    $('#monthSelector').show();
                } else {
                    $('#monthSelector').hide();
                }
            });
        </script>
    </div>
</div>
                    
                    
                </div>
                
                <!-- Graphique -->
                <div class="col-xl-4 mb-30">
                    <div class="card-box height-100-p pd-20">
                        <h4 class="h4 text-blue mb-20">Répartition des Utilisateurs</h4>
                        <div class="chart-container" style="position: relative; height: 300px;">
                            <canvas id="userTypeChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Utilisateurs récents et actions rapides -->
            <div class="row">
                <!-- Derniers utilisateurs inscrits -->
                <div class="col-xl-6 mb-30">
                    <div class="card-box height-100-p pd-20">
                        <div class="d-flex justify-content-between mb-3">
                            <h4 class="h4 text-blue">Derniers Utilisateurs Inscrits</h4>
                            <a href="all_user.php" class="btn btn-sm btn-primary">Voir tous</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th scope="col">Utilisateur</th>
                                        <th scope="col">Type</th>
                                        <th scope="col">Date d'inscription</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($recent_users as $user): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php 
                                                    $colors = ['primary', 'success', 'warning', 'danger', 'info', 'secondary'];
                                                    $color = $colors[array_rand($colors)];
                                                ?>
                                                <div class="user-avatar bg-<?php echo $color; ?> mr-2">
                                                    <?php echo strtoupper(substr($user['prenom'], 0, 1) . substr($user['nom'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0"><?php echo $user['nom'] . ' ' . $user['prenom']; ?></h6>
                                                    <small class="text-muted"><?php echo $user['email']; ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php 
                                                switch($user['type_utilisateur']) {
                                                    case 'admin': $badge = 'badge-danger'; break;
                                                    case 'etudiant': $badge = 'badge-primary'; break;
                                                    case 'personnel': $badge = 'badge-warning'; break;
                                                    case 'professeur': $badge = 'badge-info'; break;
                                                    case 'employe': $badge = 'badge-success'; break;
                                                    default: $badge = 'badge-secondary';
                                                }
                                            ?>
                                            <span class="badge <?php echo $badge; ?>"><?php echo ucfirst($user['type_utilisateur']); ?></span>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></small>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Raccourcis d'actions -->
                <div class="col-xl-6 mb-30">
                    <div class="card-box height-100-p pd-20">
                        <h4 class="h4 text-blue mb-20">Actions Rapides</h4>
                        <div class="row">
                            <div class="col-sm-6 mb-3">
                                <a href="new_user.php" class="action-btn btn btn-block btn-outline-primary btn-lg position-relative p-4 text-left">
                                    <i class="icon-copy dw dw-add-user1 font-24 mb-2"></i>
                                    <h5 class="font-16 mb-0">Ajouter un utilisateur</h5>
                                    <small>Créer un nouveau compte</small>
                                </a>
                            </div>
                            <div class="col-sm-6 mb-3">
                                <a href="new_menu.php" class="action-btn btn btn-block btn-outline-success btn-lg position-relative p-4 text-left">
                                    <i class="icon-copy dw dw-add font-24 mb-2"></i>
                                    <h5 class="font-16 mb-0">Ajouter un plat</h5>
                                    <small>Au menu du jour</small>
                                </a>
                            </div>
                            <div class="col-sm-6 mb-3">
                                <a href="orders.php" class="action-btn btn btn-block btn-outline-warning btn-lg position-relative p-4 text-left">
                                    <i class="icon-copy dw dw-shopping-cart1 font-24 mb-2"></i>
                                    <h5 class="font-16 mb-0">Commandes</h5>
                                    <small>Gérer les commandes</small>
                                </a>
                            </div>
                            <div class="col-sm-6 mb-3">
                                <a href="rapports.php" class="action-btn btn btn-block btn-outline-info btn-lg position-relative p-4 text-left">
                                    <i class="icon-copy dw dw-analytics-21 font-24 mb-2"></i>
                                    <h5 class="font-16 mb-0">Rapports</h5>
                                    <small>Analyses et statistiques</small>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="footer-wrap pd-20 mb-20 card-box">
                <div class="d-flex justify-content-between align-items-center">
                    <span>&copy; <?php echo date('Y'); ?> Restaurant ESCOA - Tous droits réservés</span>
                    <div>
                        <span class="mr-2">Version 1.0</span>
                        <a href="#" class="text-primary">Aide</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JS -->
    <script src="vendors/scripts/core.js"></script>
    <script src="vendors/scripts/script.min.js"></script>
    <script src="vendors/scripts/process.js"></script>
    <script src="vendors/scripts/layout-settings.js"></script>
    
    <script>
        $(document).ready(function() {
            // Configuration du graphique de répartition des utilisateurs
            var ctx = document.getElementById('userTypeChart').getContext('2d');
            var userTypeChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Étudiants', 'Personnel & Professeurs', 'Employés', 'Autres'],
                    datasets: [{
                        data: [
                            <?php echo $stats_users['etudiants']; ?>, 
                            <?php echo $stats_users['personnel']; ?>, 
                            <?php echo $stats_users['employes']; ?>, 
                            <?php echo $stats_users['total'] - $stats_users['etudiants'] - $stats_users['personnel'] - $stats_users['employes']; ?>
                        ],
                        backgroundColor: [
                            '#2b5797', '#00a300', '#ff0097', '#9f00a7'
                        ],
                        borderColor: [
                            '#ffffff', '#ffffff', '#ffffff', '#ffffff'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            fontColor: '#333'
                        }
                    },
                    tooltips: {
                        callbacks: {
                            label: function(tooltipItem, data) {
                                var dataset = data.datasets[tooltipItem.datasetIndex];
                                var total = dataset.data.reduce(function(previousValue, currentValue) {
                                    return previousValue + currentValue;
                                });
                                var currentValue = dataset.data[tooltipItem.index];
                                var percentage = Math.floor(((currentValue/total) * 100)+0.5);
                                return data.labels[tooltipItem.index] + ': ' + currentValue + ' (' + percentage + '%)';
                            }
                        }
                    },
                    cutoutPercentage: 70,
                    animation: {
                        animateScale: true,
                        animateRotate: true
                    }
                }
            });
            
            // Animation des cartes au survol
            $('.card-box').hover(
                function() {
                    $(this).addClass('shadow');
                },
                function() {
                    $(this).removeClass('shadow');
                }
            );
        });
    </script>
</body>
</html>