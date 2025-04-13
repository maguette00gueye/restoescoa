<?php
// Inclure les fichiers nécessaires
require_once "config/database.php";

// Vérifier l'authentification
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

// Vérifier si l'utilisateur est admin pour pouvoir modifier les commandes
$is_admin = isset($_SESSION['role_id']) && ($_SESSION['role_id'] == 1 || $_SESSION['role_id'] == 2);
if (!$is_admin) {
    echo json_encode(['success' => false, 'message' => 'Accès refusé. Droits administrateur requis.']);
    exit();
}

// Récupérer les données POST
$order_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($order_id <= 0 || empty($action)) {
    echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
    exit();
}

try {
    // Déterminer le nouveau statut en fonction de l'action
    $new_status = '';
    switch ($action) {
        case 'preparation':
            $new_status = 'en préparation';
            break;
        case 'livrer':
            $new_status = 'livré';
            break;
        case 'annuler':
            $new_status = 'annulé';
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
            exit();
    }
    
    // Mettre à jour le statut de la commande
    $update_query = "UPDATE orders SET statut = :statut, updated_at = NOW() WHERE id = :id";
    $stmt = $conn->prepare($update_query);
    $stmt->bindParam(':statut', $new_status);
    $stmt->bindParam(':id', $order_id);
    $result = $stmt->execute();
    
    if ($result) {
        // Récupérer l'heure de mise à jour formatée
        $time_query = "SELECT DATE_FORMAT(updated_at, '%d/%m/%Y %H:%i') as formatted_time FROM orders WHERE id = :id";
        $time_stmt = $conn->prepare($time_query);
        $time_stmt->bindParam(':id', $order_id);
        $time_stmt->execute();
        $time_data = $time_stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true, 
            'newStatus' => $new_status,
            'updatedTime' => $time_data['formatted_time']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>