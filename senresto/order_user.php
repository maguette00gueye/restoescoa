<?php
// Inclure les fichiers nécessaires
require_once "config/database.php";

// Vérifier l'authentification
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Récupérer l'ID de l'utilisateur connecté
$user_id = $_SESSION['user_id'];

// Construire la requête pour récupérer uniquement les commandes de cet utilisateur
try {
    // Requête pour récupérer les commandes de l'utilisateur
    $query = "SELECT o.*, u.nom, u.prenom, u.email, u.telephone 
              FROM orders o
              LEFT JOIN utilisateurs u ON o.user_id = u.id
              WHERE o.user_id = :user_id
              ORDER BY o.created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculer quelques statistiques
    $total_orders = count($orders);
    $total_spent = 0;
    $favorite_items = [];
    
    foreach ($orders as $order) {
        $total_spent += $order['total_amount'];
        
        // Récupérer les articles de chaque commande pour déterminer les favoris
        $items_query = "SELECT oi.*, 
                    CASE 
                      WHEN mi.id_menu IS NOT NULL THEN mi.name
                      WHEN ff.id_ff IS NOT NULL THEN ff.name
                      WHEN ab.id_ab IS NOT NULL THEN ab.name
                      ELSE 'Produit inconnu'
                    END AS item_name
            FROM order_items oi
            LEFT JOIN menu_items mi ON oi.item_id = mi.id_menu
            LEFT JOIN ff_items ff ON oi.item_id = ff.id_ff
            LEFT JOIN ab_items ab ON oi.item_id = ab.id_ab
            WHERE oi.order_id = :order_id";
          
        $items_stmt = $conn->prepare($items_query);
        $items_stmt->bindParam(':order_id', $order['id']);
        $items_stmt->execute();
        $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($items as $item) {
            if (!isset($favorite_items[$item['item_name']])) {
                $favorite_items[$item['item_name']] = 0;
            }
            $favorite_items[$item['item_name']] += $item['quantity'];
        }
    }
    
    // Trier les articles favoris par quantité commandée
    arsort($favorite_items);
    
} catch (PDOException $e) {
    $error_message = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Historique de vos commandes - SenResto_Escoa</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary-color: #e76f51;
      --secondary-color: #2a9d8f;
      --accent-color: #f4a261;
      --text-color: #264653;
      --light-bg: #f8f9fa;
      --dark-bg: #264653;
      --danger-color: #e63946;
      --warning-color: #ffb703;
      --success-color: #2a9d8f;
      --info-color: #219ebc;
    }
    
    body {
      font-family: 'Poppins', sans-serif;
      background-image: url('https://images.unsplash.com/photo-1555396273-367ea4eb4db5?ixlib=rb-1.2.1&auto=format&fit=crop&w=2100&q=80');
      background-size: cover;
      background-attachment: fixed;
      background-position: center;
      color: var(--text-color);
      position: relative;
    }
    
    body::before {
      content: '';
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(255, 255, 255, 0.9);
      z-index: -1;
    }
    
    .navbar {
      background-color: var(--dark-bg);
      padding: 15px 0;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    
    .navbar-brand {
      font-family: 'Playfair Display', serif;
      font-size: 28px;
      color: white;
      font-weight: 700;
    }
    
    .navbar-brand span {
      color: var(--primary-color);
    }
    
    .nav-link {
      color: white;
      font-weight: 500;
      margin: 0 10px;
      transition: all 0.3s;
    }
    
    .nav-link:hover {
      color: var(--accent-color);
    }
    
    .container {
      max-width: 1200px;
      margin-top: 30px;
      padding-bottom: 50px;
    }
    
    .welcome-header {
      text-align: center;
      margin-bottom: 40px;
      padding: 30px;
      background-color: rgba(255, 255, 255, 0.9);
      border-radius: 15px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
    }
    
    .welcome-header h2 {
      font-family: 'Playfair Display', serif;
      font-size: 36px;
      font-weight: 700;
      color: var(--text-color);
      margin-bottom: 10px;
    }
    
    .welcome-header p {
      font-size: 18px;
      color: var(--text-color);
      margin-bottom: 20px;
    }
    
    .user-badge {
      display: inline-block;
      background-color: var(--primary-color);
      color: white;
      padding: 5px 15px;
      border-radius: 50px;
      font-weight: 600;
      margin-bottom: 20px;
    }
    
    .stats-section {
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      gap: 20px;
      margin-bottom: 30px;
    }
    
    .stat-card {
      flex: 1;
      min-width: 200px;
      background-color: white;
      border-radius: 15px;
      padding: 20px;
      text-align: center;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
      transition: all 0.3s;
    }
    
    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }
    
    .stat-icon {
      font-size: 2rem;
      color: var(--primary-color);
      margin-bottom: 15px;
    }
    
    .stat-value {
      font-size: 1.8rem;
      font-weight: 700;
      color: var(--text-color);
      margin-bottom: 5px;
    }
    
    .stat-label {
      font-size: 1rem;
      color: var(--text-color);
      opacity: 0.8;
    }
    
    .filter-section {
      background-color: white;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
      margin-bottom: 30px;
    }
    
    .filter-title {
      font-family: 'Playfair Display', serif;
      font-size: 24px;
      font-weight: 600;
      color: var(--secondary-color);
      margin-bottom: 20px;
    }
    
    .btn-filter {
      margin-right: 10px;
      margin-bottom: 10px;
      padding: 8px 16px;
      border-radius: 8px;
      font-weight: 500;
      transition: all 0.3s;
    }
    
    .btn-filter:hover {
      transform: translateY(-3px);
    }
    
    .orders-section {
      background-color: rgba(255, 255, 255, 0.9);
      padding: 30px;
      border-radius: 15px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
      margin-bottom: 50px;
    }
    
    .section-title {
      font-family: 'Playfair Display', serif;
      font-size: 28px;
      font-weight: 600;
      color: var(--secondary-color);
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 3px solid var(--accent-color);
      display: inline-block;
    }
    
    .order-card {
      background-color: white;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
      margin-bottom: 20px;
      overflow: hidden;
      transition: all 0.3s;
    }
    
    .order-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }
    
    .order-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 15px 20px;
      background-color: var(--light-bg);
      border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .order-id {
      font-weight: 600;
      font-size: 1.1rem;
      color: var(--text-color);
    }
    
    .order-date {
      font-size: 0.9rem;
      color: var(--text-color);
      opacity: 0.8;
    }
    
    .order-body {
      padding: 20px;
    }
    
    .order-info {
      display: flex;
      flex-wrap: wrap;
      margin-bottom: 20px;
    }
    
    .order-info-item {
      flex: 1;
      min-width: 150px;
      margin-bottom: 15px;
    }
    
    .order-info-label {
      font-size: 0.9rem;
      font-weight: 600;
      color: var(--text-color);
      opacity: 0.8;
      margin-bottom: 5px;
    }
    
    .order-info-value {
      font-size: 1rem;
      color: var(--text-color);
    }
    
    .order-items {
      margin-top: 20px;
    }
    
    .order-footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 15px 20px;
      background-color: var(--light-bg);
      border-top: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .order-total {
      font-weight: 700;
      font-size: 1.2rem;
      color: var(--text-color);
    }
    
    .btn-details {
      background-color: var(--secondary-color);
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 8px;
      font-weight: 500;
      transition: all 0.3s;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .btn-details:hover {
      background-color: var(--text-color);
      transform: translateY(-3px);
    }
    
    .badge {
      padding: 8px 15px;
      border-radius: 50px;
      font-weight: 600;
      font-size: 12px;
    }
    
    .badge-pending {
      background-color: var(--warning-color);
      color: var(--text-color);
    }
    
    .badge-preparing {
      background-color: var(--info-color);
      color: white;
    }
    
    .badge-delivered {
      background-color: var(--success-color);
      color: white;
    }
    
    .badge-canceled {
      background-color: var(--danger-color);
      color: white;
    }
    
    .modal-content {
      border-radius: 15px;
      border: none;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }
    
    .modal-header {
      background-color: var(--secondary-color);
      color: white;
      border-top-left-radius: 15px;
      border-top-right-radius: 15px;
      padding: 20px;
    }
    
    .modal-title {
      font-family: 'Playfair Display', serif;
      font-weight: 600;
    }
    
    .modal-body {
      padding: 25px;
    }
    
    .modal-footer {
      padding: 20px;
      border-top: none;
    }
    
    .favorites-section {
      background-color: rgba(255, 255, 255, 0.9);
      padding: 30px;
      border-radius: 15px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
      margin-bottom: 50px;
    }
    
    
      .favorites-list {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
      }
      
      .favorite-item {
        background-color: white;
        padding: 15px;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        display: flex;
        align-items: center;
        gap: 10px;
        flex: 1;
        min-width: 250px;
        transition: all 0.3s;
      }
      
      .favorite-item:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
      }
      
      .favorite-icon {
        font-size: 1.5rem;
        color: var(--accent-color);
      }
      
      .favorite-details {
        flex-grow: 1;
      }
      
      .favorite-name {
        font-weight: 600;
        margin-bottom: 5px;
      }
      
      .favorite-quantity {
        font-size: 0.9rem;
        color: var(--text-color);
        opacity: 0.8;
      }
      
      .pagination-container {
        display: flex;
        justify-content: center;
        margin-top: 30px;
      }
      
      .pagination .page-item .page-link {
        color: var(--secondary-color);
        border-color: var(--light-bg);
        margin: 0 5px;
        border-radius: 8px;
        transition: all 0.3s;
      }
      
      .pagination .page-item.active .page-link {
        background-color: var(--secondary-color);
        border-color: var(--secondary-color);
      }
      
      .pagination .page-item .page-link:hover {
        background-color: var(--light-bg);
        transform: translateY(-2px);
      }
      
      .responsive-table {
        overflow-x: auto;
      }
      
      .table {
        min-width: 700px;
      }
      
      .table th {
        font-weight: 600;
        color: var(--secondary-color);
        border-bottom-width: 2px;
      }
      
      .table td, .table th {
        padding: 12px 15px;
        vertical-align: middle;
      }
      
      footer {
        background-color: var(--dark-bg);
        color: white;
        padding: 30px 0;
        text-align: center;
        position: relative;
        margin-top: 50px;
      }
      
      .footer-content {
        max-width: 800px;
        margin: 0 auto;
      }
      
      .footer-logo {
        font-family: 'Playfair Display', serif;
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 20px;
      }
      
      .footer-logo span {
        color: var(--primary-color);
      }
      
      .footer-links {
        display: flex;
        justify-content: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
      }
      
      .footer-link {
        color: white;
        margin: 0 15px;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s;
      }
      
      .footer-link:hover {
        color: var(--accent-color);
      }
      
      .social-links {
        display: flex;
        justify-content: center;
        margin-bottom: 20px;
      }
      
      .social-link {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: rgba(255, 255, 255, 0.1);
        margin: 0 10px;
        transition: all 0.3s;
        color: white;
        text-decoration: none;
      }
      
      .social-link:hover {
        background-color: var(--primary-color);
        transform: translateY(-3px);
      }
      
      .copyright {
        font-size: 14px;
        opacity: 0.8;
        margin-top: 20px;
      }
      
      @media (max-width: 992px) {
        .stat-card {
          min-width: 150px;
        }
      }
      
      @media (max-width: 768px) {
        .welcome-header h2 {
          font-size: 28px;
        }
        
        .welcome-header p {
          font-size: 16px;
        }
        
        .stat-card {
          min-width: 100%;
          margin-bottom: 15px;
        }
        
        .section-title {
          font-size: 24px;
        }
        
        .order-info-item {
          min-width: 100%;
        }
      }
      
      @media (max-width: 576px) {
        .welcome-header {
          padding: 20px;
        }
        
        .orders-section, .favorites-section {
          padding: 20px;
        }
        
        .order-header {
          flex-direction: column;
          align-items: flex-start;
        }
        
        .order-date {
          margin-top: 5px;
        }
        
        .order-footer {
          flex-direction: column;
          gap: 10px;
        }
        
        .btn-details {
          width: 100%;
          justify-content: center;
        }
      }
      </style>
      </head>
      <body>
        <!-- Navigation -->
        <nav class="navbar navbar-expand-lg">
          <div class="container">
            <a class="navbar-brand" href="index.php">Sen<span>Resto</span>_Escoa</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
              <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
              <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                  <a class="nav-link" href="homepage.php">Accueil</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" href="all_menu_user.php">Menu</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link active" href="profile.php">Mon Compte</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" href="panier.php">
                    <i class="fas fa-shopping-cart"></i> Panier
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                  </a>
                </li>
              </ul>
            </div>
          </div>
        </nav>
      
        <!-- Main Content -->
        <div class="container">
          <!-- Welcome Section -->
          <div class="welcome-header">
            <div class="user-badge">
              <i class="fas fa-user-circle"></i> Mon compte
            </div>
            <h2>Historique de mes commandes</h2>
            <p>Retrouvez toutes vos commandes passées et leurs détails sur cette page.</p>
          </div>
          
          <!-- Stats Section -->
          <div class="stats-section">
            <div class="stat-card">
              <div class="stat-icon">
                <i class="fas fa-shopping-bag"></i>
              </div>
              <div class="stat-value"> <?php $total_orders ?></div>
              <div class="stat-label">Commandes totales</div>
            </div>
            
            <div class="stat-card">
              <div class="stat-icon">
                <i class="fas fa-wallet"></i>
              </div>
              <div class="stat-value"><?php number_format($total_spent, 0, ',', ' ') ?> FCFA</div>
              <div class="stat-label">Dépenses totales</div>
            </div>
            
            <div class="stat-card">
              <div class="stat-icon">
                <i class="fas fa-utensils"></i>
              </div>
              <div class="stat-value">
                    <?php 
                    if (!empty($favorite_items)) {
                        $keys = array_keys($favorite_items);
                        echo htmlspecialchars($keys[0]);
                    } else {
                        echo 'Aucun';
                    }
                    ?>
                </div>
               
              <div class="stat-label">Plat favori</div>
            </div>
          </div>
          
          <!-- Filter Section -->
          <div class="filter-section">
            <h3 class="filter-title">Filtrer mes commandes</h3>
            <div class="filter-buttons">
              <button class="btn btn-filter" data-filter="all">Toutes</button>
              <button class="btn btn-filter" data-filter="pending">En attente</button>
              <button class="btn btn-filter" data-filter="preparing">En préparation</button>
              <button class="btn btn-filter" data-filter="delivered">Livrées</button>
              <button class="btn btn-filter" data-filter="canceled">Annulées</button>
            </div>
          </div>
          
          <!-- Orders Section -->
          <div class="orders-section">
            <h3 class="section-title">Mes commandes</h3>
            
            <?php if (empty($orders)): ?>
              <div class="alert alert-info" role="alert">
                <i class="fas fa-info-circle me-2"></i> Vous n'avez pas encore passé de commande.
              </div>
            <?php else: ?>
              <?php foreach ($orders as $order): ?>
                <?php
                  // Récupérer les articles de cette commande
                  $items_query = "SELECT oi.*, 
                            CASE 
                              WHEN mi.id_menu IS NOT NULL THEN mi.name
                              WHEN ff.id_ff IS NOT NULL THEN ff.name
                              WHEN ab.id_ab IS NOT NULL THEN ab.name
                              ELSE 'Produit inconnu'
                            END AS item_name,
                            CASE 
                              WHEN mi.id_menu IS NOT NULL THEN mi.price
                              WHEN ff.id_ff IS NOT NULL THEN ff.price
                              WHEN ab.id_ab IS NOT NULL THEN ab.price
                              ELSE 0
                            END AS item_price
                    FROM order_items oi
                    LEFT JOIN menu_items mi ON oi.item_id = mi.id_menu
                    LEFT JOIN ff_items ff ON oi.item_id = ff.id_ff
                    LEFT JOIN ab_items ab ON oi.item_id = ab.id_ab
                    WHERE oi.order_id = :order_id";
                  
                  $items_stmt = $conn->prepare($items_query);
                  $items_stmt->bindParam(':order_id', $order['id']);
                  $items_stmt->execute();
                  $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
                  
                  // Déterminer la classe du badge en fonction du statut
                  $statut_class = '';
                  switch ($order['statut']) {
                    case 'pending':
                      $statut_class = 'badge-pending';
                      $statut_text = 'En attente';
                      break;
                    case 'preparing':
                      $statut_class = 'badge-preparing';
                      $statut_text = 'En préparation';
                      break;
                    case 'delivered':
                      $statut_class = 'badge-delivered';
                      $statut_text = 'Livrée';
                      break;
                    case 'canceled':
                      $statut_class = 'badge-canceled';
                      $statut_text = 'Annulée';
                      break;
                    default:
                      $statut_class = 'badge-pending';
                      $statut_text = 'En attente';
                  }
                ?>
                <div class="order-card" data-order-statut="<?php= $order['statut'] ?>">
                  <div class="order-header">
                    <div class="order-id">
                      Commande #<?php= $order['id'] ?>
                      <span class="badge <?php= $statut_class ?>"><?php= $statut_text ?></span>
                    </div>
                    <div class="order-date">
                      <i class="far fa-calendar-alt me-1"></i> <?php= date('d/m/Y à H:i', strtotime($order['created_at'])) ?>
                    </div>
                  </div>
                  <div class="order-body">
                    <div class="order-info">
                      <div class="order-info-item">
                        <div class="order-info-label">Adresse de livraison</div>
                        <div class="order-info-value"><?php= htmlspecialchars($order['delivery_address']) ?></div>
                      </div>
                      <div class="order-info-item">
                        <div class="order-info-label">Méthode de paiement</div>
                        <div class="order-info-value"><?php= htmlspecialchars($order['mode_paiement']) ?></div>
                      </div>
                      <div class="order-info-item">
                        <div class="order-info-label">Articles</div>
                        <div class="order-info-value"><?php= count($items) ?> article(s)</div>
                      </div>
                    </div>
                  </div>
                  <div class="order-footer">
                    <div class="order-total">
                    <p><strong>Total:</strong> <?= number_format($order['total_amount'], 0, ',', ' ') ?> FCFA</p>
                    </div>
                    <button class="btn btn-details" data-bs-toggle="modal" data-bs-target="#orderModal<?php= $order['id'] ?>">
                      <i class="fas fa-eye"></i> Voir les détails
                    </button>
                  </div>
                </div>
                
                <!-- Order Details Modal -->
                <div class="modal fade" id="orderModal<?php= $order['id'] ?>" tabindex="-1" aria-labelledby="orderModalLabel<?= $order['id'] ?>" aria-hidden="true">
                  <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="orderModalLabel<?= $order['id'] ?>">
                          Détails de la commande #<?php= $order['id'] ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        <div class="order-info mb-4">
                          <div class="row">
                            <div class="col-md-6">
                              <p><strong>Date de commande:</strong> < <?php= date('d/m/Y à H:i', strtotime($order['created_at'])) ?></p>
                              <p><strong>Statut:</strong> <span class="badge <?php= $statut_class ?>"><?= $statut_text ?></span></p>
                              <p><strong>Adresse de livraison:</strong> <?php= htmlspecialchars($order['delivery_address']) ?></p>
                            </div>
                            <div class="col-md-6">
                              <p><strong>Méthode de paiement:</strong> <?php= htmlspecialchars($order['mode_paiement']) ?></p>
                              <p><strong>Total:</strong> <?php= number_format($order['total_amount'], 0, ',', ' ') ?> FCFA</p>
                        <?php if (!empty($order['notes'])): ?>
                          <p><strong>Notes:</strong> <?php= htmlspecialchars($order['notes']) ?></p>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                  
                  <h6 class="mb-3">Articles commandés</h6>
                  <div class="responsive-table">
                    <table class="table">
                      <thead>
                        <tr>
                          <th>Article</th>
                          <th>Prix unitaire</th>
                          <th>Quantité</th>
                          <th>Sous-total</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($items as $item): ?>
                          <tr>
                            <td><?= htmlspecialchars($item['item_name']) ?></td>
                            <td><?= number_format($item['item_price'], 0, ',', ' ') ?> FCFA</td>
                            <td><?= $item['quantity'] ?></td>
                            <td><?= number_format($item['item_price'] * $item['quantity'], 0, ',', ' ') ?> FCFA</td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                      <tfoot>
                        <tr>
                          <td colspan="3" class="text-end"><strong>Sous-total</strong></td>
                          <td><?= number_format($order['subtotal'], 0, ',', ' ') ?> FCFA</td>
                        </tr>
                        <tr>
                          <td colspan="3" class="text-end"><strong>Frais de livraison</strong></td>
                          <td><?= number_format($order['delivery_fee'], 0, ',', ' ') ?> FCFA</td>
                        </tr>
                        <tr>
                          <td colspan="3" class="text-end"><strong>Total</strong></td>
                          <td><strong><?= number_format($order['total_amount'], 0, ',', ' ') ?> FCFA</strong></td>
                        </tr>
                      </tfoot>
                    </table>
                  </div>
                </div>
                <div class="modal-footer">
                  <?php if ($order['statut'] === 'pending'): ?>
                    <button type="button" class="btn btn-danger" onclick="cancelOrder(<?= $order['id'] ?>)">
                      <i class="fas fa-times-circle"></i> Annuler la commande
                    </button>
                  <?php endif; ?>
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    
    <!-- Favorites Section -->
    <div class="favorites-section">
      <h3 class="section-title">Mes plats favoris</h3>
      
      <?php if (empty($favorite_items)): ?>
        <div class="alert alert-info" role="alert">
          <i class="fas fa-info-circle me-2"></i> Vous n'avez pas encore de plats favoris.
        </div>
      <?php else: ?>
        <div class="favorites-list">
          <?php 
          $count = 0;
          foreach ($favorite_items as $item_name => $quantity): 
            if ($count >= 6) break; // Limiter à 6 favoris
          ?>
            <div class="favorite-item">
              <div class="favorite-icon">
                <i class="fas fa-utensils"></i>
              </div>
              <div class="favorite-details">
                <div class="favorite-name"><?= htmlspecialchars($item_name) ?></div>
                <div class="favorite-quantity">Commandé <?= $quantity ?> fois</div>
              </div>
            </div>
          <?php 
            $count++;
          endforeach; 
          ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Footer -->
  <footer>
    <div class="container">
      <div class="footer-content">
        <div class="footer-logo">Sen<span>Resto</span>_Escoa</div>
        <div class="footer-links">
          <a href="homepage.php" class="footer-link">Accueil</a>
          <a href="all_menu_user.php" class="footer-link">Menu</a>
          <a href="about.php" class="footer-link">À propos</a>
          <a href="contact.php" class="footer-link">Contact</a>
        </div>
        <div class="social-links">
          <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
          <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
          <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
        </div>
        <div class="copyright">
          &copy; <?= date('Y') ?> SenResto_Escoa. Tous droits réservés.
        </div>
      </div>
    </div>
  </footer>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Script pour filtrer les commandes
    document.addEventListener('DOMContentLoaded', function() {
      const filterButtons = document.querySelectorAll('.btn-filter');
      const orderCards = document.querySelectorAll('.order-card');
      
      filterButtons.forEach(button => {
        button.addEventListener('click', function() {
          const filter = this.getAttribute('data-filter');
          
          // Activer le bouton sélectionné
          filterButtons.forEach(btn => {
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-outline-primary');
          });
          this.classList.remove('btn-outline-primary');
          this.classList.add('btn-primary');
          
          // Filtrer les commandes
          orderCards.forEach(card => {
            if (filter === 'all' || card.getAttribute('data-order-statut') === filter) {
              card.style.display = 'block';
            } else {
              card.style.display = 'none';
            }
          });
        });
      });
      
      // Activer le filtre "Toutes" par défaut
      filterButtons[0].classList.add('btn-primary');
    });
    
    // Fonction pour annuler une commande
    function cancelOrder(orderId) {
      if (confirm('Êtes-vous sûr de vouloir annuler cette commande ?')) {
        // Envoyer une requête AJAX pour annuler la commande
        fetch('cancel_order.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: 'order_id=' + orderId
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            alert('Votre commande a été annulée avec succès.');
            // Recharger la page pour mettre à jour le statut
            window.location.reload();
          } else {
            alert('Une erreur est survenue lors de l\'annulation de votre commande : ' + data.message);
          }
        })
        .catch(error => {
          console.error('Erreur:', error);
          alert('Une erreur est survenue lors de la communication avec le serveur.');
        });
      }
    }
  </script>
</body>
</html>