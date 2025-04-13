<?php
session_start();
header('Content-Type: application/json');
include "config/database.php";

try {
    // Vérification des données reçues
    if (!isset($_POST['menu_id']) || !isset($_POST['nom']) || !isset($_POST['prix'])) {
        throw new Exception('Données manquantes');
    }

    $menu_id = intval($_POST['menu_id']);
    $nom = strip_tags($_POST['nom']);
    $prix = floatval($_POST['prix']);

    // Vérification du stock et de la disponibilité
    $query = "SELECT stock, disponible FROM menus WHERE id = ? AND est_menu_du_jour = 1";
    $stmt = $conn->prepare($query);
    $stmt->execute([$menu_id]);
    $menu = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$menu || !$menu['disponible'] || $menu['stock'] <= 0) {
        throw new Exception('Menu non disponible');
    }

    // Initialisation du panier
    if (!isset($_SESSION['panier'])) {
        $_SESSION['panier'] = [];
    }

    // Vérification si l'ajout dépasse le stock disponible
    $quantite_actuelle = isset($_SESSION['panier'][$menu_id]) ? $_SESSION['panier'][$menu_id]['quantite'] : 0;
    if ($quantite_actuelle + 1 > $menu['stock']) {
        throw new Exception('Stock insuffisant');
    }

    // Ajout ou mise à jour dans le panier
    if (isset($_SESSION['panier'][$menu_id])) {
        $_SESSION['panier'][$menu_id]['quantite']++;
    } else {
        $_SESSION['panier'][$menu_id] = [
            'id' => $menu_id,
            'nom' => $nom,
            'prix' => $prix,
            'quantite' => 1
        ];
    }

    // Mise à jour du stock dans la base de données
    $query = "UPDATE menus SET stock = stock - 1 WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$menu_id]);

    // Calcul du nouveau total et nombre d'articles
    $total = 0;
    $itemCount = 0;
    foreach ($_SESSION['panier'] as $item) {
        $total += $item['prix'] * $item['quantite'];
        $itemCount += $item['quantite'];
    }

    // Retour du stock restant
    $stock_restant = $menu['stock'] - 1;

    echo json_encode([
        'success' => true,
        'message' => 'Menu ajouté au panier',
        'total' => number_format($total, 2),
        'itemCount' => $itemCount,
        'stock_restant' => $stock_restant
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}