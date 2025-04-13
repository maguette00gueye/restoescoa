<?php
session_start();
include "header.php";
include "head.php";
// Vérifier si l'ID est passé dans l'URL et si l'article existe dans le panier
if (isset($_GET['id']) && isset($_SESSION['cart'][$_GET['id']])) {
    $id = $_GET['id'];
    $item = $_SESSION['cart'][$id]; // Récupérer l'article
} else {
    echo "Produit non trouvé dans le panier.";
    exit;
}

// Traitement du formulaire pour mettre à jour la quantité
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_quantity = (int)$_POST['quantity'];

    // Vérifier que la quantité est valide
    if ($new_quantity > 0) {
        $_SESSION['cart'][$id]['quantity'] = $new_quantity; // Mettre à jour la quantité dans la session
        header('Location: panier.php'); // Rediriger vers le panier après la mise à jour
        exit;
    } else {
        echo "La quantité doit être supérieure à zéro.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier la Quantité</title>
    
    <!-- Liens vers les styles CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        /* Ajout de l'espace avant le titre */
        .section-header h3 {
            margin-top: 85px; /* Ajuste la valeur de la marge selon le besoin */
        }
        .container {
            margin-top: 30px;
        }
        /* Centrer les boutons */
        .button-container {
            display: flex;
            justify-content: center;
            gap: 15px; /* Espacement entre les boutons */
        }
    </style>
</head>
<body>

    <div class="container">
        <!-- Section Modifier la Quantité -->
        <div class="section-header">
            <h3 class="text-center">Modifier la Quantité de "<?php echo $item['name']; ?>"</h3>
        </div>

        <!-- Formulaire de modification de la quantité -->
        <form method="POST">
            <div class="mb-3">
                <label for="name" class="form-label">Nom du Produit</label>
                <input type="text" class="form-control" id="name" value="<?php echo $item['name']; ?>" disabled>
            </div>
            <div class="mb-3">
                <label for="quantity" class="form-label">Quantité</label>
                <input type="number" class="form-control" id="quantity" name="quantity" value="<?php echo $item['quantity']; ?>" min="1">
            </div>
            
            <!-- Conteneur des boutons centré -->
            <div class="button-container">
                <button type="submit" class="btn btn-success">Mettre à jour</button>
                <a href="panier.php" class="btn btn-primary">Retour au panier</a>
            </div>
        </form>
    </div>

    <!-- Scripts de Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Inclusion du footer
include "footer.php";
?>