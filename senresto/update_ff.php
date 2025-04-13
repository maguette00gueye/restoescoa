<?php
// Connexion à la base de données
include "config/database.php";

if (isset($_POST['id_ff'], $_POST['name'], $_POST['description'], $_POST['price'], $_POST['categorie'])) {
    $id_ff = $_POST['id_ff'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $categorie = $_POST['categorie'];

    // Mettre à jour le plat dans la base de données
    $sql = "UPDATE ff_items SET name = :name, description = :description, price = :price, categorie = :categorie WHERE id_ff = :id_ff";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id_ff', $id_ff);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':price', $price);
    $stmt->bindParam(':categorie', $categorie);
    
    if ($stmt->execute()) {
        echo "<p>Plat mis à jour avec succès!</p>";
        header("Location: all_ff.php"); // Redirigez vers la page de liste des plats
        exit;
    } else {
        echo "<p>Erreur lors de la mise à jour.</p>";
    }
} else {
    echo "<p>Informations manquantes.</p>";
}
?>
