<?php
// admin_menus.php - Interface d'administration des menus
 include "config/database.php";
 include "functions_menu.php";

// Traitement des formulaires
$message = '';
$error = '';

// Ajouter un menu
if (isset($_POST['add_menu'])) {
    $jour = htmlspecialchars($_POST['jour_semaine']);
    $date = $_POST['date_menu'];
    $description = htmlspecialchars($_POST['description']);
    $nouveau = $_POST['nouveau'];
    $disponibilite = $_POST['disponibilite'];

    $menuId = addMenu($jour, $date, $description, $nouveau, $disponibilite);
    if ($menuId) {
        $message = "Menu ajouté avec succès!";
    } else {
        $error = "Un menu existe déjà pour ce jour et cette date. Veuillez vérifier.";
    }
}


// Ajouter un plat au menu
if (isset($_POST['add_menu_item'])) {
    $menuId = $_POST['menu_id'];
    $choix_repas = $_POST['repas'];
    $repas=implode(", ", $choix_repas);
    $choix_ff= $_POST['ff'];
    $ff=implode(", ", $choix_ff);
    $choix_ab = $_POST['ab'];
    $ab=implode(", ", $choix_ab);
    

    if (addMenuItem($menuId, $repas, $ab, $ff)) {
        $message = "Plat ajouté au menu avec succès!";
    } else {
        $error = "Erreur lors de l'ajout du plat au menu.";
    }
}


// Modifier un menu
if (isset($_POST['update_menu'])) {
    $menuId = $_POST['menu_id'];
    $description = htmlspecialchars($_POST['description']);
    $nouveau = $_POST['nouveau'];
    $disponibilite = $_POST['disponibilite'];

    if (updateMenu($menuId, $description, $nouveau, $disponibilite)) {
        $message = "Menu mis à jour avec succès!";
    } else {
        $error = "Erreur lors de la mise à jour du menu.";
    }
}

// Supprimer un menu
if (isset($_POST['delete_menu'])) {
    $menuId = $_POST['menu_id'];
    
    if (deleteMenu($menuId)) {
        $message = "Menu supprimé avec succès!";
    } else {
        $error = "Erreur lors de la suppression du menu.";
    }
}

// Récupérer tous les menus
$menus = getAllMenu();

// Récupérer les plats disponibles
$stmt = $conn->query("SELECT id_repas, nom FROM repas_items");  // Utiliser la connexion PDO $pdo
$repas_Items = $stmt->fetchAll(PDO::FETCH_ASSOC);  // Utiliser fetchAll() avec PDO pour récupérer tous les résultats

$stmt = $conn->query("SELECT id_ff, nom FROM fast_food_items");
$fast_Food_Items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->query("SELECT id_ab, nom FROM a_boire_items");
$a_Boire_Items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Menus - Restaurant</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Gestion des Menus du Restaurant</h1>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Formulaire d'ajout de menu -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Ajouter un nouveau menu</h5>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="form-group">
                                <label for="jour_semaine">Jour de la semaine</label>
                                <select name="jour_semaine" id="jour_semaine" class="form-control" required>
                                    <option value="lundi">Lundi</option>
                                    <option value="mardi">Mardi</option>
                                    <option value="mercredi">Mercredi</option>
                                    <option value="jeudi">Jeudi</option>
                                    <option value="vendredi">Vendredi</option>
                                    <option value="samedi">Samedi</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="date_menu">Date</label>
                                <input type="date" name="date_menu" id="date_menu" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea name="description" id="description" class="form-control" rows="3"></textarea>
                            </div>

                            <div class="form-group">
                                <label for="nouveau">Nouveau menu</label>
                                <select name="nouveau" id="nouveau" class="form-control">
                                    <option value="1">Oui</option>
                                    <option value="0">Non</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="disponibilite">Menu disponible</label>
                                <select name="disponibilite" id="disponibilite" class="form-control">
                                    <option value="1">Oui</option>
                                    <option value="0">Non</option>
                                </select>
                            </div>
                                <button type="submit" name="add_menu" class="btn btn-primary">Ajouter menu</button>
                        </form>
                    </div>
                </div>
            </div>
                                        
            <!-- Formulaire d'ajout de plat au menu -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Ajouter un plat à un menu</h5>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="form-group">
                                <label for="menu_id">Sélectionner un menu</label>
                                <select name="menu_id" id="menu_id" class="form-control" required>
                                    <?php foreach ($menu as $menu): ?>
                                        <option value="<?php echo $menu['id']; ?>">  <!-- Utilise 'id' comme valeur pour l'option -->
                                            <?php echo ucfirst($menu['jour_semaine']) . ' - ' . $menu['date_menu']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                    
                            <div class="form-group" id="repas_select">
                                <label for="repas">Sélectionner un repas</label>
                                <select name="repas[]" class="form-control" multiple required>
                                    <?php foreach ($repas_Items as $item): ?>
                                        <option value="<?php echo $item['id_repas']; ?>"><?php echo $item['nom']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group" id="fast_food_select" >
                                <label for="ab">Sélectionner un fast food</label>
                                <select name="ff[]" class="form-control" multiple required> 
                                    <?php foreach ($fast_Food_Items as $item): ?>
                                        <option value="<?php echo $item['id_ff']; ?>"><?php echo $item['nom']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group" id="a_boire_select" >
                                <label for="ab">Sélectionner une  A Boire </label>
                                <select name="ab[]" class="form-control" multiple required>
                                    <?php foreach ($a_Boire_Items as $item): ?>
                                        <option value="<?php echo $item['id_ab']; ?>"><?php echo $item['nom']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <button type="submit" name="add_menu_item" class="btn btn-primary">Ajouter au menu </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
       
       <!-- liste des menus qui existe ajouter dans la semaine -->
<div class="mt-5">
    <h3> Liste des Menus existants</h3>
    <div class="accordion" id="menuAccordion">
        <?php foreach ($menus as $index => $menu): ?>
            <div class="card">
                <div class="card-header" id="heading<?php echo $index; ?>">
                    <h2 class="mb-0">
                        <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#collapse<?php echo $index; ?>">
                            <?php echo ucfirst($menu['jour_semaine']).' - '. $menu['date_menu']; ?>
                        </button>
                    </h2>
                </div>
                <div id="collapse<?php echo $index; ?>" class="collapse" data-parent="#menuAccordion">

                    <div class="card-body">
                        <p><strong>Description:</strong> <?php echo $menu['description']; ?></p>
                        <p><strong>Nouveau:</strong> <?php echo $menu['nouveau'] ? 'Oui' : 'Non'; ?></p>
                        <p><strong>Disponible:</strong> <?php echo $menu['disponibilite'] ? 'Oui' : 'Non'; ?></p>

                        <!-- Bouton Modifier avec icône -->
                        <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#editMenu<?php echo $menu['menu_id']; ?>">
                            <i class="fa fa-edit"></i> Modifier
                        </button>

                        <!-- Formulaire pour supprimer un plat avec icône -->
                        <form method="post" class="d-inline">
                            <input type="hidden" name="menu_id" value="<?php echo $menu['menu_id']; ?>">
                            <button type="submit" name="delete_menu" class="btn btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce menu?')">
                                <i class="fa fa-trash"></i> Supprimer
                            </button>
                        </form>
                    </div>
                    
                    <?php $menuItems = getMenuItems($menu['menu_id']); ?>
                    
                    <?php if (!empty($menu_items['repas_items'])): ?>
                        <h5>Repas</h5>
                        <ul>
                            <?php foreach ($menu_items['repas_items'] as $item): ?>
                                <li><?php echo $item['nom']; ?> - <?php echo $item['prix_final']; ?> fcfa</li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    
                    <?php if (!empty($menu_items['fast_food_items'])): ?>
                        <h5>Fast Food</h5>
                        <ul>
                            <?php foreach ($menu_items['fast_food_items'] as $item): ?>
                                <li><?php echo $item['nom']; ?> - <?php echo $item['prix_final']; ?> fcfa </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    
                    <?php if (!empty($menu_items['a_boire_items'])): ?>
                        <h5>A Boire</h5>
                        <ul>
                            <?php foreach ($menu_items['a_boire_items'] as $item): ?>
                                <li><?php echo $item['nom']; ?> - <?php echo $item['prix_final']; ?> fcfa</li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    
                    <div class="mt-3">
                        <!-- Bouton Modifier répété ici avec icône -->
                        <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#editMenu<?php echo $menu['menu_id']; ?>">
                            <i class="fa fa-edit"></i> Modifier
                        </button>

                        <!-- Formulaire pour supprimer un plat avec icône -->
                        <form method="post" class="d-inline">
                            <input type="hidden" name="menu_id" value="<?php echo $menu['menu_id']; ?>">
                            <button type="submit" name="delete_menu" class="btn btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce menu?')">
                                <i class="fa fa-trash"></i> Supprimer
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal pour modifier le menu -->
            <div class="modal fade" id="editMenu<?php echo $menu['id']; ?>" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Modifier menu - <?php echo ucfirst($menu['jour_semaine']); ?></h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form method="post">
                                <input type="hidden" name="menu_id" value="<?php echo $menu['id']; ?>">
                                <div class="form-group">
                                    <label for="description">Description</label>
                                    <textarea name="description" class="form-control" rows="3"><?php echo $menu['description']; ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="image_url">URL de l'image</label>
                                    <input type="text" name="image_url" class="form-control" value="<?php echo $menu['image_url']; ?>">
                                </div>
                                <div class="form-group">
                                    <label for="rating">Note Etoile</label>
                                    <input type="number" name="rating" class="form-control" value="<?php echo $menu['rating']; ?>" min="0" max="5" step="0.1">
                                </div>
                                <div class="form-group">
                                    <label for="nouveau">Nouveau menu</label>
                                    <select name="nouveau" class="form-control">
                                        <option value="1" <?php echo $menu['nouveau'] ? 'selected' : ''; ?>>Oui</option>
                                        <option value="0" <?php echo !$menu['nouveau'] ? 'selected' : ''; ?>>Non</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="disponibilite">Disponible</label>
                                    <select name="disponibilite" class="form-control">
                                        <option value="1" <?php echo $menu['disponibilite'] ? 'selected' : ''; ?>>Oui</option>
                                        <option value="0" <?php echo !$menu['disponibilite'] ? 'selected' : ''; ?>>Non</option>
                                    </select>
                                </div>
                                <button type="submit" name="update_menu" class="btn btn-primary">Enregistrer</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

_
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Script pour changer les sélecteurs d'items en fonction du type
        document.addEventListener('DOMContentLoaded', function() {

    // Script pour changer les sélecteurs d'items en fonction du type
       /* document.getElementById('item_type').addEventListener('change', function() {
        var type = this.value;
        document.getElementById('repas_select').style.display = 'none';
        document.getElementById('fast_food_select').style.display = 'none';
        document.getElementById('a_boire_select').style.display = 'none';
        
        switch(type) {
            case 'repas_items':
                document.getElementById('repas_select').style.display = 'block';
                document.querySelector('#repas_select select').setAttribute('required', 'required');
                document.querySelector('#fast_food_select select').removeAttribute('required');
                document.querySelector('#a_boire_select select').removeAttribute('required');
                break;
            case 'fast_food_items':
                document.getElementById('fast_food_select').style.display = 'block';
                document.querySelector('#fast_food_select select').setAttribute('required', 'required');
                document.querySelector('#repas_select select').removeAttribute('required');
                document.querySelector('#a_boire_select select').removeAttribute('required');
                break;
            case 'a_boire_items':
                document.getElementById('a_boire_select').style.display = 'block';
                document.querySelector('#a_boire_select select').setAttribute('required', 'required');
                document.querySelector('#repas_select select').removeAttribute('required');
                document.querySelector('#fast_food_select select').removeAttribute('required');
                break;
        }
    });*/
})
    </script>
</body>
</html>