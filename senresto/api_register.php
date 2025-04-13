<?php
// Initialiser la session si pas déjà fait
session_start();
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST ");
header('Content-Type: application/json');

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

// Traitement des requêtes
if (isset($_POST['register'])) {
    $nom = isset($_POST['name']) ? trim($_POST['name']) : '';
    $prenom = isset($_POST['firstname']) ? trim($_POST['firstname']) : ''; // Ajout du prénom
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $mot_de_passe = isset($_POST['password']) ? $_POST['password'] : '';
    $telephone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $adresse = isset($_POST['address']) ? trim($_POST['address']) : '';
    $type_utilisateur = isset($_POST['type_utilisateur']) ? trim($_POST['type_utilisateur']) : ''; // Ajout du type_utilisateur
     // Générer un role_id aléatoire entre 1 et 9
     $role_id = rand(1, 9);
    
    
    // Validation des données
    if (empty($nom) || empty($email) || empty($mot_de_passe)) {
        $response['message'] = 'Veuillez remplir tous les champs obligatoires (nom, email, mot de passe)';
        echo json_encode($response);
        exit;
    }
    
    // Validation email plus robuste
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Veuillez fournir une adresse email valide';
        echo json_encode($response);
        exit;
    }
    
    // Vérifier si l'email existe déjà
    $stmt = $conn->prepare("SELECT id FROM utilisateurs WHERE email = :email");
    $stmt->bindValue(':email', $email);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $response['message'] = 'Cet email est déjà utilisé';
        echo json_encode($response);
        exit;
    }
    
    // Hasher le mot de passe
    $hashed_password = password_hash($mot_de_passe, PASSWORD_DEFAULT);
    
    // Insérer l'utilisateur - avec type_utilisateur ajouté
    try {
        $stmt = $conn->prepare("INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, telephone, adresse, role_id, type_utilisateur, created_at) 
                              VALUES (:nom, :prenom, :email, :mot_de_passe, :telephone, :adresse, :role_id, :type_utilisateur, NOW())");
        $stmt->bindValue(':nom', $nom);
        $stmt->bindValue(':prenom', $prenom);
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':mot_de_passe', $hashed_password);
        $stmt->bindValue(':telephone', $telephone);
        $stmt->bindValue(':adresse', $adresse);
        $stmt->bindValue(':role_id', $role_id);
        $stmt->bindValue(':type_utilisateur', $type_utilisateur);
        $stmt->execute();
        
        $user_id = $conn->lastInsertId();
        
        $response['status'] = 'success';
        $response['message'] = 'Inscription réussie';
        $response['data'] = [
            'id' => $user_id,
            'role_id' => $role_id,
            'type_utilisateur' => $type_utilisateur  // Ajout du type_utilisateur dans la réponse
        ];
    } catch (PDOException $e) {
        $response['message'] = 'Erreur lors de l\'inscription: ' . $e->getMessage();
    }
} 
else if (isset($_POST['login'])) {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $mot_de_passe = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (empty($email) || empty($mot_de_passe)) {
        $response['message'] = 'Veuillez fournir email et mot de passe';
        echo json_encode($response);
        exit;
    }
    
    // Vérifier les identifiants
    $stmt = $conn->prepare("SELECT id, nom, email, mot_de_passe FROM utilisateurs WHERE email = :email");
    $stmt->bindValue(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($mot_de_passe, $user['mot_de_passe'])) {
        // Créer une session utilisateur
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['nom'];
        $_SESSION['user_email'] = $user['email'];
        
        // Mettre à jour la dernière connexion
        $stmt = $conn->prepare("UPDATE utilisateurs SET derniere_connexion = NOW() WHERE id = :id");
        $stmt->bindValue(':id', $user['id']);
        $stmt->execute();
        
        $response['status'] = 'success';
        $response['message'] = 'Connexion réussie';
        $response['data'] = [
            'user_id' => $user['id'],
            'name' => $user['nom'],
            'email' => $user['email']
        ];
    } else {
        $response['message'] = 'Email ou mot de passe incorrect';
    }
} else {
    $response['message'] = 'Requête non reconnue';
}

// Retourner la réponse
header('Content-Type: application/json');
echo json_encode($response);
exit;
?>