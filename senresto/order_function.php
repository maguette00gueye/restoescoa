<?php
// Connexion à la base de données
function connectDB() {
    $host = "localhost";
    $dbname = "resto_escoa_db";
    $username = "root";
    $password = "";
    
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        die("Erreur de connexion à la base de données: " . $e->getMessage());
    }
}

// Fonction pour créer une nouvelle commande
// Fonction pour créer une nouvelle commande - Corrigée pour PDO
function createOrder($user_id, $nom, $prenom, $telephone,  $mode_paiement) {
    $conn = connectDB();
    
    try {
        $sql = "INSERT INTO orders (user_id, nom, prenom, telephone,  mode_paiement) 
                VALUES (:user_id, :nom, :prenom, :telephone, :mode_paiement)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':nom', $nom);
        $stmt->bindParam(':prenom', $prenom);
        $stmt->bindParam(':telephone', $telephone);
        $stmt->bindParam(':mode_paiement', $mode_paiement);
        
        if ($stmt->execute()) {
            return $conn->lastInsertId();
        } else {
            return false;
        }
    } catch(PDOException $e) {
        die("Erreur lors de la création de la commande: " . $e->getMessage());
    }
}

// Fonction pour ajouter un article à une commande
// Fonction pour ajouter un article à une commande
function addOrderItem($orderId, $itemId, $itemType, $quantity, $price) {
    $conn = connectDB();
    
    try {
        $conn->beginTransaction();
        
        // Récupérer les détails de l'article
        $itemDetails = getItemDetails($itemId, $itemType);
        
        // Sauvegarder l'article dans order_items avec les détails supplémentaires
        $sql = "INSERT INTO order_items (order_id, item_id, item_type, item_name, item_description, item_categorie, quantity, price)
                VALUES (:order_id, :item_id, :item_type, :item_name, :item_description, :item_categorie, :quantity, :price)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':order_id', $orderId);
        $stmt->bindParam(':item_id', $itemId);
        $stmt->bindParam(':item_type', $itemType);
        $stmt->bindParam(':item_name', $itemDetails['name']);
        $stmt->bindParam(':item_description', $itemDetails['description']);
        $stmt->bindParam(':item_categorie', $itemDetails['categorie']);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':price', $price);
        $stmt->execute();
        
        // Mettre à jour le montant total de la commande
        $sql = "UPDATE orders SET total_amount = total_amount + (:price * :quantity) WHERE id = :order_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':order_id', $orderId);
        $stmt->execute();
        
        $conn->commit();
        return true;
    } catch(PDOException $e) {
        $conn->rollBack();
        die("Erreur lors de l'ajout de l'article à la commande: " . $e->getMessage());
    }
}

// Fonction pour obtenir les informations d'un article par son ID et type
function getItemDetails($itemId, $itemType) {
    $conn = connectDB();
    
    // Log pour le débogage
    error_log("Récupération des détails pour item_id: $itemId, item_type: $itemType");
    
    try {
        $tableName = "";
        $idColumn = "";
        $nameColumn = "";
        $descColumn = "";
        $catColumn = "";
        $priceColumn = "prix"; // Colonne prix par défaut
        
        switch($itemType) {
            case 'ab':
                $tableName = "ab_items";
                $idColumn = "id_ab";
                $nameColumn = "nom_ab";
                $descColumn = "description_ab";
                $catColumn = "categorie_ab";
                break;
            case 'ff':
                $tableName = "ff_items";
                $idColumn = "id_ff";
                $nameColumn = "nom_ff";
                $descColumn = "description_ff";
                $catColumn = "categorie_ff";
                break;
            case 'menu':
                $tableName = "menu_items";
                $idColumn = "id_menu";
                $nameColumn = "nom_menu";
                $descColumn = "description_menu";
                $catColumn = "categorie_menu";
                break;
            default:
                error_log("Type d'article non reconnu: $itemType");
                return [
                    'name' => 'Produit inconnu (type invalide)',
                    'description' => 'Description indisponible',
                    'categorie' => 'Catégorie inconnue',
                    'price' => 0
                ];
        }
        
        // Requête pour vérifier si la table existe
        $checkTableSQL = "SHOW TABLES LIKE '$tableName'";
        $tableExists = $conn->query($checkTableSQL)->rowCount() > 0;
        
        if (!$tableExists) {
            error_log("La table $tableName n'existe pas dans la base de données");
            return [
                'name' => 'Produit inconnu (table manquante)',
                'description' => 'Description indisponible',
                'categorie' => 'Catégorie inconnue',
                'price' => 0
            ];
        }
        
        // Requête pour vérifier les colonnes de la table
        $checkColumnsSQL = "SHOW COLUMNS FROM $tableName";
        $columnsResult = $conn->query($checkColumnsSQL);
        $columns = $columnsResult->fetchAll(PDO::FETCH_COLUMN);
        
        error_log("Colonnes disponibles dans $tableName: " . implode(", ", $columns));
        
        // Vérifier si les colonnes existent
        if (!in_array($idColumn, $columns)) {
            error_log("La colonne $idColumn n'existe pas dans la table $tableName");
            $idColumn = array_shift($columns); // Utiliser la première colonne comme ID
        }
        
        if (!in_array($nameColumn, $columns)) {
            error_log("La colonne $nameColumn n'existe pas dans la table $tableName");
            // Chercher une colonne avec 'nom' dans son nom
            foreach ($columns as $col) {
                if (stripos($col, 'nom') !== false) {
                    $nameColumn = $col;
                    break;
                }
            }
            if (!$nameColumn) $nameColumn = $idColumn;
        }
        
        if (!in_array($descColumn, $columns)) {
            error_log("La colonne $descColumn n'existe pas dans la table $tableName");
            // Chercher une colonne avec 'desc' dans son nom
            foreach ($columns as $col) {
                if (stripos($col, 'desc') !== false) {
                    $descColumn = $col;
                    break;
                }
            }
            if (!$descColumn) $descColumn = $nameColumn;
        }
        
        if (!in_array($catColumn, $columns)) {
            error_log("La colonne $catColumn n'existe pas dans la table $tableName");
            // Chercher une colonne avec 'cat' dans son nom
            foreach ($columns as $col) {
                if (stripos($col, 'cat') !== false) {
                    $catColumn = $col;
                    break;
                }
            }
            if (!$catColumn) $catColumn = $nameColumn;
        }
        
        if (!in_array($priceColumn, $columns)) {
            error_log("La colonne $priceColumn n'existe pas dans la table $tableName");
            // Chercher une colonne avec 'prix' ou 'price' dans son nom
            foreach ($columns as $col) {
                if (stripos($col, 'prix') !== false || stripos($col, 'price') !== false) {
                    $priceColumn = $col;
                    break;
                }
            }
            if (!$priceColumn) $priceColumn = '0';
        }
        
        // Construire la requête SQL avec les colonnes vérifiées
        $sql = "SELECT 
                $idColumn as id,
                $nameColumn as name, 
                $descColumn as description, 
                $catColumn as categorie, 
                $priceColumn as price 
                FROM $tableName 
                WHERE $idColumn = :item_id";
        
        error_log("Requête SQL: $sql");
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':item_id', $itemId);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            error_log("Aucun résultat trouvé pour id=$itemId dans la table $tableName");
            
            // Vérifier si l'ID existe dans la table
            $checkIdSQL = "SELECT COUNT(*) FROM $tableName";
            $totalItems = $conn->query($checkIdSQL)->fetchColumn();
            error_log("Nombre total d'éléments dans $tableName: $totalItems");
            
            return [
                'name' => 'Produit inconnu (ID invalide)',
                'description' => 'Description indisponible',
                'categorie' => 'Catégorie inconnue',
                'price' => 0
            ];
        }
        
        error_log("Résultat trouvé: " . print_r($result, true));
        return $result;
        
    } catch(PDOException $e) {
        error_log("Erreur PDO lors de la récupération des détails de l'article: " . $e->getMessage());
        return [
            'name' => 'Produit inconnu (erreur DB)',
            'description' => 'Description indisponible',
            'categorie' => 'Catégorie inconnue',
            'price' => 0
        ];
    } catch(Exception $e) {
        error_log("Erreur générale lors de la récupération des détails de l'article: " . $e->getMessage());
        return [
            'name' => 'Produit inconnu (erreur générale)',
            'description' => 'Description indisponible',
            'categorie' => 'Catégorie inconnue',
            'price' => 0
        ];
    }
}

// Fonction pour obtenir les informations d'un utilisateur par son ID
function getUserDetails($userId) {
    $conn = connectDB();
    
    try {
        $sql = "SELECT * FROM utilisateurs WHERE id = :user_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        die("Erreur lors de la récupération des détails de l'utilisateur: " . $e->getMessage());
    }
}

// Fonction pour obtenir toutes les commandes d'un utilisateur
function getUserOrders($userId) {
    $conn = connectDB();
    
    try {
        $sql = "SELECT o.*, COUNT(oi.id) as items_count 
                FROM orders o 
                LEFT JOIN order_items oi ON o.id = oi.order_id 
                WHERE o.user_id = :user_id 
                GROUP BY o.id
                ORDER BY o.created_at DESC";
                
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        die("Erreur lors de la récupération des commandes: " . $e->getMessage());
    }
}

// Fonction pour obtenir les articles d'une commande
// Fonction pour obtenir les articles d'une commande
function getOrderItems($orderId) {
    $conn = connectDB();
    
    try {
        $sql = "SELECT * FROM order_items WHERE order_id = :order_id";
                
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':order_id', $orderId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        die("Erreur lors de la récupération des articles de la commande: " . $e->getMessage());
    }
}

// Fonction pour mettre à jour le statut d'une commande
function updateOrderStatus($orderId, $status) {
    $conn = connectDB();
    
    try {
        $sql = "UPDATE orders SET statut = :status WHERE id = :order_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':order_id', $orderId);
        $stmt->execute();
        
        return true;
    } catch(PDOException $e) {
        die("Erreur lors de la mise à jour du statut de la commande: " . $e->getMessage());
    }
}

function updateExistingOrderItems() {
    $conn = connectDB();
    
    try {
        $sql = "SELECT * FROM order_items";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($items as $item) {
            $itemDetails = getItemDetails($item['item_id'], $item['item_type']);
            
            if ($itemDetails) {
                $sql = "UPDATE order_items SET 
                        item_name = :item_name,
                        item_description = :item_description,
                        item_categorie = :item_categorie
                        WHERE id = :id";
                        
                $updateStmt = $conn->prepare($sql);
                $updateStmt->bindParam(':item_name', $itemDetails['name']);
                $updateStmt->bindParam(':item_description', $itemDetails['description']);
                $updateStmt->bindParam(':item_categorie', $itemDetails['categorie']);
                $updateStmt->bindParam(':id', $item['id']);
                $updateStmt->execute();
            }
        }
        
        return true;
    } catch(PDOException $e) {
        die("Erreur lors de la mise à jour des articles existants: " . $e->getMessage());
    }
}
// Exemple d'utilisation:
/*
// Création d'une commande
$userId = 2; // ID de l'utilisateur (Maguette Gueye dans votre base de données)
$userInfo = getUserDetails($userId);
$orderId = createOrder(
    $userId, 
    $userInfo['nom'] . ' ' . $userInfo['prenom'], 
    $userInfo['email'], 
    $userInfo['telephone'], 
    $userInfo['adresse']
);

// Ajout des articles à la commande
// Par exemple, ajoutons un jus de Bouye (id 11 dans ab_items)
$itemDetails = getItemDetails(11, 'ab');
addOrderItem($orderId, 11, 'ab', 2, $itemDetails['price']);

// Ajoutons aussi un burger (id 7 dans ff_items)
$itemDetails = getItemDetails(7, 'ff');
addOrderItem($orderId, 7, 'ff', 1, $itemDetails['price']);

// Et enfin un repas du menu (id 9 dans menu_items - Yassa au Poulet)
$itemDetails = getItemDetails(9, 'menu');
addOrderItem($orderId, 9, 'menu', 1, $itemDetails['price']);

// Mettre à jour le statut de la commande
updateOrderStatus($orderId, 'en préparation');

// Affichage des commandes de l'utilisateur
$userOrders = getUserOrders($userId);
echo "Commandes de l'utilisateur {$userInfo['prenom']} {$userInfo['nom']}:<br>";
foreach ($userOrders as $order) {
    echo "Commande #{$order['id']} - Total: {$order['total_amount']} - Statut: {$order['statut']}<br>";
    
    $orderItems = getOrderItems($order['id']);
    foreach ($orderItems as $item) {
        echo "- {$item['item_name']} x{$item['quantity']} ({$item['item_categorie']}) - {$item['price']} F<br>";
    }
    echo "<br>";
}
*/
?>