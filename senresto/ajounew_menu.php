<?php
include "config/database.php";  // Connexion à la base de données

// Vérifier que tous les champs sont bien envoyés
if (!isset($_POST['name'], $_POST['description'], $_POST['price'], $_POST['categorie'], $_POST['date'], $_POST['heure'], $_FILES['image'])) {
    die("Tous les champs sont requis !");
}

// Récupérer les données du formulaire
$name = htmlspecialchars($_POST['name']);
$description = htmlspecialchars($_POST['description']);
$price = htmlspecialchars($_POST['price']);
$categorie = htmlspecialchars($_POST['categorie']);
$date = htmlspecialchars($_POST['date']);
$heure = htmlspecialchars($_POST['heure']);

// Vérifier si une image est bien envoyée
if ($_FILES['image']['error'] !== 0) {
    die("Erreur lors du téléchargement de l'image !");
}

// Vérifier l'extension de l'image
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
if (!in_array($_FILES['image']['type'], $allowed_types)) {
    die("Format d'image non autorisé !");
}

// Déplacer l'image vers le dossier src/images/
$image = basename($_FILES['image']['name']);  // Nettoie le nom du fichier
move_uploaded_file($_FILES['image']['tmp_name'], "src/images/" . $image);

// Requête d'insertion
$sql = "INSERT INTO menu_items (name, description, price, categorie, image, date, heure) 
        VALUES (:name, :description, :price, :categorie, :image, :date, :heure)";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':name', $name);
$stmt->bindParam(':description', $description);
$stmt->bindParam(':price', $price);
$stmt->bindParam(':categorie', $categorie);
$stmt->bindParam(':image', $image);
$stmt->bindParam(':date', $date);
$stmt->bindParam(':heure', $heure);

// Exécution de la requête
if ($stmt->execute()) {
    header("Location: all_menu.php"); // Redirige vers la page principale après l'ajout
    exit;
} else {
    echo "Erreur lors de l'ajout du plat !";
}
?>
