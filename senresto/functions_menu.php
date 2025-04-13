<?php
// Inclure la configuration
include "config/database.php";

/**
 * Récupère tous les menus de la semaine
 * @return array Les menus de la semaine
 */
function getAllMenu() {
    global $conn;  // Assure-toi d'utiliser la variable $pdo pour la connexion PDO
    $sql = "SELECT * FROM menu ORDER BY created_at";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    
    $menus = [];
    
    // Récupérer tous les résultats avec fetchAll()
    $menus = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $menus;
}
// Récupérer le menu pour un jour spécifique
function getMenuByDay($jour) {
    global $conn;
    $sql = "SELECT * FROM menu WHERE jour_semaine = :jour";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':jour', $jour, PDO::PARAM_STR);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);  // Retourne un seul menu
}

//Récupérer tous les plats d'un menu spécifique
function getMenuItems($menuId) {
    global $conn;
    
    // Initialiser le tableau de résultats
    $result = [
        'repas_items' => [],
        'fast_food_items' => [],
        'a_boire_items' => []
    ];
    
    // Récupérer les repas du menu
    $sql = "SELECT r.id_repas, r.nom, mi.prix AS prix_final 
            FROM menu_items mi 
            JOIN repas_items r ON mi.id_repas = r.id_repas 
            WHERE mi.menu_id = :menuId AND mi.id_repas IS NOT NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':menuId', $menuId, PDO::PARAM_INT);
    $stmt->execute();
    $result['repas_items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les fast-foods du menu
    $sql = "SELECT f.id_ff, f.nom, mi.prix AS prix_final 
            FROM menu_items mi 
            JOIN fast_food_items f ON mi.id_ff = f.id_ff 
            WHERE mi.menu_id = :menuId AND mi.id_ff IS NOT NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':menuId', $menuId, PDO::PARAM_INT);
    $stmt->execute();
    $result['fast_food_items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les boissons du menu
    $sql = "SELECT a.id_ab, a.nom, mi.prix AS prix_final 
            FROM menu_items mi 
            JOIN a_boire_items a ON mi.id_ab = a.id_ab 
            WHERE mi.menu_id = :menuId AND mi.id_ab IS NOT NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':menuId', $menuId, PDO::PARAM_INT);
    $stmt->execute();
    $result['a_boire_items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $result;
}


//Ajouter un nouveau menu pour un jour donné
function addMenu($jour, $date, $description, $nouveau = 0, $disponibilite = 1) {
    global $conn;

    $query = "INSERT INTO menu (jour_semaine, date_menu, description, nouveau, disponibilite) 
              VALUES (:jour_semaine, :date_menu, :description,  :nouveau, :disponibilite)";
    $stmt = $conn->prepare($query);

    $stmt->bindParam(':jour_semaine', $jour);
    $stmt->bindParam(':date_menu', $date);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':nouveau', $nouveau);
    $stmt->bindParam(':disponibilite', $disponibilite);
    return $stmt->execute();
}

//Ajouter un plat à un menu existant
function addMenuItem($menuId, $repas, $ab, $ff) {
    global $conn;
    
    
    // Préparer la requête avec la colonne appropriée
    $sql = "INSERT INTO menu_items (menu_id,ids_repas,ids_ff,ids_ab) VALUES (:menuId,:repas,:ff,:ab )";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':menuId', $menuId, PDO::PARAM_INT);
    $stmt->bindParam(':repas', $repas, PDO::PARAM_STR);
    $stmt->bindParam(':ff', $ff, PDO::PARAM_STR);
    $stmt->bindParam(':ab', $ab, PDO::PARAM_STR);    
    return $stmt->execute();
}
//Mettre à jour la description d'un menu
function updateMenu($menuId, $description) {
    global $conn;
    $sql = "UPDATE menu SET description = :description WHERE id = :menuId";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':description', $description, PDO::PARAM_STR);
    $stmt->bindParam(':menuId', $menuId, PDO::PARAM_INT);
    return $stmt->execute();  // Retourne true si la mise à jour est réussie
}
//Supprimer un menu et tous ses plats associés
function deleteMenu($menuId) {
    global $conn;
    // Commencer une transaction pour s'assurer que les deux suppressions sont effectuées ensemble
    $conn->beginTransaction();
    try {
        // Supprimer d'abord les éléments associés au menu dans la table menu_items
        $sql = "DELETE FROM menu_items WHERE id = :menuId";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':menuId', $menuId, PDO::PARAM_INT);
        $stmt->execute();

        // Supprimer ensuite le menu lui-même
        $sql = "DELETE FROM menu WHERE id = :menuId";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':menuId', $menuId, PDO::PARAM_INT);
        $stmt->execute();

        // Si tout est bien exécuté, on valide la transaction
        $conn->commit();
        return true;
    } catch (Exception $e) {
        // En cas d'erreur, on annule la transaction
        $conn->rollBack();
        return false;
    }
}


// [Le reste des fonctions reste identique...]