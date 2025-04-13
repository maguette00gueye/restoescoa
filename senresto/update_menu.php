<?php
// Connexion à la base de données
include "config/database.php";

if (isset($_POST['id_menu'], $_POST['name'], $_POST['description'], $_POST['price'], $_POST['categorie'],$_POST['heure'])) {
    $id_menu = $_POST['id_menu'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $categorie = $_POST['categorie'];
    $heure = $_FILES['heure']['heure'];
    // Mettre à jour le plat dans la base de données
    $sql = "UPDATE menu_items SET name = :name, description = :description, price = :price, categorie = :categorie, heure =:heure WHERE id_menu= :id_menu";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id_menu', $id_menu);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':price', $price);
    $stmt->bindParam(':categorie', $categorie);
    $stmt->bindParam(':heure', $heure);
    if ($stmt->execute()) {
        echo "<p>Plat mis à jour avec succès!</p>";
        header("Location: all_menu.php"); // Redirigez vers la page de liste des plats
        exit;
    } else {
        echo "<p>Erreur lors de la mise à jour.</p>";
    }
} else {
    echo "<p>Informations manquantes.</p>";
}
?>
