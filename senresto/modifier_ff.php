<?php
// Connexion à la base de données
include "config/database.php";

// Vérifier si l'ID du plat a été passé en paramètre
if (isset($_POST['id_ff'])) {
    $id_ff = $_POST['id_ff'];

    // Récupérer les informations du plat
    $sql = "SELECT * FROM ff_items WHERE id_ff = :id_ff";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id_ff', $id_ff);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo "<p>Plat non trouvé.</p>";
        exit;
    }
} else {
    echo "<p>ID du plat manquant.</p>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Plat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container">
    <h2>Modifier le plat</h2>
    <form action="update_ff.php" method="POST">
        <div class="mb-3">
            <label for="name" class="form-label">Nom du plat</label>
            <input type="text" class="form-control" id="name" name="name" value="<?php echo $row['name']; ?>" required>
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description" required><?php echo $row['description']; ?></textarea>
        </div>
        <div class="mb-3">
            <label for="price" class="form-label">Prix (en FCFA)</label>
            <input type="number" class="form-control" id="price" name="price" value="<?php echo $row['price']; ?>" required>
        </div>
        <div class="mb-3">
            <label for="categorie" class="form-label">Catégorie</label>
            <select class="form-control" id="categorie" name="categorie" required>
                <option value="burger" <?php if ($row['categorie'] == 'burger') echo 'selected'; ?>>Burger</option>
                <option value="tacos" <?php if ($row['categorie'] == 'tacos') echo 'selected'; ?>>tacos</option>
                <option value="pizza" <?php if ($row['categorie'] == 'pizza') echo 'selected'; ?>>Pizza</option>
                <option value="baguette" <?php if ($row['categorie'] =='baguette') echo 'selected'; ?>>baguette</option>
            </select>
        </div>
        <input type="hidden" name="id_ff" value="<?php echo $row['id_ff']; ?>">
        <button type="submit" class="btn btn-primary">Mettre à jour</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
