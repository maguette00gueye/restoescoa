<?php
// Inclure les fichiers nécessaires
require_once "config/database.php";

// Vérifier l'authentification
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Récupérer l'ID de l'utilisateur connecté ou filtrer par utilisateur spécifique
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;

// Vérifier si l'utilisateur est admin pour pouvoir voir toutes les commandes
$is_admin = isset($_SESSION['role_id']) && ($_SESSION['role_id'] == 1 || $_SESSION['role_id'] == 2);

// Si l'utilisateur n'est pas admin, il ne peut voir que ses propres commandes
if (!$is_admin) {
    $user_id = $_SESSION['user_id'];
}

// Construire la requête pour récupérer les commandes
try {
    // Requête de base
    $query = "SELECT o.*, u.nom, u.prenom, u.email, u.telephone 
              FROM orders o
              LEFT JOIN utilisateurs u ON o.user_id = u.id";
    
    // Ajouter des filtres conditionnels
    $params = [];
    
    // Filtrer par utilisateur si spécifié
    if ($user_id) {
        $query .= " WHERE o.user_id = :user_id";
        $params[':user_id'] = $user_id;
    }
    
    // Filtrer par statut si spécifié
    if (isset($_GET['statut']) && !empty($_GET['statut'])) {
        $statut = $_GET['statut'];
        if (strpos($query, 'WHERE') !== false) {
            $query .= " AND o.statut = :statut";
        } else {
            $query .= " WHERE o.statut = :statut";
        }
        $params[':statut'] = $statut;
    }
    
    // Trier par date (le plus récent d'abord)
    $query .= " ORDER BY o.created_at DESC";
    
    // Préparer et exécuter la requête
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer la liste des utilisateurs pour le filtre (admin seulement)
    $users = [];
    if ($is_admin) {
        $user_stmt = $conn->query("SELECT id, nom, prenom FROM utilisateurs ORDER BY nom, prenom");
        $users = $user_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (PDOException $e) {
    $error_message = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Historique des Commandes - SenResto_Escoa</title>
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
    
    .admin-header {
      text-align: center;
      margin-bottom: 40px;
      padding: 30px;
      background-color: rgba(255, 255, 255, 0.9);
      border-radius: 15px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
    }
    
    .admin-header h2 {
      font-family: 'Playfair Display', serif;
      font-size: 36px;
      font-weight: 700;
      color: var(--text-color);
      margin-bottom: 10px;
    }
    
    .admin-header p {
      font-size: 18px;
      color: var(--text-color);
      margin-bottom: 20px;
    }
    
    .admin-badge {
      display: inline-block;
      background-color: var(--primary-color);
      color: white;
      padding: 5px 15px;
      border-radius: 50px;
      font-weight: 600;
      margin-bottom: 20px;
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
    
    .btn-filter-all {
      background-color: var(--text-color);
      color: white;
      border: none;
    }
    
    .btn-filter-pending {
      background-color: var(--warning-color);
      color: var(--text-color);
      border: none;
    }
    
    .btn-filter-preparing {
      background-color: var(--info-color);
      color: white;
      border: none;
    }
    
    .btn-filter-delivered {
      background-color: var(--success-color);
      color: white;
      border: none;
    }
    
    .btn-filter-canceled {
      background-color: var(--danger-color);
      color: white;
      border: none;
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
    
    .table {
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }
    
    .table thead {
      background-color: var(--secondary-color);
      color: white;
    }
    
    .table th {
      padding: 15px;
      font-weight: 600;
    }
    
    .table td {
      padding: 15px;
      vertical-align: middle;
    }
    
    .table-striped tbody tr:nth-of-type(odd) {
      background-color: rgba(0, 0, 0, 0.02);
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
    
    .customer-info-section h5,
    .order-info-section h5,
    .order-items-section h5 {
      font-family: 'Playfair Display', serif;
      color: var(--secondary-color);
      margin-bottom: 15px;
      font-weight: 600;
    }
    
    .customer-info-section,
    .order-info-section {
      margin-bottom: 20px;
    }
    
    .customer-info-section p,
    .order-info-section p {
      margin-bottom: 10px;
    }
    
    .modal-footer .btn {
      padding: 10px 20px;
      font-weight: 500;
      border-radius: 8px;
      transition: all 0.3s;
    }
    
    .modal-footer .btn:hover {
      transform: translateY(-3px);
    }
    
    .btn-close-modal {
      background-color: #6c757d;
      color: white;
      border: none;
    }
    
    .btn-prepare {
      background-color: var(--info-color);
      color: white;
      border: none;
    }
    
    .btn-deliver {
      background-color: var(--success-color);
      color: white;
      border: none;
    }
    
    .btn-cancel {
      background-color: var(--danger-color);
      color: white;
      border: none;
    }
    
    .alert {
      border-radius: 10px;
      padding: 20px;
      margin-bottom: 20px;
    }
    
    .footer {
      background-color: var(--dark-bg);
      color: white;
      padding: 40px 0;
      text-align: center;
      margin-top: 50px;
    }
    
    .footer-title {
      font-family: 'Playfair Display', serif;
      font-size: 24px;
      margin-bottom: 20px;
    }
    
    .social-icons {
      margin: 20px 0;
    }
    
    .social-icons a {
      color: white;
      font-size: 20px;
      margin: 0 10px;
      transition: all 0.3s;
    }
    
    .social-icons a:hover {
      color: var(--accent-color);
      transform: translateY(-3px);
    }

    /* Animation */
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .animated {
      animation: fadeIn 0.8s ease forwards;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
      .navbar-brand {
        font-size: 24px;
      }
      
      .admin-header h2 {
        font-size: 28px;
      }
      
      .section-title {
        font-size: 24px;
      }
      
      .table-responsive {
        overflow-x: auto;
      }
      
      .btn-filter {
        width: 100%;
        margin-bottom: 10px;
      }
      
    }
  </style>
</head>
<!-- HTML -->
<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
      <a class="navbar-brand" href="#">Sen<span>Resto</span>_Escoa</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <a class="nav-link" href="all_menu.php">Menu Client</a>
          </li>
          <?php if ($is_admin): ?>
          <li class="nav-item">
            <a class="nav-link" href="orders.php"> Mes Commandes </a>
          </li>
          <?php endif; ?>
          <li class="nav-item">
            <a class="nav-link" href="logout.php">Déconnexion</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <div class="container">
    <!-- Header -->
    <div class="admin-header animated" style="animation-delay: 0.3s;">
      <div class="admin-badge">Historique des commandes</div>
      <h2>Suivi des Commandes SenResto_Escoa</h2>
      <p>Consultez et gérez facilement toutes les commandes</p>
    </div>

    <!-- Messages de notification pour les actions AJAX -->
    <div id="notification-container" style="position: fixed; top: 80px; right: 20px; z-index: 9999;"></div>

    <!-- Filters Section -->
    <div class="filter-section animated" style="animation-delay: 0.5s;">
      <h4 class="filter-title">Filtres</h4>
      
      <!-- Status Filters -->
      <div class="d-flex flex-wrap mb-4">
        <a href="orders.php" class="btn btn-filter btn-filter-all <?php echo !isset($_GET['statut']) ? 'active' : ''; ?>">
          <i class="fas fa-list-ul me-2"></i>Toutes
        </a>
        <a href="orders.php.php?statut=en+attente" class="btn btn-filter btn-filter-pending <?php echo (isset($_GET['statut']) && $_GET['statut'] == 'en attente') ? 'active' : ''; ?>">
          <i class="fas fa-clock me-2"></i>En attente
        </a>
        <a href="orders.php.php?statut=en+préparation" class="btn btn-filter btn-filter-preparing <?php echo (isset($_GET['statut']) && $_GET['statut'] == 'en préparation') ? 'active' : ''; ?>">
          <i class="fas fa-spinner me-2"></i>En préparation
        </a>
        <a href="orders.php.php?statut=livré" class="btn btn-filter btn-filter-delivered <?php echo (isset($_GET['statut']) && $_GET['statut'] == 'livré') ? 'active' : ''; ?>">
          <i class="fas fa-check-circle me-2"></i>Livrées
        </a>
        <a href="orders.php.php?statut=annulé" class="btn btn-filter btn-filter-canceled <?php echo (isset($_GET['statut']) && $_GET['statut'] == 'annulé') ? 'active' : ''; ?>">
          <i class="fas fa-times-circle me-2"></i>Annulées
        </a>
      </div>
      
      <!-- User Filter (Admin Only) -->
      <?php if ($is_admin && !empty($users)): ?>
      <div class="row">
        <div class="col-md-6">
          <form action="" method="GET" class="d-flex align-items-center">
            <div class="input-group">
              <select name="user_id" class="form-control">
                <option value="">Tous les utilisateurs</option>
                <?php foreach ($users as $user): ?>
                  <option value="<?php echo $user['id']; ?>" <?php echo ($user_id == $user['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($user['nom'] . ' ' . $user['prenom']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
              
              <?php if (isset($_GET['statut']) && !empty($_GET['statut'])): ?>
                <input type="hidden" name="statut" value="<?php echo htmlspecialchars($_GET['statut']); ?>">
              <?php endif; ?>
              
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter me-2"></i>Filtrer
              </button>
            </div>
          </form>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Orders Section -->
    <div class="orders-section animated" style="animation-delay: 0.7s;">
      <h3 class="section-title">Liste des commandes</h3>
      
      <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
          <i class="fas fa-exclamation-circle me-2"></i>
          Erreur: <?php echo $error_message; ?>
        </div>
      <?php endif; ?>
      
      <?php if (empty($orders)): ?>
        <div class="alert alert-info">
          <i class="fas fa-info-circle me-2"></i>
          Aucune commande trouvée avec les critères sélectionnés.
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-striped table-hover">
            <thead>
              <tr>
                <th>ID</th>
                <th>Client</th>
                <th>Contact</th>
                <th>Montant</th>
                <th>Statut</th>
                <th>Date</th>
                <th>mode_paiement</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php 
              $animationDelay = 0.8;
              foreach ($orders as $order): 
                $animationDelay += 0.1;
                
                // Définir la classe de badge selon le statut
                $badge_class = '';
                switch ($order['statut']) {
                    case 'en attente':
                        $badge_class = 'badge-pending';
                        break;
                    case 'en préparation':
                        $badge_class = 'badge-preparing';
                        break;
                    case 'livré':
                        $badge_class = 'badge-delivered';
                        break;
                    case 'annulé':
                        $badge_class = 'badge-canceled';
                        break;
                    default:
                        $badge_class = 'badge-secondary';
                }
              ?>
                <tr class="animated order-row" data-id="<?php echo $order['id']; ?>" style="animation-delay: <?php echo $animationDelay; ?>s;">
                  <td>#<?php echo $order['id']; ?></td>
                  <td><?php echo htmlspecialchars($order['nom'] . ' ' . $order['prenom']); ?></td>
                  <td><?php echo htmlspecialchars($order['telephone']); ?></td>
                  <td><?php echo number_format($order['total_amount'], 0, ',', ' ') . ' FCFA'; ?></td>
                  <td class="status-cell">
                    <span class="badge <?php echo $badge_class; ?>" id="status-badge-<?php echo $order['id']; ?>">
                      <?php echo ucfirst($order['statut']); ?>
                    </span>
                  </td>
                  <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                  <td><?php echo ($order['mode_paiement'] !== null) ? htmlspecialchars($order['mode_paiement']) : 'Non spécifié'; ?></td>
                  <td>
                    <button type="button" class="btn btn-details" data-bs-toggle="modal" data-bs-target="#orderDetails<?php echo $order['id']; ?>">
                      <i class="fas fa-eye"></i> Détails
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <!-- Order Details Modals -->
    <?php foreach ($orders as $order): 
      // Définir la classe de badge selon le statut
      $badge_class = '';
      switch ($order['statut']) {
          case 'en attente':
              $badge_class = 'badge-pending';
              break;
          case 'en préparation':
              $badge_class = 'badge-preparing';
              break;
          case 'livré':
              $badge_class = 'badge-delivered';
              break;
          case 'annulé':
              $badge_class = 'badge-canceled';
              break;
          default:
              $badge_class = 'badge-secondary';
      }
    ?>
    <div class="modal fade" id="orderDetails<?php echo $order['id']; ?>" tabindex="-1" aria-labelledby="orderDetailsLabel<?php echo $order['id']; ?>" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="orderDetailsLabel<?php echo $order['id']; ?>">
              Détails de la commande #<?php echo $order['id']; ?>
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-md-6">
                <div class="customer-info-section">
                  <h5><i class="fas fa-user me-2"></i>Informations client</h5>
                  <p><strong>Nom:</strong> <?php echo htmlspecialchars($order['nom']); ?></p>
                  <p><strong>Prenom:</strong> <?php echo htmlspecialchars($order['prenom']); ?></p>
                  <p><strong>Téléphone:</strong> <?php echo htmlspecialchars($order['telephone']); ?></p>
                  <p><strong>Mode_Paiement:</strong> <?php echo htmlspecialchars($order['mode_paiement']); ?></p>
                </div>
              </div>
              <div class="col-md-6">
                <div class="order-info-section">
                  <h5><i class="fas fa-info-circle me-2"></i>Informations commande</h5>
                  <p><strong>Date:</strong> <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
                  <p>
                    <strong>Statut:</strong> 
                    <span class="badge <?php echo $badge_class; ?>" id="modal-status-badge-<?php echo $order['id']; ?>">
                      <?php echo ucfirst($order['statut']); ?>
                    </span>
                  </p>
                </div>
              </div>
            </div>
            
            <hr>
            
            <!-- Order Items -->
            <div class="order-items-section">
              <h5><i class="fas fa-shopping-cart me-2"></i>Articles commandés</h5>
              
              <?php
              try {
                  // Récupérer les articles de la commande
                  $items_query = "SELECT oi.*, 
                            CASE 
                              WHEN mi.id_menu IS NOT NULL THEN mi.name
                              WHEN ff.id_ff IS NOT NULL THEN ff.name
                              WHEN ab.id_ab IS NOT NULL THEN ab.name
                              ELSE 'Produit inconnu'
                            END AS item_name,
                            CASE 
                              WHEN mi.id_menu IS NOT NULL THEN mi.categorie
                              WHEN ff.id_ff IS NOT NULL THEN ff.categorie
                              WHEN ab.id_ab IS NOT NULL THEN ab.categorie
                              ELSE 'Catégorie inconnue'
                            END AS item_categorie
                    FROM order_items oi
                    LEFT JOIN menu_items mi ON oi.item_id = mi.id_menu
                    LEFT JOIN ff_items ff ON oi.item_id = ff.id_ff
                    LEFT JOIN ab_items ab ON oi.item_id = ab.id_ab
                    WHERE oi.order_id = :order_id";
                  
                  $items_stmt = $conn->prepare($items_query);
                  $items_stmt->bindParam(':order_id', $order['id']);
                  $items_stmt->execute();
                  $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
                  
                  if (count($items) > 0): ?>
                    <div class="table-responsive">
                      <table class="table table-bordered">
                        <thead>
                          <tr>
                            <th>Produit</th>
                            <th>Catégorie</th>
                            <th>Quantité</th>
                            <th>Prix unitaire</th>
                            <th>Total</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php 
                          $sous_total = 0;
                          foreach ($items as $item): 
                            $total_item = $item['quantity'] * $item['price'];
                            $sous_total += $total_item;
                          ?>
                          <tr>
                            <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['item_categorie']); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td><?php echo number_format($item['price'], 0, ',', ' ') . ' FCFA'; ?></td>
                            <td><?php echo number_format($total_item, 0, ',', ' ') . ' FCFA'; ?></td>
                          </tr>
                          <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                          <tr>
                            <td colspan="4" class="text-end"><strong>Total:</strong></td>
                            <td><strong><?php echo number_format($order['total_amount'], 0, ',', ' ') . ' FCFA'; ?></strong></td>
                          </tr>
                        </tfoot>
                      </table>
                    </div>
                  <?php else: ?>
                    <div class="alert alert-warning">
                      <i class="fas fa-exclamation-triangle me-2"></i>
                      Aucun détail disponible pour cette commande.
                    </div>
                  <?php endif;
              } catch (PDOException $e) { ?>
                <div class="alert alert-danger">
                  <i class="fas fa-exclamation-circle me-2"></i>
                  Erreur lors de la récupération des détails: <?php echo $e->getMessage(); ?>
                </div>
              <?php } ?>
            </div>
          </div>
          <div class="modal-footer" id="modal-footer-<?php echo $order['id']; ?>">
            <button type="button" class="btn btn-close-modal" data-bs-dismiss="modal">
              <i class="fas fa-times me-2"></i>Fermer
            </button>
            
            <?php if ($is_admin): ?>
              <?php if ($order['statut'] == 'en attente'): ?>
                <button class="btn btn-info update-status-btn" data-id="<?php echo $order['id']; ?>" data-action="preparation">
                  <i class="fas fa-spinner me-2"></i>Marquer en préparation
                </button>
                <button class="btn btn-danger update-status-btn" data-id="<?php echo $order['id']; ?>" data-action="annuler">
                  <i class="fas fa-times-circle me-2"></i>Annuler
                </button>
              <?php elseif ($order['statut'] == 'en préparation'): ?>
                <button class="btn btn-success update-status-btn" data-id="<?php echo $order['id']; ?>" data-action="livrer">
                  <i class="fas fa-check-circle me-2"></i>Marquer comme livrée
                </button>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>

    <?php include('footer.php'); ?>
  </div>

  <!-- Scripts JS -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    console.log("Document ready - jQuery is working"); // Ajoutez cette ligne pour vérifier si jQuery est chargé
    
    // Améliorer l'ouverture des modales
    $('.btn-details').on('click', function() {
        var targetModal = $(this).data('bs-target');
        $(targetModal).modal('show');
    });

    // Pour les boutons de mise à jour du statut
    $('.update-status-btn').on('click', function() {
        var orderId = $(this).data('id');
        var action = $(this).data('action');
        
        console.log("Button clicked - Order ID:", orderId, "Action:", action); // Ajoutez cette ligne pour debug
        
        // Confirmation avant action
        if (confirm('Êtes-vous sûr de vouloir ' + (action === 'annuler' ? 'annuler cette commande' : 'changer le statut de cette commande') + '?')) {
            updateOrderStatus(orderId, action);
        }
    });
    
    // Appelez la fonction au chargement initial
    reattachEventHandlers();

    // Fonction pour mettre à jour le statut d'une commande via AJAX
    function updateOrderStatus(orderId, action) {
        // Afficher un indicateur de chargement
        $('#modal-footer-' + orderId).prepend('<span class="spinner-border spinner-border-sm me-2" id="loading-spinner-' + orderId + '"></span>');
        
        console.log("Sending AJAX request to update_order_ajax.php"); // Ajoutez cette ligne
        
        $.ajax({
            url: 'update_order_ajax.php',
            type: 'POST',
            data: {
                id: orderId,
                action: action
            },
            dataType: 'json',
            success: function(response) {
                console.log("AJAX success response:", response); // Ajoutez cette ligne
                
                // Supprimer l'indicateur de chargement
                $('#loading-spinner-' + orderId).remove();
                
                if (response.success) {
                    // Mettre à jour l'interface utilisateur
                    var newStatus = response.newStatus;
                    var badgeClass = '';
                    
                    switch (newStatus) {
                        case 'en attente':
                            badgeClass = 'badge-pending';
                            break;
                        case 'en préparation':
                            badgeClass = 'badge-preparing';
                            break;
                        case 'livré':
                            badgeClass = 'badge-delivered';
                            break;
                        case 'annulé':
                            badgeClass = 'badge-canceled';
                            break;
                        default:
                            badgeClass = 'badge-secondary';
                    }
                    
                    // Mettre à jour le badge dans la table et dans la modale
                    $('#status-badge-' + orderId)
                        .removeClass('badge-pending badge-preparing badge-delivered badge-canceled badge-secondary')
                        .addClass(badgeClass)
                        .text(newStatus.charAt(0).toUpperCase() + newStatus.slice(1));
                    
                    $('#modal-status-badge-' + orderId)
                        .removeClass('badge-pending badge-preparing badge-delivered badge-canceled badge-secondary')
                        .addClass(badgeClass)
                        .text(newStatus.charAt(0).toUpperCase() + newStatus.slice(1));
                    
                    // Mettre à jour l'heure de mise à jour
                    $('#updated-time-' + orderId).text(response.updatedTime);
                    
                    // Mettre à jour les boutons d'action dans la modale
                    var footerContent = '<button type="button" class="btn btn-close-modal" data-bs-dismiss="modal">' +
                                      '<i class="fas fa-times me-2"></i>Fermer</button>';
                    
                    if (newStatus === 'en attente') {
                        footerContent += '<button class="btn btn-info update-status-btn" data-id="' + orderId + '" data-action="preparation">' +
                                      '<i class="fas fa-spinner me-2"></i>Marquer en préparation</button>' +
                                      '<button class="btn btn-danger update-status-btn" data-id="' + orderId + '" data-action="annuler">' +
                                      '<i class="fas fa-times-circle me-2"></i>Annuler</button>';
                    } else if (newStatus === 'en préparation') {
                        footerContent += '<button class="btn btn-success update-status-btn" data-id="' + orderId + '" data-action="livrer">' +
                                      '<i class="fas fa-check-circle me-2"></i>Marquer comme livrée</button>';
                    }
                    
                    $('#modal-footer-' + orderId).html(footerContent);
                    
                    // Réattacher les gestionnaires d'événements aux nouveaux boutons
                    reattachEventHandlers();
                    
                    // Afficher une notification de succès
                    showNotification('Succès', 'Statut mis à jour avec succès!', 'success');
                    
                    // Rafraîchir la page après un court délai si nécessaire
                    // setTimeout(function() { location.reload(); }, 2000);
                } else {
                    // Afficher une notification d'erreur
                    showNotification('Erreur', response.message || 'Une erreur est survenue lors de la mise à jour.', 'danger');
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX error:", status, error);
                console.log(xhr.responseText); // Ajoutez cette ligne pour voir l'erreur complète
                
                // Supprimer l'indicateur de chargement
                $('#loading-spinner-' + orderId).remove();
                
                // Afficher une notification d'erreur
                showNotification('Erreur', 'Impossible de contacter le serveur.', 'danger');
            }
        });
    }
    
    // Fonction pour réattacher les gestionnaires d'événements
    function reattachEventHandlers() {
        $('.update-status-btn').off('click'); // Supprimer les gestionnaires existants
        
        $('.update-status-btn').on('click', function() {
            var orderId = $(this).data('id');
            var action = $(this).data('action');
            
            console.log("Reattached handler clicked - Order ID:", orderId, "Action:", action); // Debug
            
            if (confirm('Êtes-vous sûr de vouloir ' + (action === 'annuler' ? 'annuler cette commande' : 'changer le statut de cette commande') + '?')) {
                updateOrderStatus(orderId, action);
            }
        });
    }
    
    // Fonction pour afficher des notifications
    function showNotification(title, message, type) {
        var notificationId = 'notification-' + Math.floor(Math.random() * 1000);
        var notification = '<div id="' + notificationId + '" class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
                         '<strong>' + title + ':</strong> ' + message +
                         '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                         '</div>';
        
        // Ajouter la notification au conteneur
        $('#notification-container').append(notification);
        
        // Supprimer automatiquement la notification après 5 secondes
        setTimeout(function() {
            $('#' + notificationId).alert('close');
        }, 5000);
    }
});
</script>