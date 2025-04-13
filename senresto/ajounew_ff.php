<?php
include "config/database.php";  // Vérifiez le chemin correct vers le fichier database.php

// Récupérer les données du formulaire
$name = $_POST['name'];
$description = $_POST['description'];
$price = $_POST['price'];
$categorie = $_POST['categorie'];
$image = $_FILES['image']['name'];  // Assurez-vous que l'image est téléchargée correctement

// Déplacer l'image téléchargée dans le répertoire src/images/
move_uploaded_file($_FILES['image']['tmp_name'], "src/images/" . $image);

// Requête d'insertion
$sql = "INSERT INTO ff_items (name, description, price, categorie, image) VALUES (:name, :description, :price, :categorie, :image)";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':name', $name);
$stmt->bindParam(':description', $description);
$stmt->bindParam(':price', $price);
$stmt->bindParam(':categorie', $categorie);
$stmt->bindParam(':image', $image);

// Exécution de la requête
if ($stmt->execute()) {
    echo "Plat ajouté avec succès!";
    header("Location: all_ff.php");  // Redirige vers la page principale après l'ajout
    exit;
} else {
    echo "Erreur lors de l'ajout du plat!";
}
?>