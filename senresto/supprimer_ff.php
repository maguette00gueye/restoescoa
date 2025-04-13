<?php
// Connexion à la base de données
include "config/database.php";

// Vérifier si l'ID est passé dans la requête POST
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_ff'])) {
    $id = $_POST['id_ff'];  // Utilisez $id ici au lieu de $id_ff

    // Supprimer le plat de la base de données
    $sql = "DELETE FROM ff_items WHERE id_ff = :id_ff";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id_ff', $id);  // Utilisez $id ici

    if ($stmt->execute()) {
        echo "Plat supprimé avec succès!";
        // Redirection après suppression
        header("Location: all_ff.php");
        exit;  // Assurez-vous de sortir après la redirection
    } else {
        echo "Erreur lors de la suppression!";
    }
}
?>
