<?php
// Connexion à la base de données
include "config/database.php";

// Vérifier si l'ID est passé dans la requête POST
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_menu'])) {
    $id = $_POST['id_menu'];  // Utilisez $id ici au lieu de $id_ff

    // Supprimer le plat de la base de données
    $sql = "DELETE FROM menu_items WHERE id_menu = :id_menu";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id_menu', $id);  // Utilisez $id ici

    if ($stmt->execute()) {
        echo "Plat supprimé avec succès!";
        // Redirection après suppression
        header("Location: all_menu.php");
        exit;  // Assurez-vous de sortir après la redirection
    } else {
        echo "Erreur lors de la suppression!";
    }
}
?>
