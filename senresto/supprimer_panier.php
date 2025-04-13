<?php
session_start();
header('Content-Type: application/json');
include "config/database.php";

try {
    if (!isset($_POST['menu_id'])) {
        throw new Exception('ID du menu manquant');
    }

    $menu_id = intval($_POST['menu_id']);

    if (isset($_SESSION['panier'][$menu_id])) {
        // Récupérer la quantité à remettre en stock
        $quantite_a_remettre = $_SESSION['panier'][$menu_id]['quantite'];

        // Mise à jour du stock dans la base de données
        $query = "UPDATE menus SET stock = stock + ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$quantite_a_remettre, $menu_id]);

        // Supprimer l'article du panier
        unset($_SESSION['panier'][$menu_id]);
    }

    // Calcul du nouveau total
    $total = 0;
    $itemCount = 0;
    foreach ($_SESSION['panier'] as $item) {
        $total += $item['prix'] * $item['quantite'];
        $itemCount += $item['quantite'];
    }

    echo json_encode([
        'success' => true,
        'total' => number_format($total, 2),
        'itemCount' => $itemCount
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}