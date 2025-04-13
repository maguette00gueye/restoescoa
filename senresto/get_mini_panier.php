<?php
session_start();
header('Content-Type: application/json');
include "config/database.php";

try {
    $html = '';
    $total = 0;
    $itemCount = 0;

    if (!empty($_SESSION['panier'])) {
        foreach ($_SESSION['panier'] as $menu_id => $item) {
            // Vérifier le stock actuel
            $query = "SELECT stock FROM menus WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$menu_id]);
            $menu = $stmt->fetch(PDO::FETCH_ASSOC);

            $sous_total = $item['prix'] * $item['quantite'];
            $total += $sous_total;
            $itemCount += $item['quantite'];

            $html .= '<div class="mini-panier-item" id="panier-item-' . $menu_id . '">
                        <div class="item-info">
                            <h6 class="mb-0">' . htmlspecialchars($item['nom']) . '</h6>
                            <span class="text-muted">' . number_format($item['prix'], 2) . ' €</span>
                        </div>
                        <div class="quantity-control">
                            <button class="quantity-btn" 
                                    onclick="updateQuantite(' . $menu_id . ', ' . ($item['quantite'] - 1) . ')"
                                    ' . ($item['quantite'] <= 1 ? 'disabled' : '') . '>
                                <i class="icon-copy dw dw-minus"></i>
                            </button>
                            <span class="mx-2">' . $item['quantite'] . '</span>
                            <button class="quantity-btn" 
                                    onclick="updateQuantite(' . $menu_id . ', ' . ($item['quantite'] + 1) . ')"
                                    ' . ($menu['stock'] <= 0 ? 'disabled' : '') . '>
                                <i class="icon-copy dw dw-plus"></i>
                            </button>
                            <button class="btn btn-link text-danger" 
                                    onclick="supprimerDuPanier(' . $menu_id . ')">
                                <i class="icon-copy dw dw-delete-3"></i>
                            </button>
                        </div>
                        <div class="item-total">
                            ' . number_format($sous_total, 2) . ' €
                        </div>
                    </div>';
        }

        $html .= '<div class="mini-panier-summary">
                    <div class="d-flex justify-content-between">
                        <strong>Total</strong>
                        <strong>' . number_format($total, 2) . ' €</strong>
                    </div>
                </div>';
    } else {
        $html = '<div class="text-center py-4">
                    <i class="icon-copy dw dw-shopping-cart1" style="font-size: 48px; color: #ccc;"></i>
                    <p class="mt-3">Votre panier est vide</p>
                </div>';
    }

    echo json_encode([
        'success' => true,
        'html' => $html,
        'total' => number_format($total, 2),
        'itemCount' => $itemCount,
        'isEmpty' => empty($_SESSION['panier'])
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'html' => '<div class="alert alert-danger">Une erreur est survenue</div>'
    ]);
}