<?php
// Initialiser la session si pas déjà fait
session_start();
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
// Connexion à la base de données
include "config/database.php";

// Initialiser la réponse
$response = [
    'status' => 'error',
    'message' => '',
    'data' => []
];

// Vérifier la connexion à la base de données
if (!isset($conn) || $conn === null) {
    $response['message'] = 'Erreur de connexion à la base de données';
    echo json_encode($response);
    exit;
}

// Vérifier si l'utilisateur est connecté ou si un ID est fourni
if (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];
} elseif (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    $response['message'] = 'Utilisateur non authentifié ou ID non fourni';
    echo json_encode($response);
    exit;
}

// Récupérer les informations du profil
try {
    $stmt = $conn->prepare("SELECT id, nom, prenom, email, telephone, adresse, role_id, type_utilisateur, 
                           created_at, derniere_connexion 
                           FROM utilisateurs 
                           WHERE id = :user_id");
    $stmt->bindValue(':user_id', $user_id);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Supprimer le mot de passe des données retournées pour la sécurité
        unset($user['mot_de_passe']);
        
        $response['status'] = 'success';
        $response['message'] = 'Profil récupéré avec succès';
        $response['data'] = $user;
    } else {
        $response['message'] = 'Utilisateur non trouvé';
    }
} catch (PDOException $e) {
    $response['message'] = 'Erreur lors de la récupération du profil: ' . $e->getMessage();
}

// Retourner la réponse
echo json_encode($response);
exit;
?>