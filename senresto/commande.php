<?php
session_start();
// Initialiser le panier s'il n'existe pas
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    // Rediriger vers la page de connexion si l'utilisateur n'est pas connecté
    header("Location: login.php");
    exit();
}

// Inclure le fichier avec les fonctions de gestion des commandes
include "order_function.php";

$conn = connectDB();

// Récupérer uniquement les informations nécessaires de l'utilisateur connecté
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT id, nom, prenom, telephone FROM utilisateurs WHERE id = :user_id");
$stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
$stmt->execute();
//$user = $stmt->fetch(PDO::FETCH_ASSOC); // Récupérer les résultats sous forme de tableau associatif

if ($stmt->rowCount() > 0) {
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    // Si l'utilisateur n'existe pas, rediriger vers la page de connexion
    header("Location: login.php");
    exit();
}

// Vérifier si nous sommes en mode synchronisation du panier
if (isset($_GET['sync_cart']) && $_GET['sync_cart'] == 'true') {
    if (isset($_POST['cart_data'])) {
        $cartData = json_decode($_POST['cart_data'], true);
        if (is_array($cartData)) {
            $_SESSION['cart'] = $cartData;
            echo json_encode(["status" => "success", "message" => "Panier synchronisé avec succès"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Format de panier invalide"]);
        }
        exit;
    }
    exit(json_encode(["status" => "error", "message" => "Aucune donnée de panier reçue"]));
}

// Calculer le total du panier
$totalPanier = 0;
foreach ($_SESSION['cart'] as $item) {
    $totalPanier += $item['price'] * $item['quantity'];
}

// Traitement du formulaire de commande
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_order'])) {
    // Synchroniser le panier une dernière fois au cas où
    if (isset($_POST['js_cart_data']) && !empty($_POST['js_cart_data'])) {
        $jsCartData = json_decode($_POST['js_cart_data'], true);
        if (is_array($jsCartData)) {
            $_SESSION['cart'] = $jsCartData;
            // Recalculer le total après synchronisation
            $totalPanier = 0;
            foreach ($_SESSION['cart'] as $item) {
                $totalPanier += $item['price'] * $item['quantity'];
            }
        }
    }
    
    // Vérifier que le panier n'est pas vide
    if (empty($_SESSION['cart'])) {
        $errorMessage = "Votre panier est vide. Veuillez ajouter des articles avant de passer commande.";
    } else {
        // Récupération du mode de paiement
        $mode_paiement = isset($_POST['mode_paiement']) ? $_POST['mode_paiement'] : '';
        
        // Validation de base
        if (empty($mode_paiement)) {
            $errorMessage = "Veuillez sélectionner un mode de paiement.";
        } else {
            try {
                // Créer une nouvelle commande avec les informations de l'utilisateur
                $fullName = $user['nom'] . ' ' . $user['prenom'];
                $orderId = createOrder(
                    $user_id,
                    $fullName,
                    '',  // Email n'est plus nécessaire
                    $user['telephone'],
                    '',  // Type d'utilisateur n'est plus nécessaire
                    $mode_paiement
                );
                
                if ($orderId) {
                    // Ajouter chaque article à la commande
                    $allItemsAdded = true;
                    foreach ($_SESSION['cart'] as $id => $item) {
                        // Format d'ID supposé: "id_type"
                        $parts = explode('_', $id);
                        $itemId = $parts[0];
                        $itemType = isset($parts[1]) ? $parts[1] : 'default_type';
                        $quantity = $item['quantity'];
                        
                        if ($quantity > 0) {
                            $result = addOrderItem($orderId, $itemId, $itemType, $quantity, $item['price']);
                            if (!$result) {
                                $allItemsAdded = false;
                            }
                        }
                    }
                    
                    if ($allItemsAdded) {
                        // Mettre à jour le montant total de la commande
                        $updateTotalQuery = "UPDATE orders SET total_amount = :total WHERE id = :order_id";
                        $stmt = $conn->prepare($updateTotalQuery);
                        $stmt->bindParam(':total', $totalPanier, PDO::PARAM_STR);
                        $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
                        $stmt->execute();
                                                
                        // Vider le panier dans la session
                        $_SESSION['cart'] = [];
                        
                        // Stocker l'ID de commande dans la session pour l'utiliser sur la page de paiement
                        $_SESSION['current_order_id'] = $orderId;
                        
                        // Redirection vers la page de paiement
                        echo "<script>
                            // Vider également le panier côté JavaScript
                            sessionStorage.removeItem('escoaCart');
                            
                            // Afficher un message avant la redirection
                            alert('Commande #$orderId créée avec succès! Vous allez être redirigé vers la page de paiement.');
                            
                            // Rediriger vers la page de paiement
                            window.location.href = 'paiement.php';
                        </script>";
                        exit();
                    } else {
                        $errorMessage = "Certains articles n'ont pas pu être ajoutés à la commande.";
                    }
                } else {
                    $errorMessage = "Impossible de créer la commande. Veuillez réessayer.";
                }
            } catch (Exception $e) {
                $errorMessage = "Une erreur est survenue: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finaliser votre commande - Restaurant ESCOA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .cart-empty {
            text-align: center;
            padding: 20px;
            font-style: italic;
            color: #6c757d;
        }
        .btn-action {
            margin: 5px;
        }
        .user-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Finaliser votre commande</h1>
        
        <?php if (isset($errorMessage)): ?>
            <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
        <?php endif; ?>
        
        <div id="cart-debug" style="display: none;"></div>
        
        <form method="post" action="" id="order-form">
            <!-- Champ caché pour stocker les données du panier JavaScript -->
            <input type="hidden" name="js_cart_data" id="js_cart_data" value="<?php echo htmlspecialchars(json_encode($_SESSION['cart'])); ?>">
            
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h4>Vos informations</h4>
                        </div>
                        <div class="card-body">
                            <div class="user-info">
                                <div class="row mb-2">
                                    <div class="col-md-4 fw-bold">Nom complet:</div>
                                    <div class="col-md-8"><?php echo htmlspecialchars($user['nom'] . ' ' . $user['prenom']); ?></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-md-4 fw-bold">Téléphone:</div>
                                    <div class="col-md-8"><?php echo htmlspecialchars($user['telephone']); ?></div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="mode_paiement" class="form-label">Mode de paiement</label>
                                <select class="form-control" id="mode_paiement" name="mode_paiement" required>
                                    <option value="">Choisir un mode de paiement</option>
                                    <option value="especes">Espèces</option>
                                    <option value="wave">Wave</option>
                                    <option value="orange_money">Orange Money</option>
                                    <option value="autres">Autres</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4>Votre panier</h4>
                            <a href="panier.php" class="btn btn-sm btn-primary">Ajouter des articles</a>
                        </div>
                        <div class="card-body">
                            <div id="cart-items">
                                <?php if (!empty($_SESSION['cart'])): ?>
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Article</th>
                                                <th>Quantité</th>
                                                <th>Prix</th>
                                                <th>Total</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($_SESSION['cart'] as $id => $item): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                                <td>
                                                    <div class="input-group input-group-sm">
                                                        <button type="button" class="btn btn-sm btn-outline-secondary decrease-qty" data-id="<?php echo $id; ?>">-</button>
                                                        <span class="form-control text-center"><?php echo $item['quantity']; ?></span>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary increase-qty" data-id="<?php echo $id; ?>">+</button>
                                                    </div>
                                                </td>
                                                <td><?php echo number_format($item['price'], 0, ',', ' '); ?> FCFA</td>
                                                <td><?php echo number_format($item['price'] * $item['quantity'], 0, ',', ' '); ?> FCFA</td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-danger remove-item" data-id="<?php echo $id; ?>">
                                                        Supprimer
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <div class="cart-empty">
                                        <p>Votre panier est vide.</p>
                                        <a href="panier.php" class="btn btn-primary">Parcourir le menu</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <hr>
                            <div class="d-flex justify-content-between">
                                <h5>Total:</h5>
                                <h5 id="total-amount"><?php echo number_format($totalPanier, 0, ',', ' '); ?> FCFA</h5>
                            </div>
                            
                            <div class="d-flex mt-3 justify-content-between">
                                <button type="button" id="cancel-order" class="btn btn-danger me-2">Annuler la commande</button>
                                <button type="submit" name="submit_order" id="submit-button" class="btn btn-success flex-grow-1" <?php echo empty($_SESSION['cart']) ? 'disabled' : ''; ?>>
                                    Confirmer la commande
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <?php include "footer.php"; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 1. INITIALISATION: Vérifier et synchroniser le panier
            const jsCartData = sessionStorage.getItem('escoaCart');
            
            // Ajouter un débogage pour voir le contenu du panier
            const debugDiv = document.getElementById('cart-debug');
            if (debugDiv) {
                debugDiv.textContent = 'Panier JS: ' + jsCartData;
            }
            
            // Si le panier existe dans sessionStorage mais pas dans PHP, le synchroniser
            if (jsCartData && jsCartData !== '{}') {
                const jsCart = JSON.parse(jsCartData);
                // Si le panier PHP est vide mais JavaScript a des articles
                if (document.querySelector('.cart-empty') && Object.keys(jsCart).length > 0) {
                    console.log('Synchronisation du panier au chargement de la page');
                    syncCartWithServer(jsCart);
                    return; // On arrête ici car la page va se recharger
                }
            } else {
                // Si le panier JavaScript est vide mais PHP a des articles, synchronisons également
                const phpCartData = document.getElementById('js_cart_data').value;
                if (phpCartData && phpCartData !== '{}' && phpCartData !== '[]') {
                    sessionStorage.setItem('escoaCart', phpCartData);
                    console.log('Panier JavaScript mis à jour depuis PHP');
                }
            }
            
            // 2. FONCTIONS DE GESTION DU PANIER
            function updateCart(itemId, action) {
                // Récupérer le panier de sessionStorage
                let cart = {};
                try {
                    const cartData = sessionStorage.getItem('escoaCart');
                    if (cartData) {
                        cart = JSON.parse(cartData);
                    }
                } catch (error) {
                    console.error("Erreur lors de la récupération du panier:", error);
                    cart = {};
                }
                
                if (action === 'remove') {
                    // Supprimer l'article
                    if (cart[itemId]) {
                        delete cart[itemId];
                    }
                } else if (action === 'increase') {
                    // Augmenter la quantité
                    if (cart[itemId]) {
                        cart[itemId].quantity = (cart[itemId].quantity || 0) + 1;
                    }
                } else if (action === 'decrease') {
                    // Diminuer la quantité
                    if (cart[itemId] && cart[itemId].quantity > 1) {
                        cart[itemId].quantity -= 1;
                    } else if (cart[itemId] && cart[itemId].quantity <= 1) {
                        // Si la quantité est 1 ou moins, supprimer l'article
                        delete cart[itemId];
                    }
                }
                
                // Mettre à jour le sessionStorage
                sessionStorage.setItem('escoaCart', JSON.stringify(cart));
                
                // Synchroniser avec le serveur
                syncCartWithServer(cart);
            }
            
            function syncCartWithServer(cart) {
                // Préparer les données pour l'envoi
                const formData = new FormData();
                formData.append('cart_data', JSON.stringify(cart));
                
                // Mettre à jour le champ caché pour la soumission du formulaire
                document.getElementById('js_cart_data').value = JSON.stringify(cart);
                
                // Envoyer les données au serveur via fetch
                fetch('commande.php?sync_cart=true', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Réponse du serveur:', data);
                    if (data.status === 'success') {
                        // Recharger la page pour afficher le panier mis à jour
                        window.location.reload();
                    } else {
                        console.error('Erreur serveur:', data.message);
                        alert('Erreur lors de la synchronisation du panier');
                    }
                })
                .catch(error => {
                    console.error('Erreur lors de la synchronisation:', error);
                    alert('Erreur de connexion lors de la synchronisation du panier');
                });
            }
            
            // 3. GESTION DES ÉVÉNEMENTS
            
            // Augmenter la quantité d'un article
            document.querySelectorAll('.increase-qty').forEach(function(button) {
                button.addEventListener('click', function() {
                    const itemId = this.getAttribute('data-id');
                    updateCart(itemId, 'increase');
                });
            });
            
            // Diminuer la quantité d'un article
            document.querySelectorAll('.decrease-qty').forEach(function(button) {
                button.addEventListener('click', function() {
                    const itemId = this.getAttribute('data-id');
                    updateCart(itemId, 'decrease');
                });
            });
            
            // Supprimer un article
            document.querySelectorAll('.remove-item').forEach(function(button) {
                button.addEventListener('click', function() {
                    const itemId = this.getAttribute('data-id');
                    updateCart(itemId, 'remove');
                });
            });
            
            // Annuler la commande (vider le panier)
            document.getElementById('cancel-order').addEventListener('click', function() {
                if (confirm('Êtes-vous sûr de vouloir annuler la commande et vider votre panier ?')) {
                    sessionStorage.removeItem('escoaCart');
                    syncCartWithServer({});
                }
            });
            
            // S'assurer que le panier JS est bien envoyé lors de la soumission du formulaire
            document.getElementById('order-form').addEventListener('submit', function(e) {
                const cartData = sessionStorage.getItem('escoaCart');
                if (cartData) {
                    document.getElementById('js_cart_data').value = cartData;
                }
                
                // Si le panier est vide, empêcher la soumission
                if (!cartData || cartData === '{}') {
                    e.preventDefault();
                    alert('Votre panier est vide. Veuillez ajouter des articles avant de passer commande.');
                }
            });
        });
    </script>
</body>
</html>