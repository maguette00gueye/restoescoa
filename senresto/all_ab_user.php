<?php
// Démarrer la session
session_start();
// Connexion à la base de données
// Initialiser le panier si ce n'est pas encore fait
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = []; // Initialiser le panier comme un tableau vide si non défini
}

// Connexion à la base de données
include "config/database.php";

// Récupérer la valeur de la recherche et de la catégorie
$search = isset($_GET['search']) ? $_GET['search'] : '';
$categorie = isset($_GET['categorie']) ? $_GET['categorie'] : '';

// Récupérer les catégories distinctes
$sqlCategories = "SELECT DISTINCT categorie FROM ab_items";
$stmtCategories = $conn->query($sqlCategories);
$categories = $stmtCategories->fetchAll(PDO::FETCH_COLUMN);

// Vérifier si un article a été ajouté au panier
if (isset($_POST['add_to_cart'])) {
    $item_id = $_POST['item_id'];
    $item_name = $_POST['item_name'];
    $item_price = $_POST['item_price'];
    $item_quantity = $_POST['item_quantity'];

    // Ajouter l'article au panier dans la session
    $_SESSION['cart'][] = [
        'id' => $item_id,
        'name' => $item_name,
        'price' => $item_price,
        'quantity' => $item_quantity
    ];
}
// Variable pour afficher le message de succès et le bouton "Voir panier"
//$added_to_cart = true;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SenResto_Escoa - Boissons</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
     :root {
      --primary-color:rgb(189, 135, 19);
      --secondary-color:rgb(218, 171, 67);
      --accent-color:rgb(224, 171, 55);
      --text-color:rgb(20, 2, 117);
      --light-bg:rgb(205, 227, 241);
      --dark-bg:rgb(22, 22, 23);
    }
    
    body {
      font-family: 'Poppins', sans-serif;
      background-image: url('https://images.unsplash.com/photo-1493770348161-369560ae357d?ixlib=rb-1.2.1&auto=format&fit=crop&w=2100&q=80');
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
    
    .menu-header {
      text-align: center;
      margin-bottom: 40px;
      padding: 30px;
      background-color: rgba(255, 255, 255, 0.9);
      border-radius: 15px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
    }
    
    .menu-header h2 {
      font-family: 'Playfair Display', serif;
      font-size: 36px;
      font-weight: 700;
      color: var(--primary-color);
      margin-bottom: 10px;
    }
    
    .menu-header p {
      font-size: 18px;
      color: var(--text-color);
      margin-bottom: 20px;
    }
    
    .search-form {
      background-color: white;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }
    
    .search-form .form-control {
      border: 2px solid #eaeaea;
      border-radius: 8px;
      padding: 12px 15px;
      font-size: 16px;
    }
    
    .search-form .form-control:focus {
      box-shadow: none;
      border-color: var(--secondary-color);
    }
    
    .search-form .btn-primary {
      background-color: var(--primary-color);
      border: none;
      padding: 12px 25px;
      font-weight: 600;
      border-radius: 8px;
      transition: all 0.3s;
    }
    
    .search-form .btn-primary:hover {
      background-color: var(--secondary-color);
      transform: translateY(-2px);
    }
    
    .category-title {
      font-family: 'Playfair Display', serif;
      font-size: 28px;
      font-weight: 600;
      color: var(--secondary-color);
      margin: 40px 0 20px;
      padding-bottom: 10px;
      border-bottom: 3px solid var(--accent-color);
      display: inline-block;
    }
    
    .category-section {
      margin-bottom: 50px;
      background-color: rgba(255, 255, 255, 0.9);
      padding: 30px;
      border-radius: 15px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    }
    
    .card {
      border: none;
      border-radius: 15px;
      overflow: hidden;
      transition: all 0.4s ease;
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
      height: 100%;
      margin-bottom: 30px;
    }
    
    .card:hover {
      transform: translateY(-10px);
      box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
    }
    
    .card-img-container {
      position: relative;
      overflow: hidden;
      height: 220px;
    }
    
    .card-img-top {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: all 0.5s;
    }
    
    .card:hover .card-img-top {
      transform: scale(1.1);
    }
    
    .card-category {
      position: absolute;
      top: 15px;
      right: 15px;
      background: var(--primary-color);
      color: white;
      padding: 5px 15px;
      border-radius: 30px;
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 1px;
    }
    
    .card-body {
      padding: 25px;
      text-align: center;
    }
    
    .card-title {
      font-family: 'Playfair Display', serif;
      font-size: 22px;
      font-weight: 600;
      color: var(--text-color);
      margin-bottom: 10px;
    }
    
    .card-description {
      font-size: 14px;
      color: #666;
      height: 60px;
      overflow: hidden;
      margin-bottom: 15px;
    }
    
    .card-price {
      font-size: 24px;
      font-weight: 700;
      color: var(--primary-color);
      margin: 15px 0;
    }
    
    .quantity-control {
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 15px;
    }
    
    .quantity-btn {
      background: var(--light-bg);
      border: none;
      width: 35px;
      height: 35px;
      border-radius: 50%;
      font-size: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.3s;
    }
    
    .quantity-btn:hover {
      background: var(--accent-color);
      color: white;
    }
    
    .quantity-input {
      width: 50px;
      text-align: center;
      font-size: 16px;
      font-weight: 600;
      border: none;
      padding: 5px;
      margin: 0 10px;
      background: transparent;
    }
    
    .add-to-cart-btn {
      background-color: var(--secondary-color);
      color: white;
      border: none;
      padding: 12px 25px;
      border-radius: 8px;
      font-weight: 600;
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      transition: all 0.3s;
    }
    
    .add-to-cart-btn:hover {
      background-color: var(--primary-color);
      transform: translateY(-3px);
    }
    
    .cart-button {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 1000;
    }
    
    .cart-btn {
      background-color: var(--primary-color);
      color: white;
      border: none;
      padding: 12px 20px;
      border-radius: 50px;
      display: flex;
      align-items: center;
      gap: 10px;
      font-weight: 600;
      transition: all 0.3s;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .cart-btn:hover {
      background-color: var(--secondary-color);
      transform: translateY(-3px);
    }
    
    .cart-count {
      background-color: white;
      color: var(--primary-color);
      width: 25px;
      height: 25px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 12px;
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
      
      .menu-header h2 {
        font-size: 28px;
      }
      
      .category-title {
        font-size: 24px;
      }
      
      .card-img-container {
        height: 180px;
      }
    }
  </style>
</head>
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
            <a class="nav-link" href="homepage.php">Accueil</a>
          </li>
          <li class="nav-item">
            <a class="nav-link active" href="#">Boissons</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#">À propos</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#">Contact</a>
          </li>
          <li class="nav-item">
          <a class="nav-link" href="panier.php">
            <i class="fas fa-shopping-cart"></i> Voir panier
            <span class="cart-count"><?php echo count($_SESSION['cart'] ?? []); ?></span>
          </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="logout.php">se deconnecter </a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Cart Button -->
  <div class="cart-button animated" style="animation-delay: 0.3s;">
    <a href="panier.php" class="cart-btn">
      <i class="fas fa-shopping-cart"></i>
      Panier
      <span class="cart-count"><?php echo count($_SESSION['cart'] ?? []); ?></span>
    </a>
  </div>
  <!-- Notification d'ajout au panier avec bouton "Voir panier" -->
  <?php if (isset($added_to_cart) && $added_to_cart): ?>
  <div class="cart-notification" id="cartNotification">
    <div>
      <i class="fas fa-check-circle"></i>
      Article ajouté au panier avec succès!
    </div>
    <a href="panier.php" class="view-cart-btn">Voir panier</a>
    <button type="button" onclick="closeNotification()" class="btn-close btn-close-white"></button>
  </div>
  <?php endif; ?>

  <!-- Main Content -->
  <div class="container">
    <!-- Header -->
    <div class="menu-header animated" style="animation-delay: 0.5s;">
      <h2>Découvrez nos Boissons</h2>
      <p>Retrouvez une large gamme de boissons pour accompagner vos plats sénégalais</p>

      <!-- Search Form -->
      <div class="search-form">
        <form method="GET" class="row g-3">
          <div class="col-md-5">
            <input type="text" name="search" class="form-control" placeholder="Rechercher par nom" value="<?php echo htmlspecialchars($search); ?>">
          </div>
          <div class="col-md-5">
            <select name="categorie" class="form-control">
              <option value="">Toutes les catégories</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat; ?>" <?php echo ($categorie == $cat) ? 'selected' : ''; ?>><?php echo ucfirst($cat); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">
              <i class="fas fa-search"></i> Rechercher
            </button>
          </div>
        </form>
      </div>
    </div>

    <?php
    $animationDelay = 0.7;

    // Boucle sur chaque catégorie pour afficher les éléments
    foreach ($categories as $cat) {
        // Construire la requête SQL
        $sql = "SELECT * FROM ab_items WHERE categorie = :categorie";
        if ($search != '') {
            $sql .= " AND name LIKE :search";
        }
        if ($categorie != '' && $categorie != $cat) {
            continue; // Sauter cette catégorie si une autre est sélectionnée
        }

        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':categorie', $cat);
        if ($search != '') {
            $stmt->bindValue(':search', '%' . $search . '%');
        }
        $stmt->execute();

        // Vérifier si la catégorie contient des éléments
        if ($stmt->rowCount() > 0) {
            echo "<div class='category-section animated' style='animation-delay: {$animationDelay}s;'>";
            echo "<h3 class='category-title'>" . ucfirst($cat) . "</h3>";
            echo "<div class='row'>";

            // Affichage des éléments
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $animationDelay += 0.1;
                echo '<div class="col-lg-4 col-md-6 mb-4">';
                echo '  <div class="card animated" style="animation-delay: ' . $animationDelay . 's;">';
                echo '    <div class="card-img-container">';
                echo '      <img src="src/images/' . htmlspecialchars($row['image']) . '" class="card-img-top" alt="' . htmlspecialchars($row['name']) . '">';
                echo '      <div class="card-category">' . ucfirst($row['categorie']) . '</div>';
                echo '    </div>';
                echo '    <div class="card-body">';
                echo '      <h5 class="card-title">' . htmlspecialchars($row['name']) . '</h5>';
                echo '      <p class="card-description">' . htmlspecialchars($row['description']) . '</p>';
                echo '      <div class="card-price">' . number_format($row['price'], 0, ',', ' ') . ' FCFA</div>';
                
                // Formulaire pour ajouter au panier avec contrôle de quantité
                echo '      <form method="POST" action="">';
                echo '        <input type="hidden" name="item_id" value="' . $row['id_ab'] . '">';
                echo '        <input type="hidden" name="item_name" value="' . htmlspecialchars($row['name']) . '">';
                echo '        <input type="hidden" name="item_price" value="' . htmlspecialchars($row['price']) . '">';
                echo '        <div class="quantity-control">';
                echo '          <button type="button" class="quantity-btn decrease-btn" onclick="decreaseQuantity(this)"><i class="fas fa-minus"></i></button>';
                echo '          <input type="number" name="item_quantity" value="1" min="1" class="quantity-input" onchange="validateQuantity(this)">';
                echo '          <button type="button" class="quantity-btn increase-btn" onclick="increaseQuantity(this)"><i class="fas fa-plus"></i></button>';
                echo '        </div>';
                echo '        <button type="submit" name="add_to_cart" class="add-to-cart-btn">';
                echo '          <i class="fas fa-cart-plus"></i> Ajouter au panier';
                echo '        </button>';
                echo '      </form>';
                
                echo '    </div>';
                echo '  </div>';
                echo '</div>';
            }

            echo "</div>"; // Fermeture de la div row
            echo "</div>"; // Fermeture de la category-section
        }
    }
    ?>
  </div>

  <!-- Footer -->
  <footer class="footer">
    <div class="container">
      <h3 class="footer-title">SenResto_Escoa</h3>
      <p>La meilleure cuisine sénégalaise à votre porte</p>
      <div class="social-icons">
        <a href="#"><i class="fab fa-facebook-f"></i></a>
        <a href="#"><i class="fab fa-instagram"></i></a>
        <a href="#"><i class="fab fa-twitter"></i></a>
        <a href="#"><i class="fab fa-whatsapp"></i></a>
      </div>
      <p>&copy; 2025 SenResto_Escoa. Tous droits réservés.</p>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Fonctions pour les contrôles de quantité
    function decreaseQuantity(btn) {
      const input = btn.nextElementSibling;
      const currentValue = parseInt(input.value);
      if (currentValue > 1) {
        input.value = currentValue - 1;
      }
    }
    
    function increaseQuantity(btn) {
      const input = btn.previousElementSibling;
      input.value = parseInt(input.value) + 1;
    }
    
    function validateQuantity(input) {
      if (input.value < 1) {
        input.value = 1;
      }
    }
    
    // Animation au défilement
    document.addEventListener('DOMContentLoaded', function() {
      const animatedElements = document.querySelectorAll('.animated');
      
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
          }
        });
      }, { threshold: 0.1 });
      
      animatedElements.forEach(element => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(20px)';
        element.style.transition = 'opacity 0.8s ease, transform 0.8s ease';
        observer.observe(element);
      });
    });
  </script>
</body>
</html>

<?php
// Fermer la connexion à la base de données
$conn = null;
?>