<?php
session_start();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => '', 'data' => []];

// Vérifier la méthode HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Connexion à la base de données
include "config/database.php";

// Gérer les différentes méthodes
switch ($method) {
    case 'GET':
        if (isset($_GET['action'])) {
            $action = $_GET['action'];

            // Action : Voir le menu (Boissons)
            if ($action === 'view_menu_drinks') {
                $search = isset($_GET['search']) ? $_GET['search'] : '';
                $categorie = isset($_GET['categorie']) ? $_GET['categorie'] : '';

                $sql = "SELECT * FROM ab_items WHERE 1";
                if ($search != '') {
                    $sql .= " AND name LIKE :search";  // Recherche par nom
                }
                if ($categorie != '') {
                    $sql .= " AND categorie = :categorie";  // Filtrage par catégorie
                }

                $stmt = $conn->prepare($sql);

                // Lier les paramètres
                if ($search != '') {
                    $stmt->bindValue(':search', '%' . $search . '%');  // Recherche floue sur le nom
                }
                if ($categorie != '') {
                    $stmt->bindValue(':categorie', $categorie);  // Filtrage exact par catégorie
                }

                $stmt->execute();
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if ($items) {
                    foreach ($items as &$item) {
                        $item['price'] = strval($item['price']);
                    }
                    $response['status'] = 'success';
                    $response['data'] = $items;
                } else {
                    $response['status'] = 'error';
                    $response['message'] = 'Aucune boisson trouvée';
                }
            }

            // Action : Voir le menu (Menu général)
            else if ($action === 'view_menu') {
                // Récupérer les paramètres de recherche et catégorie
                $search = isset($_GET['search']) ? $_GET['search'] : '';
                $categorie = isset($_GET['categorie']) ? $_GET['categorie'] : '';

                // Requête SQL pour récupérer les menus filtrés
                $query = "SELECT * FROM menu_items WHERE DATE(date) = CURDATE()"; // Filtrage par date d'aujourd'hui
                if ($categorie) {
                    $query .= " AND categorie = :categorie";
                }
                if ($search) {
                    $query .= " AND name LIKE :search";
                }

                $stmt = $conn->prepare($query);
                if ($categorie) {
                    $stmt->bindValue(':categorie', $categorie);
                }
                if ($search) {
                    $stmt->bindValue(':search', '%' . $search . '%');
                }
                $stmt->execute();
                $menus = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if ($menus) {
                    // Convertir les valeurs numériques en chaînes de caractères pour les menus
                    foreach ($menus as &$menu) {
                        $menu['price'] = strval($menu['price']); // Force le prix à être une chaîne
                    }
                    $response['status'] = 'success';
                    $response['data'] = $menus;
                } else {
                    $response['status'] = 'error';
                    $response['message'] = 'Aucun menu disponible';
                }
            }

            // Action : Voir le menu (Fast-Food)
            else if ($action === 'view_fast_food') {
                $search = isset($_GET['search']) ? $_GET['search'] : '';
                $categorie = isset($_GET['categorie']) ? $_GET['categorie'] : '';

                $query = "SELECT * FROM ff_items WHERE 1";
                if ($search) {
                    $query .= " AND name LIKE :search";
                }
                if ($categorie) {
                    $query .= " AND categorie = :categorie";
                }

                $stmt = $conn->prepare($query);
                if ($search) {
                    $stmt->bindValue(':search', '%' . $search . '%');
                }
                if ($categorie) {
                    $stmt->bindValue(':categorie', $categorie);
                }
                $stmt->execute();
                $fast_foods = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if ($fast_foods) {
                    foreach ($fast_foods as &$fast_food) {
                        $fast_food['price'] = strval($fast_food['price']);
                    }
                    $response['status'] = 'success';
                    $response['data'] = $fast_foods;
                } else {
                    $response['status'] = 'error';
                    $response['message'] = 'Aucun article de fast-food trouvé';
                }
            }
            else {
                $response['message'] = 'Action inconnue';
            }
        } else {
            $response['message'] = 'Aucune action spécifiée';
        }
        break;

    case 'POST':
        // Ajouter un article au panier
        if (isset($_POST['add_to_cart'])) {
            if (isset($_POST['item_id'], $_POST['item_name'], $_POST['item_price'], $_POST['item_quantity'])) {
                $item_id = $_POST['item_id'];
                $item_name = $_POST['item_name'];
                $item_price = $_POST['item_price'];
                $item_quantity = $_POST['item_quantity'];

                if ($item_quantity <= 0 || !is_numeric($item_quantity)) {
                    $response['message'] = 'Quantité invalide';
                    break;
                }

                $item_exists = false;
                foreach ($_SESSION['cart'] as &$item) {
                    if ($item['id'] === $item_id) {
                        $item['quantity'] += $item_quantity;
                        $item_exists = true;
                        break;
                    }
                }

                if (!$item_exists) {
                    $_SESSION['cart'][] = [
                        'id' => $item_id,
                        'name' => $item_name,
                        'price' => $item_price,
                        'quantity' => $item_quantity,
                    ];
                }

                $response['status'] = 'success';
                $response['message'] = 'Article ajouté au panier.';
            } else {
                $response['message'] = 'Paramètres manquants pour ajouter au panier';
            }
        }

        // Supprimer un article du panier
        else if (isset($_POST['remove_from_cart'])) {
            if (isset($_POST['item_id'])) {
                $item_id = $_POST['item_id'];

                foreach ($_SESSION['cart'] as $key => &$item) {
                    if ($item['id'] === $item_id) {
                        unset($_SESSION['cart'][$key]);
                        $response['status'] = 'success';
                        $response['message'] = 'Article supprimé du panier';
                        break;
                    }
                }
                if (!isset($response['status'])) {
                    $response['message'] = 'Article non trouvé dans le panier';
                }
            } else {
                $response['message'] = 'Paramètre manquant: item_id';
            }
        }

        // Procéder au paiement
        else if (isset($_POST['proceed_to_payment'])) {
           if (empty($_SESSION['cart'])) {
             $response['message'] = 'Le panier est vide. Impossible de procéder au paiement.';
              break;
            }

            $total_amount = 0;
            foreach ($_SESSION['cart'] as $item) {
                $total_amount += $item['price'] * $item['quantity'];
            }

            // Inclure la classe PayTech
            include 'PayTech.php';

            // Remplacez par vos clés API
            $apiKey = '9700a81b5ddda71f2b902764fbe7fd4b0fcad1e63b6614172fd6cb0cf57b7cf2'; // Test Mode
            $apiSecret = 'dd092fc0feed7d14e6e8b89b3eb839cd976983ca9544887608ae4fed4a38ffbf';

            // Créez une instance de PayTech
            $paytech = new PayTech($apiKey, $apiSecret);
            $paytech->setTestMode(true); // Activer le mode test, à changer en production lorsque prêt

            // Paramétrez la commande
            $paytech->setQuery([
                'item_name' => 'Commande de restaurant',
                'item_price' => $total_amount,
                'command_name' => 'commande-' . uniqid(),
            ]);

            // Paramétrer la notification de paiement
            $paytech->setCurrency('XOF');
            $paytech->setRefCommand('commande-' . uniqid());
            $paytech->setNotificationUrl([
                'ipn_url' => 'https://votre-site.com/ipn.php',
                'success_url' => 'https://votre-site.com/success.php',
                'cancel_url' => 'https://votre-site.com/cancel.php',
            ]);

            // Demander un paiement
            $paymentResponse = $paytech->send();

            if ($paymentResponse['success'] === 1) {
                $response['status'] = 'success';
                $response['message'] = 'Paiement initié avec succès.';
                $response['data'] = [
                    'payment_url' => $paymentResponse['redirect_url']
                ];
                header('Location: ' . $paymentResponse['redirect_url']);
                exit;
            } else {
                $response['message'] = 'Erreur lors de la demande de paiement: ' . implode(', ', $paymentResponse['errors']);
            }
        }
        break;

    case 'DELETE':
        if (isset($_GET['action']) && $_GET['action'] === 'remove_item' && isset($_GET['id'])) {
            $id = $_GET['id'];
            if (isset($_SESSION['cart'][$id])) {
                unset($_SESSION['cart'][$id]);
                $response['status'] = 'success';
                $response['message'] = 'Article supprimé du panier';
            } else {
                $response['message'] = 'Article non trouvé dans le panier';
            }
        } else {
            $response['message'] = 'Paramètres manquants ou action inconnue';
        }
        break;

    default:
        $response['message'] = 'Méthode non supportée';
        break;
}

echo json_encode($response);
?>
