<?php
if (session_status() == PHP_SESSION_NONE) {
  session_start();
}

// Inclusions des fichiers nécessaires
//include "header.php";
include "head.php";

// Vérifier si le panier n'est pas vide
if (empty($_SESSION['cart'])) {
    die('Le panier est vide. Vous devez ajouter des articles avant de pouvoir payer.');
}

// Calculer le total
$total = 0;
foreach ($_SESSION['cart'] as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Supprimer un produit du panier
if (isset($_GET['action']) && $_GET['action'] == 'supprimer' && isset($_GET['id'])) {
    $id = $_GET['id'];
    unset($_SESSION['cart'][$id]); // Supprimer l'article par son index
    header('Location: panier.php'); // Rediriger pour éviter de soumettre à nouveau l'URL
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Votre Panier</title>
  
  <!-- Liens vers les styles CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  
  <style>
    /* Styles personnalisés */
    .cart-table {
      margin-top: 20px;
    }
    .cart-actions button {
      width: 120px;
    }
    .cart-total {
      font-size: 1.5rem;
      font-weight: bold;
      margin-top: 20px;
    }

    /* Espace avant le titre "Votre Panier" */
    .section-header h3 {
      margin-top: 85px; /* Ajoute 50px d'espace au-dessus du titre */
    }
  </style>
</head>
<body>

  <!-- Section Panier -->
  <div class="section-header">
    <h3 class="text-center">Votre Panier</h3>

    <!-- Tableau des articles du panier -->
    <table class="table table-striped table-bordered cart-table">
      <thead class="thead-dark">
        <tr>
          <th>Nom</th>
          <th>Quantité</th>
          <th>Prix Unitaire</th>
          <th>Total</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php
        // Afficher les articles du panier
        foreach ($_SESSION['cart'] as $id => $item) {
            $item_total = $item['price'] * $item['quantity'];
            echo "<tr>
                    <td>{$item['name']}</td>
                    <td>{$item['quantity']}</td>
                    <td>{$item['price']} FCFA</td>
                    <td>{$item_total} FCFA</td>
                    <td class='cart-actions'>
                        <a href='?action=supprimer&id={$id}' class='btn btn-danger btn-sm' title='Supprimer'>
                            <i class='fas fa-trash'></i> Supprimer
                        </a>
                        <a href='modifier.php?id={$id}' class='btn btn-warning btn-sm' title='Modifier'>
                            <i class='fas fa-pencil-alt'></i> Modifier
                        </a>
                    </td>
                  </tr>";
        }
        ?>
      </tbody>
    </table>

    <!-- Total du panier -->
    <div class="cart-total text-right">
        <h4>Total : <?php echo $total; ?> FCFA</h4>
    </div>

    <!-- Bouton pour procéder au paiement -->
    <div class="text-center">
        <form action="commande.php" method="POST">
            <button type="submit" class="btn btn-success btn-lg">Passer la Commande </button>
        </form>
    </div>
  </div>

  <!-- Scripts de Bootstrap -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

