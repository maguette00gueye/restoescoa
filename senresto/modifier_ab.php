<?php
// Connexion à la base de données
include "config/database.php";

// Vérifier si l'ID du plat a été passé en paramètre
if (isset($_POST['id_ab'])) {
    $id_ab = $_POST['id_ab'];

    // Récupérer les informations du plat
    $sql = "SELECT * FROM ab_items WHERE id_ab = :id_ab";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id_ab', $id_ab);
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
    <title>Modifier Boisson </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container">
    <h2>Modifier A boire </h2>
    <form action="modifier_ab.php" method="POST">
        <div class="mb-3">
            <label for="name" class="form-label">Nom A Boire </label>
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
                <option value="eau" <?php if ($row['categorie'] == 'eau') echo 'selected'; ?>>Eau</option>
                <option value="the" <?php if ($row['categorie'] == 'the') echo 'selected'; ?>>The</option>
                <option value="boisson" <?php if ($row['categorie'] == 'boisson') echo 'selected'; ?>>Boisson</option>
                <option value="jus" <?php if ($row['categorie'] =='jus') echo 'selected'; ?>>Jus</option>
            </select>
        </div>
        <input type="hidden" name="id_ab" value="<?php echo $row['id_ab']; ?>">
        <button type="submit" class="btn btn-primary">Mettre à jour</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
