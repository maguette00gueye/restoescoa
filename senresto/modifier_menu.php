<?php
// Connexion à la base de données
include "config/database.php";

// Vérifier si l'ID du plat a été passé en paramètre
if (isset($_POST['id_menu'])) {
    $id_menu = $_POST['id_menu'];

    // Récupérer les informations du plat
    $sql = "SELECT * FROM menu_items WHERE id_menu = :id_menu";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id_menu', $id_menu);
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
    <h2>Modifier Plat  </h2>
    <form action="update_menu.php" method="POST">
        <div class="mb-3">
            <label for="name" class="form-label">Nom Du Plat </label>
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

        <!-- Champ Heure -->
        <div class="mb-4">
            <label for="heure" class="form-label">Heure</label>
            <input type="time" class="form-control" id="heure" name="heure" required>
        </div>

        <div class="mb-3">
            <label for="categorie" class="form-label">Catégorie</label>
            <select class="form-control" id="categorie" name="categorie" required>
                <option value="Petit_dejeuner" <?php if ($row['categorie'] == 'Petit_dejeuner') echo 'selected'; ?>>Petit_dejeuner</option>
                <option value="Repas" <?php if ($row['categorie'] == 'Repas') echo 'selected'; ?>>Repas</option>
                <option value="Diner" <?php if ($row['categorie'] == 'Diner') echo 'selected'; ?>>Diner</option>
                <option value="plat_special" <?php if ($row['categorie'] == 'plat_special') echo 'selected'; ?>>Plat_Special</option>
                <option value="#" <?php if ($row['categorie'] =='#') echo 'selected'; ?>>J#</option>
            </select>
        </div>
    
        <input type="hidden" name="id_menu" value="<?php echo $row['id_menu']; ?>">
        <button type="submit" class="btn btn-primary">Mettre à jour</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
