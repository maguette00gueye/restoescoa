<?php
// validation_commande.php
session_start();
include "config/database.php";

// Vérifier si le panier existe et n'est pas vide
if (!isset($_SESSION['panier']) || empty($_SESSION['panier'])) {
    header('Location: menu.php');
    exit;
}

// Calculer le total
$total = 0;
foreach ($_SESSION['panier'] as $item) {
    $total += $item['prix'] * $item['quantite'];
}

// Traitement du formulaire de commande
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Générer un numéro de commande unique
        $numero_commande = 'CMD-' . date('Ymd') . '-' . uniqid();

        // Insérer la commande
        $stmt = $conn->prepare("INSERT INTO commandes (numero_commande, table_numero, total, notes, statut) VALUES (?, ?, ?, ?, 'en_attente')");
        $stmt->execute([
            $numero_commande,
            $_POST['table_numero'],
            $total,
            $_POST['notes']
        ]);

        $commande_id = $conn->lastInsertId();

        // Insérer les détails de la commande
        $stmt = $conn->prepare("INSERT INTO commande_details (commande_id, menu_id, quantite, prix_unitaire, notes) VALUES (?, ?, ?, ?, ?)");
        
        foreach ($_SESSION['panier'] as $menu_id => $item) {
            $stmt->execute([
                $commande_id,
                $menu_id,
                $item['quantite'],
                $item['prix'],
                ''  // Notes spécifiques par article si nécessaire
            ]);
        }

        $conn->commit();
        
        // Vider le panier
        unset($_SESSION['panier']);
        
        // Rediriger vers la page de confirmation
        $_SESSION['success_message'] = "Votre commande n° " . $numero_commande . " a été enregistrée avec succès.";
        header('Location: confirmation_commande.php?numero=' . $numero_commande);
        exit;

    } catch (Exception $e) {
        $conn->rollBack();
        $error_message = "Une erreur est survenue lors de la commande. Veuillez réessayer.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <?php include "head.php"; ?>
    <style>
        .recap-commande {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .table-numero {
            font-size: 24px;
            font-weight: bold;
            color: #1b00ff;
        }
    </style>
</head>

<body>
    <?php include "chargement.php"; ?>
    <?php include "header.php"; ?>
    <?php include "menu.php"; ?>

    <div class="main-container">
        <div class="pd-ltr-20 height-100-p xs-pd-20-10">
            <div class="min-height-200px">
                <div class="page-header">
                    <div class="row">
                        <div class="col-md-6 col-sm-12">
                            <div class="title">
                                <h4>Validation de la Commande</h4>
                            </div>
                            <nav aria-label="breadcrumb" role="navigation">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="index.php">Accueil</a></li>
                                    <li class="breadcrumb-item"><a href="menu.php">Menu</a></li>
                                    <li class="breadcrumb-item active">Validation</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <div class="card-box mb-30">
                    <div class="pd-20">
                        <h4 class="text-blue h4 mb-20">Récapitulatif de votre commande</h4>
                        
                        <div class="recap-commande">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Article</th>
                                        <th>Prix unitaire</th>
                                        <th>Quantité</th>
                                        <th class="text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($_SESSION['panier'] as $id => $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['nom']); ?></td>
                                            <td><?php echo number_format($item['prix'], 2); ?> €</td>
                                            <td><?php echo $item['quantite']; ?></td>
                                            <td class="text-right"><?php echo number_format($item['prix'] * $item['quantite'], 2); ?> €</td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr>
                                        <td colspan="3" class="text-right"><strong>Total</strong></td>
                                        <td class="text-right"><strong><?php echo number_format($total, 2); ?> €</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="table_numero">Numéro de table <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="table_numero" name="table_numero" 
                                               min="1" max="50" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="notes">Notes spéciales</label>
                                        <textarea class="form-control" id="notes" name="notes" 
                                                  placeholder="Allergies, préférences de cuisson, etc."></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="text-right">
                                <a href="menu.php" class="btn btn-secondary">Retour au menu</a>
                                <button type="submit" class="btn btn-primary">Confirmer la commande</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php include "footer.php"; ?>
        </div>
    </div>

    <!-- js -->
    <script src="vendors/scripts/core.js"></script>
    <script src="vendors/scripts/script.min.js"></script>
    <script src="vendors/scripts/process.js"></script>
    <script src="vendors/scripts/layout-settings.js"></script>
</body>
</html>